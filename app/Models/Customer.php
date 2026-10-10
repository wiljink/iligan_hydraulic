<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'tin_number', 'phone', 'email', 'contact_person', 'vehicle_info', 'credit_balance'];

    protected $casts = [
        'credit_balance' => 'decimal:2',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creditMemos(): HasMany
    {
        return $this->hasMany(CreditMemo::class);
    }
}
