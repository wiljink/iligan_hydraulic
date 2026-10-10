<?php

namespace App\Services\Payroll;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Loads punches from ZKTeco exports:
 *  - USB download "1_attlog.dat" / .txt: "PIN <tab> YYYY-MM-DD HH:MM:SS <tab> ..." per line
 *  - Excel/CSV reports (ZKTime, ZKBioTime, Attendance Management): an employee number column
 *    (AC-No., User ID, Person ID, Emp No., ...) plus a date-time column, or separate Date and Time columns.
 * Punches already on file are skipped, so the same export can be imported again safely.
 */
class BiometricImporter
{
    // Most specific first: a bare "No." or "ID" is often just a row number, so it's the last resort.
    private const ID_HEADERS = ['acno', 'userid', 'personid', 'personnelid', 'empno', 'employeeno', 'employeeid',
        'empid', 'pin', 'badgenumber', 'enrollnumber', 'enrollno', 'user', 'no', 'id'];

    private const DATETIME_HEADERS = ['time', 'datetime', 'punchtime', 'checktime', 'atttime', 'attendancetime',
        'logtime', 'timestamp', 'verifytime', 'clockin'];

    private const DATE_HEADERS = ['date', 'punchdate', 'attdate'];

    private const TIME_ONLY_HEADERS = ['time', 'punchtime', 'checktime'];

    /**
     * @return array{imported: int, duplicates: int, unknown: array<int, string>, invalid: int}
     */
    public function import(string $path, string $originalName, ?int $userId = null, string $source = AttendanceLog::SOURCE_IMPORT, ?string $deviceSerial = null): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $punches = in_array($extension, ['dat', 'txt'], true)
            ? $this->parseText(file_get_contents($path))
            : $this->parseSpreadsheet($path);

        return $this->store($punches['rows'], $punches['invalid'], $userId, $source, $deviceSerial);
    }

    /**
     * Parse ZKTeco ATTLOG text (USB download, or the body the device pushes over ADMS).
     *
     * @return array{rows: array<int, array{0: string, 1: Carbon}>, invalid: int}
     */
    public function parseText(string $content): array
    {
        $rows = [];
        $invalid = 0;

        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            if (trim($line) === '') {
                continue;
            }
            if (preg_match('/^\s*(\S+)\s+(\d{4}-\d{1,2}-\d{1,2}[ T]\d{1,2}:\d{2}(?::\d{2})?)/', $line, $m)) {
                $rows[] = [$m[1], Carbon::parse($m[2])];
            } else {
                $invalid++;
            }
        }

        return ['rows' => $rows, 'invalid' => $invalid];
    }

    /** @return array{rows: array<int, array{0: string, 1: Carbon}>, invalid: int} */
    private function parseSpreadsheet(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, false);

        // Find the header row (reports often start with a title block).
        foreach ($sheet as $index => $row) {
            $headers = array_map(fn ($h) => preg_replace('/[^a-z0-9]/', '', strtolower((string) $h)), $row);
            $idCol = $this->findColumn($headers, self::ID_HEADERS);
            $dateTimeCol = $this->findColumn($headers, self::DATETIME_HEADERS);
            $dateCol = $this->findColumn($headers, self::DATE_HEADERS);
            $timeCol = $this->findColumn($headers, self::TIME_ONLY_HEADERS);

            if ($idCol !== null && ($dateTimeCol !== null || $dateCol !== null)) {
                return $this->readRows(array_slice($sheet, $index + 1), $idCol, $dateTimeCol, $dateCol, $timeCol);
            }
        }

        throw new InvalidArgumentException('Could not find the employee number and date/time columns. Expected headers like "AC-No." / "User ID" and "Time" / "Date" + "Time".');
    }

    private function readRows(array $rows, int $idCol, ?int $dateTimeCol, ?int $dateCol, ?int $timeCol): array
    {
        $parsed = [];
        $invalid = 0;
        // With separate Date and Time columns, "Time" holds only the clock time.
        $separate = $dateCol !== null && $timeCol !== null && $dateCol !== $timeCol;

        foreach ($rows as $row) {
            $employeeNo = trim((string) ($row[$idCol] ?? ''));
            if ($employeeNo === '') {
                continue;
            }
            $employeeNo = preg_replace('/\.0+$/', '', $employeeNo); // Excel turned 12 into 12.0

            try {
                $at = $separate
                    ? $this->toCarbon($row[$dateCol])->setTimeFromTimeString($this->toCarbon($row[$timeCol], true)->format('H:i:s'))
                    : $this->toCarbon($row[$dateTimeCol ?? $dateCol]);
                $parsed[] = [$employeeNo, $at];
            } catch (\Throwable) {
                $invalid++;
            }
        }

        return ['rows' => $parsed, 'invalid' => $invalid];
    }

    private function toCarbon(mixed $value, bool $timeOnly = false): Carbon
    {
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
        }
        if (trim((string) $value) === '') {
            throw new InvalidArgumentException('Empty date');
        }

        return $timeOnly ? Carbon::parse('2000-01-01 '.trim((string) $value)) : Carbon::parse(trim((string) $value));
    }

    private function findColumn(array $headers, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            $index = array_search($candidate, $headers, true);
            if ($index !== false) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{0: string, 1: Carbon}>  $rows
     * @return array{imported: int, duplicates: int, unknown: array<int, string>, invalid: int}
     */
    public function store(array $rows, int $invalid = 0, ?int $userId = null, string $source = AttendanceLog::SOURCE_IMPORT, ?string $deviceSerial = null): array
    {
        $employees = Employee::pluck('id', 'employee_no');
        $now = now();
        $imported = 0;
        $unknown = [];

        foreach (array_chunk($rows, 500) as $chunk) {
            $records = array_map(function ($row) use ($employees, $now, $userId, $source, $deviceSerial, &$unknown) {
                [$employeeNo, $at] = $row;
                if (! isset($employees[$employeeNo])) {
                    $unknown[$employeeNo] = $employeeNo;
                }

                return [
                    'employee_no' => $employeeNo,
                    'employee_id' => $employees[$employeeNo] ?? null,
                    'punched_at' => $at->format('Y-m-d H:i:s'),
                    'source' => $source,
                    'device_serial' => $deviceSerial,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $chunk);

            $imported += AttendanceLog::insertOrIgnore($records);
        }

        return [
            'imported' => $imported,
            'duplicates' => count($rows) - $imported,
            'unknown' => array_values($unknown),
            'invalid' => $invalid,
        ];
    }
}
