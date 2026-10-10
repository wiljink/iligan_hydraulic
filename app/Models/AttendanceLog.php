<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    public const SOURCE_IMPORT = 'import';

    public const SOURCE_DEVICE = 'device';

    public const SOURCE_MANUAL = 'manual';

    protected $fillable = ['employee_no', 'employee_id', 'punched_at', 'source', 'device_serial', 'user_id', 'note'];

    protected $casts = [
        'punched_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
