<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements Import, ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            try {
                DB::transaction(function () use ($row, $name) {
                    $barcode = $this->nullableString($row['barcode'] ?? null);
                    $sku = $this->nullableString($row['sku'] ?? null);

                    $product = null;
                    if ($barcode) {
                        $product = Product::where('barcode', $barcode)->first();
                    }
                    if (! $product && $sku) {
                        $product = Product::where('sku', $sku)->first();
                    }
                    if (! $product) {
                        $product = Product::where('name', $name)->first();
                    }

                    $categoryId = $this->resolveCategory($row['category'] ?? null);
                    $brandId = $this->resolveBrand($row['brand'] ?? null);
                    $supplierId = $this->resolveSupplier($row['supplier'] ?? null);

                    $data = [
                        'name' => $name,
                        'barcode' => $barcode,
                        'part_number' => $this->nullableString($row['part_number'] ?? null),
                        'oem_number' => $this->nullableString($row['oem_number'] ?? null),
                        'fitment' => $this->nullableString($row['fitment'] ?? null),
                        'description' => $this->nullableString($row['description'] ?? null),
                        'category_id' => $categoryId,
                        'brand_id' => $brandId,
                        'supplier_id' => $supplierId,
                        'unit' => $this->nullableString($row['unit'] ?? null) ?? 'pcs',
                        'cost_price' => (float) ($row['cost_price'] ?? 0),
                        'selling_price' => (float) ($row['selling_price'] ?? 0),
                        'reorder_level' => (int) ($row['reorder_level'] ?? 5),
                        'shelf_location' => $this->nullableString($row['shelf_location'] ?? null),
                    ];

                    if ($sku) {
                        $data['sku'] = $sku;
                    }

                    $openingStock = (int) ($row['opening_stock'] ?? 0);

                    if ($product) {
                        $product->fill($data)->save();
                        $this->updated++;
                    } else {
                        $data['current_stock'] = 0;
                        $product = Product::create($data);
                        $this->created++;

                        if ($openingStock > 0) {
                            $product->recordMovement(
                                StockMovement::TYPE_OPENING,
                                $openingStock,
                                'Opening stock via import'
                            );
                        }
                    }
                });
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNumber} ({$name}): {$e->getMessage()}";
            }
        }
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveCategory($value): ?int
    {
        $name = $this->nullableString($value);
        if (! $name) {
            return null;
        }

        $slug = \Illuminate\Support\Str::slug($name);

        $category = Category::where('name', $name)->orWhere('slug', $slug)->first();

        return ($category ?? Category::create(['name' => $name, 'slug' => $slug]))->id;
    }

    private function resolveBrand($value): ?int
    {
        $name = $this->nullableString($value);
        if (! $name) {
            return null;
        }

        return Brand::firstOrCreate(['name' => $name])->id;
    }

    private function resolveSupplier($value): ?int
    {
        $name = $this->nullableString($value);
        if (! $name) {
            return null;
        }

        return Supplier::firstOrCreate(['name' => $name])->id;
    }
}
