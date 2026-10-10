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
            $row = $row instanceof Collection ? $row->toArray() : (array) $row;

            // Template columns: Quantity, Unit, Product Name, Category, Part Number,
            // OEM Number, Cost, Selling Price. Older column names are still accepted.
            $name = trim((string) $this->column($row, ['product_name', 'name']));
            if ($name === '') {
                continue;
            }

            try {
                DB::transaction(function () use ($row, $name) {
                    $barcode = $this->nullableCode($row['barcode'] ?? null);
                    $sku = $this->nullableCode($row['sku'] ?? null);

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

                    // Only columns present in the file are written, so updating an existing
                    // product never wipes fields the template doesn't include.
                    $data = ['name' => $name];
                    $fields = [
                        'barcode' => [['barcode'], fn ($v) => $this->nullableCode($v)],
                        'part_number' => [['part_number'], fn ($v) => $this->nullableCode($v)],
                        'oem_number' => [['oem_number'], fn ($v) => $this->nullableString($v)],
                        'fitment' => [['fitment'], fn ($v) => $this->nullableString($v)],
                        'description' => [['description'], fn ($v) => $this->nullableString($v)],
                        'category_id' => [['category'], fn ($v) => $this->resolveCategory($v)],
                        'brand_id' => [['brand'], fn ($v) => $this->resolveBrand($v)],
                        'supplier_id' => [['supplier'], fn ($v) => $this->resolveSupplier($v)],
                        'unit' => [['unit'], fn ($v) => $this->nullableString($v) ?? 'pcs'],
                        'cost_price' => [['cost', 'cost_price'], fn ($v) => (float) $v],
                        'selling_price' => [['selling_price'], fn ($v) => (float) $v],
                        'reorder_level' => [['reorder_level'], fn ($v) => (int) ($v ?? 5)],
                        'shelf_location' => [['shelf_location'], fn ($v) => $this->nullableString($v)],
                    ];
                    foreach ($fields as $field => [$keys, $cast]) {
                        if ($this->hasColumn($row, $keys)) {
                            $data[$field] = $cast($this->column($row, $keys));
                        }
                    }

                    if ($sku) {
                        $data['sku'] = $sku;
                    }

                    $openingStock = (int) $this->column($row, ['quantity', 'opening_stock']);

                    if ($product) {
                        $product->fill($data)->save();
                        $this->updated++;
                    } else {
                        $data += ['unit' => 'pcs', 'cost_price' => 0, 'selling_price' => 0, 'reorder_level' => 5];
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

    private function hasColumn(array $row, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return true;
            }
        }

        return false;
    }

    private function column(array $row, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }

        return null;
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableCode($value): ?string
    {
        $value = $this->nullableString($value);
        if ($value === null) {
            return null;
        }

        // If Excel converted the code into scientific notation (e.g. 2.000000000018E+12 or 2E+12)
        if (preg_match('/^[+-]?\d+(?:\.\d+)?[eE][+-]?\d+$/i', $value)) {
            return sprintf('%.0f', (float) $value);
        }

        // If Excel loaded float e.g. "2000000000018.0"
        if (preg_match('/^(\d+)\.0+$/', $value, $m)) {
            return $m[1];
        }

        return $value;
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
