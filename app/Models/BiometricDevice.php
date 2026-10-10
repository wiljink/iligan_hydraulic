<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A ZKTeco (or compatible ADMS/iClock) device allowed to push punches to the system.
 */
class BiometricDevice extends Model
{
    protected $fillable = ['serial_number', 'name', 'branch_id', 'last_seen_at'];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
