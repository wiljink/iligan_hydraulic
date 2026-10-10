<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $fillable = ['transfer_number', 'from_branch_id', 'to_branch_id', 'transfer_date', 'notes', 'user_id'];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public static function generateTransferNumber(): string
    {
        $prefix = 'TR-'.now()->format('Ymd').'-';
        $last = static::where('transfer_number', 'like', $prefix.'%')->orderByDesc('transfer_number')->value('transfer_number');
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
