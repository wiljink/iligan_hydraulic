<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One employee's payslip within a payroll.
 */
class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id', 'employee_id', 'daily_rate', 'days_worked', 'late_minutes', 'undertime_minutes',
        'incomplete_days', 'basic_pay', 'late_undertime_deduction', 'adjustments', 'gross_pay',
        'sss', 'philhealth', 'pagibig', 'cash_advance', 'total_deductions', 'net_pay',
        'sss_employer', 'philhealth_employer', 'pagibig_employer', 'adjustment_note',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'days_worked' => 'decimal:2',
        'basic_pay' => 'decimal:2',
        'late_undertime_deduction' => 'decimal:2',
        'adjustments' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'sss' => 'decimal:2',
        'philhealth' => 'decimal:2',
        'pagibig' => 'decimal:2',
        'cash_advance' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'sss_employer' => 'decimal:2',
        'philhealth_employer' => 'decimal:2',
        'pagibig_employer' => 'decimal:2',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function cashAdvanceDeductions(): HasMany
    {
        return $this->hasMany(CashAdvanceDeduction::class);
    }
}
