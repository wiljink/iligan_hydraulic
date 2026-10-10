<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Employee extends Model
{
    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    protected $fillable = [
        'employee_no', 'first_name', 'last_name', 'position', 'branch_id', 'daily_rate',
        'schedule_in', 'schedule_out', 'break_minutes', 'grace_minutes', 'rest_day',
        'deduct_sss', 'deduct_philhealth', 'deduct_pagibig',
        'sss_no', 'philhealth_no', 'pagibig_no', 'tin_number', 'phone', 'address',
        'date_hired', 'is_active',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'date_hired' => 'date',
        'deduct_sss' => 'boolean',
        'deduct_philhealth' => 'boolean',
        'deduct_pagibig' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Link punches that arrived before the employee was set up.
        static::saved(function (Employee $employee) {
            AttendanceLog::where('employee_no', $employee->employee_no)
                ->whereNull('employee_id')
                ->update(['employee_id' => $employee->id]);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function cashAdvances(): HasMany
    {
        return $this->hasMany(CashAdvance::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->last_name}, {$this->first_name}";
    }

    /** Paid working minutes in a scheduled day (schedule minus break). */
    public function workMinutesPerDay(): int
    {
        $in = Carbon::parse($this->schedule_in);
        $out = Carbon::parse($this->schedule_out);

        return max(1, (int) $in->diffInMinutes($out) - (int) $this->break_minutes);
    }

    public function ratePerMinute(): float
    {
        return (float) $this->daily_rate / $this->workMinutesPerDay();
    }
}
