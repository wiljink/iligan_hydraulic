<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAdvance extends Model
{
    protected $fillable = ['employee_id', 'advance_date', 'amount', 'deduction_per_payroll', 'balance', 'notes', 'user_id'];

    protected $casts = [
        'advance_date' => 'date',
        'amount' => 'decimal:2',
        'deduction_per_payroll' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(CashAdvanceDeduction::class);
    }
}
