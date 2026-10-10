<?php

namespace App\Services\Payroll;

use App\Models\AttendanceLog;
use App\Models\CashAdvance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Semi-monthly payroll for daily-rated employees.
 *
 *  basic pay  = days worked x daily rate
 *  gross pay  = basic - late/undertime + adjustments
 *  deductions = SSS + PhilHealth + Pag-IBIG (half of the monthly share each cut-off)
 *               + cash advance installments
 *
 * Monthly contributions are based on daily rate x payroll_days_per_month (default 26).
 */
class PayrollService
{
    public function __construct(private AttendanceSummary $attendance) {}

    /** The semi-monthly cut-off containing the given date: 1st-15th or 16th-end of month. */
    public static function periodFor(Carbon $date): array
    {
        return $date->day <= 15
            ? [$date->copy()->startOfMonth(), $date->copy()->startOfMonth()->addDays(14)]
            : [$date->copy()->startOfMonth()->addDays(15), $date->copy()->endOfMonth()->startOfDay()];
    }

    public function create(Carbon $periodStart, Carbon $periodEnd): Payroll
    {
        if ($periodEnd->lt($periodStart)) {
            throw new InvalidArgumentException('The period end must be on or after the start.');
        }
        if (Payroll::whereDate('period_start', $periodStart)->whereDate('period_end', $periodEnd)->exists()) {
            throw new InvalidArgumentException('A payroll for this period already exists.');
        }

        $payroll = Payroll::create([
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'status' => Payroll::STATUS_DRAFT,
            'user_id' => Auth::id(),
        ]);

        return $this->compute($payroll);
    }

    /**
     * (Re)compute every payslip from the attendance logs. Manual adjustments are kept.
     */
    public function compute(Payroll $payroll): Payroll
    {
        $this->ensureDraft($payroll);

        return DB::transaction(function () use ($payroll) {
            $from = $payroll->period_start->copy();
            $to = $payroll->period_end->copy();

            $logs = AttendanceLog::whereBetween('punched_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->orderBy('punched_at')
                ->get()
                ->groupBy('employee_no');

            // Active employees, plus anyone inactive who still punched in this period.
            $employees = Employee::where(fn ($q) => $q->where('is_active', true)->orWhereIn('employee_no', $logs->keys()))
                ->orderBy('last_name')
                ->get();

            $existing = $payroll->items()->get()->keyBy('employee_id');
            $payroll->items()->whereNotIn('employee_id', $employees->pluck('id'))->delete();

            foreach ($employees as $employee) {
                $summary = $this->attendance->forEmployee($employee, $from, $to, $logs->get($employee->employee_no, collect()));
                $item = $existing->get($employee->id) ?? new PayrollItem(['payroll_id' => $payroll->id, 'employee_id' => $employee->id]);

                $this->fillItem($item, $employee, $summary);
                $item->save();
            }

            return $this->refreshTotals($payroll);
        });
    }

    /** Recalculate one payslip after its adjustment changes. */
    public function adjust(PayrollItem $item, float $amount, ?string $note): PayrollItem
    {
        $this->ensureDraft($item->payroll);

        $item->adjustments = round($amount, 2);
        $item->adjustment_note = $note;
        $this->calculateTotals($item, $item->employee);
        $item->save();
        $this->refreshTotals($item->payroll);

        return $item;
    }

    /**
     * Lock the payroll and post cash advance installments against the employees' balances.
     */
    public function finalize(Payroll $payroll): Payroll
    {
        $this->ensureDraft($payroll);

        return DB::transaction(function () use ($payroll) {
            foreach ($payroll->items()->where('cash_advance', '>', 0)->get() as $item) {
                $remaining = (float) $item->cash_advance;

                $advances = CashAdvance::where('employee_id', $item->employee_id)
                    ->where('balance', '>', 0)
                    ->orderBy('advance_date')
                    ->lockForUpdate()
                    ->get();

                foreach ($advances as $advance) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $amount = round(min($remaining, (float) $advance->balance, (float) $advance->deduction_per_payroll), 2);
                    $advance->decrement('balance', $amount);
                    $advance->deductions()->create(['payroll_item_id' => $item->id, 'amount' => $amount]);
                    $remaining = round($remaining - $amount, 2);
                }

                // Balance was already paid down elsewhere (e.g. another payroll): don't deduct more than was owed.
                if ($remaining > 0) {
                    $item->cash_advance = round((float) $item->cash_advance - $remaining, 2);
                    $item->total_deductions = round((float) $item->total_deductions - $remaining, 2);
                    $item->net_pay = round((float) $item->net_pay + $remaining, 2);
                    $item->save();
                }
            }

            $this->refreshTotals($payroll);

            $payroll->update([
                'status' => Payroll::STATUS_FINALIZED,
                'finalized_at' => now(),
            ]);

            return $payroll;
        });
    }

