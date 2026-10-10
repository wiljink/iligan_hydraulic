<?php

namespace App\Models;

use App\Models\Concerns\GeneratesDocumentNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use GeneratesDocumentNumber, HasFactory;

    /**
     * Outstanding balance in SQL: what is still owed after payments and returns.
     * Cash sales store the amount tendered, so payments are capped at the invoice total.
     * Returns first reduce the balance; anything beyond it is refunded (refunded_amount).
     */
    public const BALANCE_SQL = '(total - returned_amount - CASE WHEN amount_paid > total THEN total ELSE amount_paid END + refunded_amount)';

    protected $fillable = [
        'invoice_number', 'branch_id', 'customer_id', 'user_id', 'subtotal', 'discount',
        'tax', 'total', 'amount_paid', 'returned_amount', 'refunded_amount', 'change_due', 'payment_method',
        'payment_reference', 'status', 'voided_by', 'voided_at', 'void_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'returned_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'change_due' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    /**
     * Credit sales are issued a Charge Invoice (CI-), everything else a Sales Invoice (SI-).
     */
    public static function generateInvoiceNumber(string $paymentMethod = 'cash'): string
    {
        return static::nextDocumentNumber($paymentMethod === 'credit' ? 'CI' : 'SI', 'invoice_number');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creditMemos(): HasMany
    {
        return $this->hasMany(CreditMemo::class);
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function isCharge(): bool
    {
        return $this->payment_method === 'credit';
    }

    public function documentTitle(): string
    {
        return $this->isCharge() ? 'Charge Invoice' : 'Sales Invoice';
    }

    public function netTotal(): float
    {
        return round((float) $this->total - (float) $this->returned_amount, 2);
    }

    public function balance(): float
    {
        if ($this->isVoided()) {
            return 0.0;
        }

        return max(0, round((float) $this->total - (float) $this->returned_amount - min((float) $this->amount_paid, (float) $this->total) + (float) $this->refunded_amount, 2));
    }

    /**
     * Payment made at the counter when the sale was rung up, for sales from before
     * collection receipts existed (those have no Payment row).
     */
    public function paidAtSale(): float
    {
        $collected = $this->relationLoaded('payments') ? $this->payments->sum('amount') : $this->payments()->sum('amount');

        return max(0, round(min((float) $this->amount_paid, (float) $this->total) - (float) $collected, 2));
    }
}
