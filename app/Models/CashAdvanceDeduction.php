<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAdvanceDeduction extends Model
{
    protected $fillable = ['cash_advance_id', 'payroll_item_id', 'amount'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function cashAdvance(): BelongsTo
    {
        return $this->belongsTo(CashAdvance::class);
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
