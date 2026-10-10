<?php

namespace App\Services\Payroll;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Turns raw biometric punches into a day-by-day attendance sheet for one employee.
 *
 * Rules (day shift): the first punch of the day is time-in, the last is time-out.
 * Late = minutes after schedule-in once the grace period is exceeded; undertime =
 * minutes before schedule-out. A day missing its time-out (a single punch, or an odd
 * number ending hours before quitting time) is counted as worked but flagged
 * "incomplete" (no undertime) so HR can add the missing punch.
 */
class AttendanceSummary
{
    public const PRESENT = 'present';

    public const INCOMPLETE = 'incomplete';

    public const ABSENT = 'absent';

    public const REST_DAY = 'rest_day';

    /**
     * @return array{days: Collection, days_worked: int, late_minutes: int, undertime_minutes: int, incomplete_days: int, absent_days: int}
     */
    public function forEmployee(Employee $employee, Carbon $from, Carbon $to, ?Collection $logs = null): array
    {
        $logs ??= AttendanceLog::where('employee_no', $employee->employee_no)
            ->whereBetween('punched_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('punched_at')
            ->get();

        $byDate = $logs->groupBy(fn (AttendanceLog $log) => $log->punched_at->toDateString());

        $days = collect();
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $date) {
            $days->push($this->day($employee, Carbon::instance($date), $byDate->get($date->toDateString(), collect())));
        }

        return [
            'days' => $days,
            'days_worked' => $days->whereIn('status', [self::PRESENT, self::INCOMPLETE])->count(),
            'late_minutes' => (int) $days->sum('late'),
            'undertime_minutes' => (int) $days->sum('undertime'),
            'incomplete_days' => $days->where('status', self::INCOMPLETE)->count(),
            'absent_days' => $days->where('status', self::ABSENT)->count(),
        ];
    }

    private function day(Employee $employee, Carbon $date, Collection $punches): array
    {
        $isRestDay = $date->dayOfWeek === (int) $employee->rest_day;
        $row = ['date' => $date, 'in' => null, 'out' => null, 'punches' => $punches, 'late' => 0, 'undertime' => 0, 'rest_day' => $isRestDay];

        if ($punches->isEmpty()) {
            // Today isn't over yet, so no punches today doesn't mean absent.
            return $row + ['status' => $isRestDay ? self::REST_DAY : ($date->gte(today()) ? null : self::ABSENT)];
        }

        $scheduleIn = $date->copy()->setTimeFromTimeString($employee->schedule_in);
        $scheduleOut = $date->copy()->setTimeFromTimeString($employee->schedule_out);
        $first = $punches->first()->punched_at;
        $last = $punches->last()->punched_at;

        if ($punches->count() === 1) {
            // Only one punch: treat it as time-in if it's in the first half of the shift, otherwise time-out.
            $midShift = $scheduleIn->copy()->addMinutes((int) ($scheduleIn->diffInMinutes($scheduleOut) / 2));
            if ($first->lt($midShift)) {
                $row['in'] = $first;
                $row['late'] = $this->lateMinutes($employee, $scheduleIn, $first);
            } else {
                $row['out'] = $first;
            }

            return array_merge($row, ['status' => self::INCOMPLETE]);
        }

        $row['in'] = $first;
        $row['out'] = $last;
        $row['late'] = $this->lateMinutes($employee, $scheduleIn, $first);

        // Punches come in pairs (in/out, lunch out/in), so an odd count means one is missing.
        // If the last punch is near quitting time it's a skipped lunch punch (harmless); if it's
        // hours early (in + lunch out + lunch in, no time-out) it isn't a real time-out.
        if ($punches->count() % 2 === 1 && $last->lt($scheduleOut->copy()->subHours(2))) {
            return array_merge($row, ['status' => self::INCOMPLETE]);
        }

        $row['undertime'] = $last->lt($scheduleOut) ? (int) $last->diffInMinutes($scheduleOut) : 0;

        return array_merge($row, ['status' => self::PRESENT]);
    }

    private function lateMinutes(Employee $employee, Carbon $scheduleIn, Carbon $timeIn): int
    {
        $minutesLate = $timeIn->gt($scheduleIn) ? (int) $scheduleIn->diffInMinutes($timeIn) : 0;

        return $minutesLate > (int) $employee->grace_minutes ? $minutesLate : 0;
    }
}
