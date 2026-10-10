<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountPayable extends Model
{
    use HasFactory;

    protected $table = 'accounts_payable';

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'supplier_id',
        'vendor_name',
        'bill_number',
        'bill_date',
        'due_date',
        'amount',
        'amount_paid',
        'status',
        'payment_method',
        'reference_number',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->amount_paid);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== self::STATUS_PAID &&
            $this->due_date !== null &&
            $this->due_date->lt(now()->startOfDay());
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue || ! $this->due_date) {
            return 0;
        }

        return (int) $this->due_date->diffInDays(now()->startOfDay());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_PAID);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_PAID)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString());
    }

    public function scopeDueSoon(Builder $query, int $days = 7): Builder
    {
        return $query->where('status', '!=', self::STATUS_PAID)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }
}
