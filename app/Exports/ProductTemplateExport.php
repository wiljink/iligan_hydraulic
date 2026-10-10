<?php

namespace App\Exports;

use App\Models\Category;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductTemplateExport implements Export, WithMultipleSheets
{
    public function __construct(private bool $withProducts = false) {}

    public function sheets(): array
    {
        $categories = Category::orderBy('name')->pluck('name')->all();

        $sheets = [
            'Products' => new class($this->withProducts, $categories) extends DefaultValueBinder implements
                FromArray,
                WithHeadings,
                WithTitle,
                WithColumnFormatting,
                WithCustomValueBinder,
                WithEvents,
                ShouldAutoSize
            {
                /** Rows below the header that get the Category dropdown. */
                private const DROPDOWN_ROWS = 2000;

                public function __construct(private bool $withProducts, private array $categories) {}

                public function headings(): array
                {
                    return [
                        'Quantity', 'Unit', 'Product Name', 'Barcode', 'Category',
                        'Part Number', 'OEM Number', 'Cost', 'Selling Price',
                    ];
                }

                public function array(): array
                {
                    if (! $this->withProducts) {
                        return [[
                            50, 'meter', 'Hydraulic Hose 1/2in', '4806512345678', $this->categories[0] ?? 'Hydraulic Hoses',
                            'HH-050', '8-97139-415-0, 8-97139-416-0, 1-13240-216-0', 150, 220,
                        ]];
                    }

                    return Product::with('category')
                        ->orderBy('name')
                        ->get()
                        ->map(fn (Product $p) => [
                            $p->current_stock,
                            $p->unit,
                            $p->name,
                            (string) ($p->barcode ?? ''),
                            $p->category?->name,
                            (string) ($p->part_number ?? ''),
                            (string) ($p->oem_number ?? ''),
                            $p->cost_price,
                            $p->selling_price,
                        ])
                        ->toArray();
                }

                public function columnFormats(): array
                {
                    return [
                        'D' => NumberFormat::FORMAT_TEXT, // barcode
                        'F' => NumberFormat::FORMAT_TEXT, // part number
                        'G' => NumberFormat::FORMAT_TEXT, // oem number(s)
                    ];
                }

                public function bindValue(Cell $cell, $value): bool
                {
                    $col = $cell->getColumn();
                    if (in_array($col, ['D', 'F', 'G'], true) && is_scalar($value) && $value !== '') {
                        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

                        return true;
                    }

                    return parent::bindValue($cell, $value);
                }

                public function title(): string
                {
                    return 'Products';
                }

                public function registerEvents(): array
                {
                    return [
                        AfterSheet::class => function (AfterSheet $event) {
                            if (empty($this->categories)) {
                                return;
                            }

                            // Category column (E) picks from the system's categories, listed on the hidden "Categories" sheet.
                            $validation = new DataValidation;
                            $validation->setType(DataValidation::TYPE_LIST)
                                ->setErrorStyle(DataValidation::STYLE_STOP)
                                ->setAllowBlank(true)
                                ->setShowDropDown(true)
                                ->setShowErrorMessage(true)
                                ->setErrorTitle('Unknown category')
                                ->setError('Pick a category from the list. To use a new one, add it on the Categories page first and download a fresh template.')
                                ->setShowInputMessage(true)
                                ->setPromptTitle('Category')
                                ->setPrompt('Choose from the list')
                                ->setFormula1("'Categories'!\$A\$1:\$A\$".count($this->categories));

                            $sheet = $event->sheet->getDelegate();
                            $lastRow = max(self::DROPDOWN_ROWS, $sheet->getHighestRow() + 500);
                            $sheet->setDataValidation("E2:E{$lastRow}", $validation);
                        },
                    ];
                }
            },
            // Source list for the Category dropdown; hidden so it stays out of the way.
            'Categories' => new class($categories) implements FromArray, WithTitle, WithEvents
            {
                public function __construct(private array $categories) {}

                public function array(): array
                {
                    return array_map(fn ($name) => [$name], $this->categories);
                }

                public function title(): string
                {
                    return 'Categories';
                }

                public function registerEvents(): array
                {
                    return [
                        AfterSheet::class => fn (AfterSheet $event) => $event->sheet->getDelegate()->setSheetState(Worksheet::SHEETSTATE_HIDDEN),
                    ];
                }
            },
            'Instructions' => new class implements FromArray, WithTitle, ShouldAutoSize
            {
                public function array(): array
                {
                    return [
                        ['Osmena Hydraulic - Product Import Template'],
                        [''],
                        ['- Product Name is required.'],
                        ['- Quantity is the opening stock and is only applied when creating a brand-new product.'],
                        ['- OEM Number can hold many numbers in one cell - separate them with commas (e.g. 8-97139-415-0, 8-97139-416-0).'],
                        ['- Barcode is optional. Products are matched by Barcode first, then Product Name. Leave it blank if the item has no barcode.'],
                        ['- Barcode, Part Number and OEM Number columns are formatted as Text so long numbers stay intact (no 4.8E+12).'],
                        ['- Category: pick from the dropdown, which lists the categories already in the system.'],
                        ['  To use a new category, add it on the Categories page first, then download a fresh template.'],
                        ['- Unit examples: pcs, set, pair, liter, box, roll, meter, gallon, kit. Defaults to pcs.'],
                        ['- Cost and Selling Price are per unit.'],
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
