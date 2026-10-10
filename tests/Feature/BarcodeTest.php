<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\BarcodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
    }

    public function test_barcode_service_validates_ean13_correctly(): void
    {
        // 2000000000015 has check digit 5 -> valid
        $this->assertTrue(BarcodeService::isValidEan13('2000000000015'));

        // 2000000000001 has check digit 1 (real is 8) -> invalid EAN-13
        $this->assertFalse(BarcodeService::isValidEan13('2000000000001'));

        // Non-13 digit strings
        $this->assertFalse(BarcodeService::isValidEan13('IH-CFPBRM'));
        $this->assertFalse(BarcodeService::isValidEan13('12345'));
    }

    public function test_barcode_service_generates_unique_valid_ean13(): void
    {
        $barcode = BarcodeService::generateUniqueEan13();
        $this->assertEquals(13, strlen($barcode));
        $this->assertTrue(BarcodeService::isValidEan13($barcode));
    }

    public function test_product_barcode_endpoint_returns_png_for_arbitrary_13_digits(): void
    {
        $product = Product::create([
            'name' => 'Hydraulic Hose',
            'barcode' => '2000000000001',
            'unit' => 'pcs',
            'cost_price' => 100,
            'selling_price' => 150,
            'reorder_level' => 5,
        ]);

        $response = $this->get(route('products.barcode', $product));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_product_barcode_endpoint_returns_svg_format(): void
    {
        $product = Product::create([
            'name' => 'Hydraulic Fitting',
            'barcode' => '2000000000015',
            'unit' => 'pcs',
            'cost_price' => 50,
            'selling_price' => 75,
            'reorder_level' => 10,
        ]);

        $response = $this->get(route('products.barcode', $product).'?format=svg');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_product_barcode_endpoint_falls_back_to_sku(): void
    {
        $product = Product::create([
            'name' => 'Oil Filter',
            'barcode' => null,
            'sku' => 'IH-TEST01',
            'unit' => 'pcs',
            'cost_price' => 80,
            'selling_price' => 120,
            'reorder_level' => 5,
        ]);

        $response = $this->get(route('products.barcode', $product));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_single_barcode_print_page_renders_successfully(): void
    {
        $product = Product::create([
            'name' => 'Test Hydraulic Hose',
            'barcode' => '2000000000001',
            'unit' => 'meter',
            'cost_price' => 150,
            'selling_price' => 250,
            'reorder_level' => 10,
        ]);

        $response = $this->get(route('products.barcode.print', $product));

        $response->assertStatus(200);
        $response->assertSee('Print Barcode Labels');
        $response->assertSee('Test Hydraulic Hose');
        $response->assertSee('2000000000001');
    }

    public function test_bulk_barcode_print_page_renders_successfully(): void
    {
        Product::create([
            'name' => 'Bulk Product 1',
            'barcode' => '2000000000001',
            'unit' => 'pcs',
            'cost_price' => 100,
            'selling_price' => 150,
            'reorder_level' => 5,
        ]);

        $response = $this->get(route('products.barcodes.print'));

        $response->assertStatus(200);
        $response->assertSee('Batch Barcode Printing');
    }

    public function test_generate_barcode_api_endpoint(): void
    {
        $response = $this->getJson(route('products.generate-barcode'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['barcode']);
        $barcode = $response->json('barcode');
        $this->assertTrue(BarcodeService::isValidEan13($barcode));
    }

    public function test_product_template_export_preserves_text_part_and_oem_numbers(): void
    {
        $export = new \App\Exports\ProductTemplateExport(true);
        $productsSheet = $export->sheets()['Products'];

        $this->assertEquals(
            ['Quantity', 'Unit', 'Product Name', 'Barcode', 'Category', 'Part Number', 'OEM Number', 'Cost', 'Selling Price'],
            $productsSheet->headings()
        );

        $formats = $productsSheet->columnFormats();
        $this->assertEquals('@', $formats['D']); // barcode
        $this->assertEquals('@', $formats['F']); // part number
        $this->assertEquals('@', $formats['G']); // oem number

        $worksheet = (new \PhpOffice\PhpSpreadsheet\Spreadsheet)->getActiveSheet();
        $cell = $worksheet->getCell('D2');
        $productsSheet->bindValue($cell, '2000000000018');
        $this->assertEquals(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING, $cell->getDataType());
        $this->assertEquals('2000000000018', $cell->getValue());
    }

    public function test_template_category_column_is_a_dropdown_of_system_categories(): void
    {
        \App\Models\Category::create(['name' => 'Oil Filter', 'slug' => 'oil-filter']);
        \App\Models\Category::create(['name' => 'Air Filter', 'slug' => 'air-filter']);

        $raw = \Maatwebsite\Excel\Facades\Excel::raw(new \App\Exports\ProductTemplateExport, \Maatwebsite\Excel\Excel::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        file_put_contents($path, $raw);

        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $products = $book->getSheetByName('Products');
        $list = $book->getSheetByName('Categories');

        $this->assertSame(['Air Filter', 'Oil Filter'], [$list->getCell('A1')->getValue(), $list->getCell('A2')->getValue()]);
        $this->assertSame(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN, $list->getSheetState());

        $validation = $products->getCell('E2')->getDataValidation();
        $this->assertSame(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST, $validation->getType());
        $this->assertSame("'Categories'!\$A\$1:\$A\$2", $validation->getFormula1());
        $this->assertSame('Air Filter', $products->getCell('E2')->getValue()); // sample row uses a real category

        // The filled-in template still imports cleanly (the hidden list sheet is ignored).
        $import = new \App\Imports\ProductsImport;
        \Maatwebsite\Excel\Facades\Excel::import($import, $path);
        $this->assertSame(1, $import->created);
        $this->assertSame([], $import->errors);
        $this->assertSame('Air Filter', Product::where('name', 'Hydraulic Hose 1/2in')->first()->category->name);

        unlink($path);
    }

    public function test_product_import_with_new_template_columns(): void
    {
        $existing = Product::create([
            'name' => 'Existing Seal Kit',
            'barcode' => '2000000000025',
            'unit' => 'set',
            'cost_price' => 100,
            'selling_price' => 150,
            'reorder_level' => 7,
        ]);

        $oem = '8-97139-415-0, 8-97139-416-0, 1-13240-216-0, 5-87830-102-0';

        $import = new \App\Imports\ProductsImport;
        $import->collection(collect([
            collect(['quantity' => 12, 'unit' => 'pcs', 'product_name' => 'New Gear Pump', 'category' => 'Pumps',
                'part_number' => 'GP-30', 'oem_number' => $oem, 'cost' => 4000, 'selling_price' => 6000]),
            collect(['quantity' => 99, 'unit' => 'set', 'product_name' => 'Existing Seal Kit', 'category' => null,
                'part_number' => 'SK-1', 'oem_number' => null, 'cost' => 110, 'selling_price' => 175]),
        ]));

        $this->assertSame([], $import->errors);
        $this->assertSame(1, $import->created);
        $this->assertSame(1, $import->updated);

        $new = Product::where('name', 'New Gear Pump')->first();
        $this->assertSame($oem, $new->oem_number);
        $this->assertSame('GP-30', $new->part_number);
        $this->assertEquals(12, $new->current_stock);
        $this->assertEquals(4000, $new->cost_price);
        $this->assertSame('Pumps', $new->category->name);

        $existing->refresh();
        $this->assertEquals(175, $existing->selling_price);
        $this->assertEquals(0, $existing->current_stock); // quantity only applies to new products
        $this->assertSame('2000000000025', $existing->barcode); // not in template, left untouched
        $this->assertEquals(7, $existing->reorder_level);
    }

    public function test_product_import_sanitizes_scientific_notation_barcode(): void
    {
        $import = new \App\Imports\ProductsImport;
        $import->collection(collect([
            [
                'name' => 'Scientific Notation Product',
                'barcode' => '2.000000000018E+12',
                'cost_price' => '100',
                'selling_price' => '150',
                'unit' => 'pcs',
            ],
            [
                'name' => 'Float Zero Product',
                'barcode' => '2000000000015.0',
                'cost_price' => '200',
                'selling_price' => '250',
                'unit' => 'pcs',
            ],
        ]));

        $this->assertDatabaseHas('products', [
            'name' => 'Scientific Notation Product',
            'barcode' => '2000000000018',
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Float Zero Product',
            'barcode' => '2000000000015',
        ]);
    }
}
