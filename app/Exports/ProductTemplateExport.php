<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProductTemplateExport implements Export, WithMultipleSheets
{
    public function __construct(private bool $withProducts = false) {}

    public function sheets(): array
    {
        $sheets = [
            'Products' => new class($this->withProducts) implements FromArray, WithHeadings, WithTitle
            {
                public function __construct(private bool $withProducts) {}

                public function headings(): array
                {
                    return [
                        'name', 'sku', 'barcode', 'part_number', 'oem_number', 'fitment',
                        'category', 'brand', 'supplier', 'unit', 'cost_price', 'selling_price',
                        'reorder_level', 'shelf_location', 'opening_stock', 'description',
                    ];
                }

                public function array(): array
                {
                    if (! $this->withProducts) {
                        return [[
                            'Hydraulic Hose 1/2in', '', '1234567890123', 'HH-050', '', 'Universal',
                            'Hoses', 'Generic', 'Main Supplier', 'meter', 150, 220,
                            10, 'A1-01', 50, 'High-pressure hydraulic hose',
                        ]];
                    }

                    return Product::with(['category', 'brand', 'supplier'])
                        ->orderBy('name')
                        ->get()
                        ->map(fn (Product $p) => [
                            $p->name, $p->sku, $p->barcode, $p->part_number, $p->oem_number, $p->fitment,
                            $p->category?->name, $p->brand?->name, $p->supplier?->name, $p->unit,
                            $p->cost_price, $p->selling_price, $p->reorder_level, $p->shelf_location,
                            $p->current_stock, $p->description,
                        ])
                        ->toArray();
                }

                public function title(): string
                {
                    return 'Products';
                }
            },
            'Instructions' => new class implements FromArray, WithTitle
            {
                public function array(): array
                {
                    return [
                        ['Iligan Hydraulics & Auto Parts - Product Import Template'],
                        [''],
                        ['- name is required.'],
                        ['- Leave sku blank to auto-generate one for new products.'],
                        ['- Matching priority: barcode, then sku, then name (case-sensitive).'],
                        ['- category / brand / supplier are created automatically if they do not exist.'],
                        ['- opening_stock is only applied when creating a brand-new product.'],
                        ['- unit examples: pcs, set, pair, liter, box, roll, meter, gallon, kit.'],
                    ];
                }

                public function title(): string
                {
                    return 'Instructions';
                }
            },
        ];

        return $sheets;
    }
}
