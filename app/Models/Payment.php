<?php

namespace App\Models;

use App\Models\Concerns\GeneratesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payment received against an invoice. Each one is issued a Collection Receipt.
 */
class Payment extends Model
{
    use GeneratesDocumentNumber;

    public const METHODS = [
        'cash' => 'Cash',
        'gcash' => 'GCash',
        'bank_transfer' => 'Bank Transfer',
        'check' => 'Check',
        'card' => 'Card',
    ];

    protected $fillable = [
        'receipt_number', 'sale_id', 'customer_id', 'branch_id', 'user_id',
        'amount', 'payment_method', 'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public static function generateReceiptNumber(): string
    {
        return static::nextDocumentNumber('CR', 'receipt_number');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
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

    public function methodLabel(): string
    {
        return self::METHODS[$this->payment_method] ?? ucfirst(str_replace('_', ' ', $this->payment_method));
    }
}
