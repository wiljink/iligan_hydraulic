<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku', 'barcode', 'name', 'part_number', 'oem_number', 'fitment',
        'description', 'category_id', 'brand_id', 'supplier_id', 'unit',
        'cost_price', 'selling_price', 'current_stock', 'reorder_level',
        'shelf_location', 'image', 'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->sku)) {
                $product->sku = static::generateSku();
            }
        });
    }

    public static function generateSku(): string
    {
        do {
            $sku = 'IH-'.strtoupper(Str::random(6));
        } while (static::where('sku', $sku)->exists());

        return $sku;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    /**
     * Single mutation path for stock: writes a ledger row and updates the running balance.
     */
    public function recordMovement(string $type, int $quantity, ?string $note = null, ?string $referenceType = null, ?int $referenceId = null): StockMovement
    {
        $this->current_stock += $quantity;
        $this->save();

        return $this->stockMovements()->create([
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $this->current_stock,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => $note,
            'user_id' => Auth::id(),
        ]);
    }
}
