<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Branch extends Model
{
    public const TYPE_STORE = 'store';

    public const TYPE_WAREHOUSE = 'warehouse';

    public const SESSION_KEY = 'current_branch_id';

    protected $fillable = ['code', 'name', 'type', 'address', 'phone', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(BranchStock::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function isWarehouse(): bool
    {
        return $this->type === self::TYPE_WAREHOUSE;
    }

    public function getLabelAttribute(): string
    {
        return $this->name.($this->isWarehouse() ? ' (Warehouse)' : '');
    }

    /**
     * The branch the signed-in user is working in. Users locked to a branch always get
     * their own; others use the branch picked in the sidebar, falling back to the first active branch.
     */
    public static function current(): ?self
    {
        $user = Auth::user();

        if ($user && $user->branch_id && ! $user->canSwitchBranch()) {
            return $user->branch;
        }

        $id = session(self::SESSION_KEY) ?? $user?->branch_id;
        $branch = $id ? static::active()->find($id) : null;

        return $branch ?? static::active()->orderBy('id')->first();
    }
}
