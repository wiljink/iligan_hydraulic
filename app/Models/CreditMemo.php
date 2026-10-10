<?php

namespace App\Models;

use App\Models\Concerns\GeneratesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Issued when a customer returns items from an invoice.
 */
class CreditMemo extends Model
{
    use GeneratesDocumentNumber;

    protected $fillable = [
        'memo_number', 'sale_id', 'customer_id', 'branch_id', 'user_id',
        'reason', 'approved_by', 'total', 'applied_to_balance', 'refunded',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'applied_to_balance' => 'decimal:2',
        'refunded' => 'decimal:2',
    ];

    public static function generateMemoNumber(): string
    {
        return static::nextDocumentNumber('CM', 'memo_number');
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

    public function items(): HasMany
    {
        return $this->hasMany(CreditMemoItem::class);
    }
}
