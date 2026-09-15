<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'Rent', 'Utilities', 'Salaries & Wages', 'Freight & Delivery',
        'Repairs & Maintenance', 'Fuel & Transportation', 'Office Supplies',
        'Government Fees & Taxes', 'Marketing', 'Other',
    ];

    protected $fillable = ['expense_date', 'category', 'description', 'amount', 'user_id'];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
