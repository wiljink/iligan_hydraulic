<?php

namespace App\Models;

use App\Models\Concerns\GeneratesDocumentNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An expense paid out on a Petty Cash Voucher (cash from the petty cash fund)
 * or a Check Voucher (paid by check).
 */
class Expense extends Model
{
    use GeneratesDocumentNumber, HasFactory;

    public const CATEGORIES = [
        'Rent', 'Utilities', 'Salaries & Wages', 'Freight & Delivery',
        'Repairs & Maintenance', 'Fuel & Transportation', 'Office Supplies',
        'Government Fees & Taxes', 'Marketing', 'Other',
    ];

    public const TYPE_PETTY_CASH = 'petty_cash';

    public const TYPE_CHECK = 'check';

    public const VOUCHER_TYPES = [
        self::TYPE_PETTY_CASH => 'Petty Cash Voucher',
        self::TYPE_CHECK => 'Check Voucher',
    ];

    protected $fillable = [
        'voucher_type', 'voucher_number', 'expense_date', 'category', 'payee', 'payee_address', 'description',
        'amount', 'bank_name', 'check_number', 'check_date', 'approved_by', 'user_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'check_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Expense $expense) {
            $expense->voucher_type ??= self::TYPE_PETTY_CASH;
            $expense->voucher_number ??= static::nextDocumentNumber(
                $expense->isCheck() ? 'CV' : 'PCV',
                'voucher_number'
            );
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCheck(): bool
    {
        return $this->voucher_type === self::TYPE_CHECK;
    }

    public function voucherTitle(): string
    {
        return self::VOUCHER_TYPES[$this->voucher_type] ?? 'Voucher';
    }
}
