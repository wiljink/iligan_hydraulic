<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\BiometricDevice;
use App\Models\Branch;
use App\Models\Employee;
use App\Services\Payroll\AttendanceSummary;
use App\Services\Payroll\BiometricImporter;
use App\Services\Payroll\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use InvalidArgumentException;

class AttendanceController extends Controller
{
    public function index(Request $request, AttendanceSummary $summary): View
    {
        [$from, $to] = $this->period($request);

        $logs = AttendanceLog::whereBetween('punched_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('punched_at')
            ->get()
            ->groupBy('employee_no');

        $rows = Employee::active()->orderBy('last_name')->get()
            ->map(fn (Employee $e) => ['employee' => $e] + $summary->forEmployee($e, $from, $to, $logs->get($e->employee_no, collect())));

        // Punches from device users not yet set up as employees.
        $unknownNumbers = $logs->keys()->diff(Employee::pluck('employee_no'))->values();

        return view('hr.attendance.index', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'unknownNumbers' => $unknownNumbers,
            'devices' => BiometricDevice::with('branch')->orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Employee $employee, AttendanceSummary $summary): View
    {
        [$from, $to] = $this->period($request);

        return view('hr.attendance.show', ['employee' => $employee, 'from' => $from, 'to' => $to] + $summary->forEmployee($employee, $from, $to));
    }

    public function import(Request $request, BiometricImporter $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'extensions:dat,txt,csv,xls,xlsx'],
        ]);

        $file = $request->file('file');

        try {
            $result = $importer->import($file->getRealPath(), $file->getClientOriginalName(), $request->user()->id);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = "Imported {$result['imported']} punch(es); {$result['duplicates']} already on file were skipped.";
        if ($result['invalid']) {
            $message .= " {$result['invalid']} unreadable row(s) ignored.";
        }
        if ($result['unknown']) {
            $message .= ' No employee yet for device ID(s): '.implode(', ', array_slice($result['unknown'], 0, 15)).'. Add them as employees and their punches link up automatically.';
        }

        return back()->with($result['unknown'] ? 'warning' : 'success', $message);
    }

    public function storePunch(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'punched_at' => ['required', 'date'],
            'note' => ['required', 'string', 'max:255'],
        ]);

        $created = AttendanceLog::firstOrCreate(
            ['employee_no' => $employee->employee_no, 'punched_at' => Carbon::parse($data['punched_at'])->format('Y-m-d H:i:00')],
            ['employee_id' => $employee->id, 'source' => AttendanceLog::SOURCE_MANUAL, 'user_id' => $request->user()->id, 'note' => $data['note']]
        );

        return back()->with($created->wasRecentlyCreated ? 'success' : 'warning', $created->wasRecentlyCreated ? 'Punch added.' : 'That punch is already on file.');
    }

    public function destroyPunch(AttendanceLog $log): RedirectResponse
    {
        // Only hand-entered punches can be removed; device records are the audit trail.
        abort_unless($log->source === AttendanceLog::SOURCE_MANUAL, 403, 'Only manually added punches can be deleted.');

        $log->delete();

        return back()->with('success', 'Punch removed.');
    }

    public function storeDevice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'serial_number' => ['required', 'string', 'max:50', 'unique:biometric_devices,serial_number'],
            'name' => ['required', 'string', 'max:100'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);

        BiometricDevice::create($data);

        return back()->with('success', 'Device registered. Point its Cloud Server (ADMS) setting to this site to start receiving punches.');
    }

    public function destroyDevice(BiometricDevice $device): RedirectResponse
    {
        $device->delete();

        return back()->with('success', 'Device removed. It can no longer send punches.');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function period(Request $request): array
    {
        if ($request->filled('from') && $request->filled('to')) {
            return [Carbon::parse($request->get('from'))->startOfDay(), Carbon::parse($request->get('to'))->startOfDay()];
        }

        return PayrollService::periodFor(now());
    }
}