    private function fillItem(PayrollItem $item, Employee $employee, array $summary): void
    {
        $item->daily_rate = $employee->daily_rate;
        $item->days_worked = $summary['days_worked'];
        $item->late_minutes = $summary['late_minutes'];
        $item->undertime_minutes = $summary['undertime_minutes'];
        $item->incomplete_days = $summary['incomplete_days'];
        $item->adjustments ??= 0;

        $this->calculateTotals($item, $employee);
    }

    private function calculateTotals(PayrollItem $item, Employee $employee): void
    {
        $item->basic_pay = round((float) $item->daily_rate * (float) $item->days_worked, 2);
        $item->late_undertime_deduction = round(($item->late_minutes + $item->undertime_minutes) * $employee->ratePerMinute(), 2);
        $item->gross_pay = round($item->basic_pay - $item->late_undertime_deduction + (float) $item->adjustments, 2);

        // No contributions for a cut-off with no work.
        $worked = (float) $item->days_worked > 0;
        $monthly = (float) $employee->daily_rate * (float) (setting('payroll_days_per_month') ?: 26);

        $sss = $worked && $employee->deduct_sss ? GovernmentContributions::sss($monthly) : ['employee' => 0, 'employer' => 0];
        $philhealth = $worked && $employee->deduct_philhealth ? GovernmentContributions::philhealth($monthly) : ['employee' => 0, 'employer' => 0];
        $pagibig = $worked && $employee->deduct_pagibig ? GovernmentContributions::pagibig($monthly) : ['employee' => 0, 'employer' => 0];

        // Semi-monthly: half of each monthly share per cut-off.
        $item->sss = round($sss['employee'] / 2, 2);
        $item->philhealth = round($philhealth['employee'] / 2, 2);
        $item->pagibig = round($pagibig['employee'] / 2, 2);
        $item->sss_employer = round($sss['employer'] / 2, 2);
        $item->philhealth_employer = round($philhealth['employer'] / 2, 2);
        $item->pagibig_employer = round($pagibig['employer'] / 2, 2);

        $government = $item->sss + $item->philhealth + $item->pagibig;

        // Cash advance installments, but never more than what is left after contributions.
        $due = CashAdvance::where('employee_id', $employee->id)
            ->where('balance', '>', 0)
            ->get()
            ->sum(fn (CashAdvance $a) => min((float) $a->balance, (float) $a->deduction_per_payroll));
        $item->cash_advance = round(max(0, min($due, (float) $item->gross_pay - $government)), 2);

        $item->total_deductions = round($government + $item->cash_advance, 2);
        $item->net_pay = round((float) $item->gross_pay - $item->total_deductions, 2);
    }

    private function refreshTotals(Payroll $payroll): Payroll
    {
        $items = $payroll->items()->get();

        $payroll->update([
            'total_gross' => $items->sum('gross_pay'),
            'total_deductions' => $items->sum('total_deductions'),
            'total_net' => $items->sum('net_pay'),
        ]);

        return $payroll;
    }

    private function ensureDraft(Payroll $payroll): void
    {
        if ($payroll->isFinalized()) {
            throw new InvalidArgumentException('This payroll is already finalized and can no longer be changed.');
        }
    }
}
