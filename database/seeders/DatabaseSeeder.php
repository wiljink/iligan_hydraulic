<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@iliganhydraulics.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        User::create([
            'name' => 'Store Manager',
            'email' => 'manager@iliganhydraulics.test',
            'password' => 'password',
            'role' => User::ROLE_MANAGER,
        ]);

        User::create([
            'name' => 'Cashier',
            'email' => 'cashier@iliganhydraulics.test',
            'password' => 'password',
            'role' => User::ROLE_CASHIER,
        ]);

        Setting::set('company_name', 'Iligan Hydraulics & Auto Parts');
        Setting::set('company_address', 'National Highway, Iligan City, Lanao del Norte');
        Setting::set('company_phone', '(063) 000-0000');
        Setting::set('company_tin', '');
        Setting::set('currency_symbol', '₱');
        Setting::set('tax_rate', '0.12');
        Setting::set('tax_inclusive', '0');
        Setting::set('low_stock_default', '5');
        Setting::set('receipt_footer', 'Thank you for your business! No returns after 7 days without receipt.');

        $categories = [
            'Hydraulic Hoses' => 'Hoses',
            'Hydraulic Fittings' => 'Fittings',
            'Hydraulic Pumps' => 'Pumps',
            'Hydraulic Cylinders' => 'Cylinders',
            'Seals & O-Rings' => 'Seals',
            'Filters' => 'Filters',
            'Engine Oil & Lubricants' => 'Lubricants',
            'Brake Parts' => 'Brakes',
            'Belts & Bearings' => 'Belts',
            'Electrical Parts' => 'Electrical',
        ];

        $categoryModels = [];
        foreach ($categories as $name => $slug) {
            $categoryModels[$name] = Category::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($slug)]);
        }

        $brandNames = ['Parker', 'Eaton', 'Bosch', 'Denso', 'NOK', 'Komatsu', 'Toyota', 'Isuzu', 'Generic/Universal', 'SKF'];
        $brandModels = collect($brandNames)->mapWithKeys(fn ($name) => [$name => Brand::create(['name' => $name])]);

        $supplierModels = collect([
            ['name' => 'Cebu Hydraulics Supply Corp', 'contact_person' => 'Juan Reyes', 'phone' => '0917-000-0001'],
            ['name' => 'Manila Auto Parts Trading', 'contact_person' => 'Maria Santos', 'phone' => '0917-000-0002'],
            ['name' => 'Iligan Industrial Supply', 'contact_person' => 'Pedro Cruz', 'phone' => '0917-000-0003'],
        ])->map(fn ($data) => Supplier::create($data));

        $products = [
            ['name' => 'Hydraulic Hose 1/2" (per meter)', 'category' => 'Hydraulic Hoses', 'brand' => 'Parker', 'unit' => 'meter', 'cost' => 150, 'price' => 220, 'stock' => 80, 'reorder' => 15, 'part' => 'HH-050'],
            ['name' => 'Hydraulic Hose 3/8" (per meter)', 'category' => 'Hydraulic Hoses', 'brand' => 'Parker', 'unit' => 'meter', 'cost' => 120, 'price' => 180, 'stock' => 60, 'reorder' => 15, 'part' => 'HH-038'],
            ['name' => 'JIC Fitting 1/2" Male', 'category' => 'Hydraulic Fittings', 'brand' => 'Eaton', 'unit' => 'pcs', 'cost' => 45, 'price' => 75, 'stock' => 150, 'reorder' => 30, 'part' => 'JIC-050M'],
            ['name' => 'NPT Fitting 3/8" Female', 'category' => 'Hydraulic Fittings', 'brand' => 'Eaton', 'unit' => 'pcs', 'cost' => 40, 'price' => 68, 'stock' => 120, 'reorder' => 30, 'part' => 'NPT-038F'],
            ['name' => 'Hydraulic Gear Pump 20cc', 'category' => 'Hydraulic Pumps', 'brand' => 'Komatsu', 'unit' => 'pcs', 'cost' => 3500, 'price' => 5200, 'stock' => 6, 'reorder' => 3, 'part' => 'GP-20CC'],
            ['name' => 'Hydraulic Cylinder 2"x10"', 'category' => 'Hydraulic Cylinders', 'brand' => 'Generic/Universal', 'unit' => 'pcs', 'cost' => 4200, 'price' => 6500, 'stock' => 4, 'reorder' => 2, 'part' => 'CYL-0210'],
            ['name' => 'Oil Seal 30x45x7', 'category' => 'Seals & O-Rings', 'brand' => 'NOK', 'unit' => 'pcs', 'cost' => 35, 'price' => 60, 'stock' => 200, 'reorder' => 40, 'part' => 'OS-304507'],
            ['name' => 'O-Ring Kit Assorted', 'category' => 'Seals & O-Rings', 'brand' => 'NOK', 'unit' => 'kit', 'cost' => 280, 'price' => 450, 'stock' => 25, 'reorder' => 8, 'part' => 'ORK-ASST'],
            ['name' => 'Hydraulic Oil Filter', 'category' => 'Filters', 'brand' => 'Bosch', 'unit' => 'pcs', 'cost' => 180, 'price' => 320, 'stock' => 40, 'reorder' => 10, 'part' => 'HOF-100'],
            ['name' => 'Fuel Filter - Isuzu Elf', 'category' => 'Filters', 'brand' => 'Isuzu', 'unit' => 'pcs', 'cost' => 220, 'price' => 380, 'stock' => 35, 'reorder' => 10, 'fitment' => 'Isuzu Elf 2015-2023'],
            ['name' => 'Hydraulic Oil AW46 (per liter)', 'category' => 'Engine Oil & Lubricants', 'brand' => 'Generic/Universal', 'unit' => 'liter', 'cost' => 85, 'price' => 140, 'stock' => 300, 'reorder' => 50, 'part' => 'AW46'],
            ['name' => 'Engine Oil 15W-40 (per liter)', 'category' => 'Engine Oil & Lubricants', 'brand' => 'Generic/Universal', 'unit' => 'liter', 'cost' => 90, 'price' => 150, 'stock' => 250, 'reorder' => 50, 'part' => '15W40'],
            ['name' => 'Brake Pad Set - Toyota Hilux', 'category' => 'Brake Parts', 'brand' => 'Toyota', 'unit' => 'set', 'cost' => 850, 'price' => 1350, 'stock' => 18, 'reorder' => 5, 'fitment' => 'Toyota Hilux 2015-2020'],
            ['name' => 'Brake Shoe - Isuzu Elf', 'category' => 'Brake Parts', 'brand' => 'Isuzu', 'unit' => 'set', 'cost' => 620, 'price' => 980, 'stock' => 15, 'reorder' => 5, 'fitment' => 'Isuzu Elf 2015-2023'],
            ['name' => 'Alternator Belt', 'category' => 'Belts & Bearings', 'brand' => 'Bosch', 'unit' => 'pcs', 'cost' => 320, 'price' => 520, 'stock' => 22, 'reorder' => 8, 'part' => 'ALT-BELT'],
            ['name' => 'Wheel Bearing Front', 'category' => 'Belts & Bearings', 'brand' => 'SKF', 'unit' => 'pcs', 'cost' => 480, 'price' => 750, 'stock' => 16, 'reorder' => 6, 'part' => 'WB-FRONT'],
            ['name' => 'Glow Plug - Isuzu', 'category' => 'Electrical Parts', 'brand' => 'Denso', 'unit' => 'pcs', 'cost' => 180, 'price' => 300, 'stock' => 30, 'reorder' => 10, 'fitment' => 'Isuzu Elf, D-Max'],
            ['name' => '12V Battery 70Ah', 'category' => 'Electrical Parts', 'brand' => 'Generic/Universal', 'unit' => 'pcs', 'cost' => 3200, 'price' => 4800, 'stock' => 8, 'reorder' => 3, 'part' => 'BAT-70AH'],
            ['name' => 'Quick Coupler 1/2"', 'category' => 'Hydraulic Fittings', 'brand' => 'Parker', 'unit' => 'pcs', 'cost' => 220, 'price' => 380, 'stock' => 3, 'reorder' => 10, 'part' => 'QC-050'],
            ['name' => 'Hydraulic Pressure Gauge', 'category' => 'Hydraulic Pumps', 'brand' => 'Generic/Universal', 'unit' => 'pcs', 'cost' => 450, 'price' => 750, 'stock' => 2, 'reorder' => 5, 'part' => 'PG-STD'],
        ];

        foreach ($products as $index => $data) {
            $product = Product::create([
                'name' => $data['name'],
                'barcode' => '200000000'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'part_number' => $data['part'] ?? null,
                'fitment' => $data['fitment'] ?? null,
                'category_id' => $categoryModels[$data['category']]->id,
                'brand_id' => $brandModels[$data['brand']]->id,
                'supplier_id' => $supplierModels->random()->id,
                'unit' => $data['unit'],
                'cost_price' => $data['cost'],
                'selling_price' => $data['price'],
                'current_stock' => 0,
                'reorder_level' => $data['reorder'],
            ]);

            $product->recordMovement(StockMovement::TYPE_OPENING, $data['stock'], 'Initial catalog seed');
        }
    }
}
