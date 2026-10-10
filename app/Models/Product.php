<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

        // Keep branch stock in sync when a product is created with stock already set.
        static::created(function (Product $product) {
            $branchId = Branch::current()?->id;
            if ($product->current_stock && $branchId) {
                BranchStock::create(['branch_id' => $branchId, 'product_id' => $product->id, 'quantity' => $product->current_stock]);
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

    public static function generateBarcode(): string
    {
        return \App\Services\BarcodeService::generateUniqueEan13();
    }

    public function getBarcodeCodeAttribute(): string
    {
        return $this->barcode ?: $this->sku;
    }

    public function isValidEan13(): bool
    {
        return ! empty($this->barcode) && \App\Services\BarcodeService::isValidEan13($this->barcode);
    }

    public function getBarcodeTypeAttribute(): string
    {
        if ($this->isValidEan13()) {
            return 'EAN-13';
        }

        return 'Code 128';
    }

    public function getBarcodeSvgAttribute(): string
    {
        return \App\Services\BarcodeService::generateSvg($this->barcode_code, 2, 50);
    }

    public function getBarcodeDataUriAttribute(): string
    {
        return \App\Services\BarcodeService::generateSvgDataUri($this->barcode_code, 2, 50);
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

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function branchStocks(): HasMany
    {
        return $this->hasMany(BranchStock::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    /**
     * Stock on hand at a single branch.
     */
    public function stockAt(Branch|int|null $branch): int
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        return (int) $this->branchStocks()->where('branch_id', $branchId)->value('quantity');
    }

    /**
     * Adds a `branch_stock` column holding each product's stock at the given branch.
     */
    public function scopeWithBranchStock(Builder $query, Branch|int|null $branch): Builder
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        if (is_null($query->getQuery()->columns)) {
            $query->select('products.*');
        }

        return $query->addSelect(['branch_stock' => BranchStock::selectRaw('COALESCE(SUM(quantity), 0)')
            ->whereColumn('branch_stocks.product_id', 'products.id')
            ->where('branch_stocks.branch_id', $branchId),
        ]);
    }

    /**
     * Single mutation path for stock: writes a ledger row and updates the branch and
     * company-wide balances. Defaults to the signed-in user's current branch.
     * balance_after on the ledger row is the branch balance.
     */
    public function recordMovement(string $type, int $quantity, ?string $note = null, ?string $referenceType = null, ?int $referenceId = null, ?int $branchId = null): StockMovement
    {
        $branchId ??= Branch::current()?->id;
        $balanceAfter = null;

        if ($branchId) {
            $branchStock = BranchStock::firstOrCreate(
                ['branch_id' => $branchId, 'product_id' => $this->id],
                ['quantity' => 0]
            );
            $branchStock->increment('quantity', $quantity);
            $balanceAfter = $branchStock->quantity;
        }

        $this->current_stock += $quantity;
        $this->save();

        return $this->stockMovements()->create([
            'branch_id' => $branchId,
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter ?? $this->current_stock,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => $note,
            'user_id' => Auth::id(),
        ]);
    }
}
