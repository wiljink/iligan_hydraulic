<?php

namespace Database\Seeders;

use App\Models\AccountPayable;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@iliganhydraulics.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $manager = User::create([
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

        Setting::set('company_name', 'Osmena Hydraulic');
        Setting::set('company_address', 'Door A, S & A building, Osmena St., Cagayan de Oro City');
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
            'Hydraulic Cylinders' => 'Cylinders',
            'Seals & O-Rings' => 'Seals',
            'Filters' => 'Filters',
            'Brake Parts' => 'Brakes',
            'Belts & Bearings' => 'Belts',
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
            $prefix = '200000000'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $checkDigit = \App\Services\BarcodeService::calculateEan13CheckDigit($prefix);

            $product = Product::create([
                'name' => $data['name'],
                'barcode' => $prefix.$checkDigit,
                'part_number' => $data['part'] ?? null,
                'fitment' => $data['fitment'] ?? null,
                'category_id' => isset($categoryModels[$data['category']]) ? $categoryModels[$data['category']]->id : null,
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

        $expenseSamples = [
            ['days' => 25, 'category' => 'Rent', 'description' => 'Shop rent - this month', 'amount' => 15000, 'payee' => 'S & A Building Management', 'check' => 'BDO 0001021'],
            ['days' => 20, 'category' => 'Utilities', 'description' => 'Electricity bill', 'amount' => 3200, 'payee' => 'CEPALCO'],
            ['days' => 18, 'category' => 'Salaries & Wages', 'description' => 'Staff wages - 1st half', 'amount' => 12000, 'payee' => 'Shop staff (payroll)', 'check' => 'BDO 0001022'],
            ['days' => 10, 'category' => 'Fuel & Transportation', 'description' => 'Delivery van fuel', 'amount' => 1800, 'payee' => 'Petron Lapasan'],
            ['days' => 5, 'category' => 'Repairs & Maintenance', 'description' => 'Compressor servicing', 'amount' => 2500, 'payee' => 'RJ Aircon & Compressor Services'],
            ['days' => 2, 'category' => 'Office Supplies', 'description' => 'Receipt paper, ink', 'amount' => 650, 'payee' => 'National Book Store'],
        ];

        foreach ($expenseSamples as $expense) {
            [$bank, $checkNumber] = isset($expense['check']) ? explode(' ', $expense['check']) : [null, null];

            Expense::create([
                'voucher_type' => $checkNumber ? Expense::TYPE_CHECK : Expense::TYPE_PETTY_CASH,
                'payee' => $expense['payee'],
                'bank_name' => $bank,
                'check_number' => $checkNumber,
                'check_date' => $checkNumber ? now()->subDays($expense['days'])->toDateString() : null,
                'expense_date' => now()->subDays($expense['days'])->toDateString(),
                'category' => $expense['category'],
                'description' => $expense['description'],
                'amount' => $expense['amount'],
                'user_id' => $manager->id,
            ]);
        }

        $customer = Customer::create([
            'name' => 'Roberto Dela Cruz',
            'phone' => '0917-555-0101',
            'vehicle_info' => 'Isuzu Elf 2019',
        ]);

        $creditProduct = Product::where('part_number', 'GP-20CC')->first();
        if ($creditProduct) {
            $creditQty = 1;
            $lineTotal = $creditProduct->selling_price * $creditQty;
            $tax = round($lineTotal * 0.12, 2);
            $total = $lineTotal + $tax;

            $sale = Sale::create([
                'invoice_number' => 'INV-'.now()->subDays(12)->format('Ymd').'-0001',
                'customer_id' => $customer->id,
                'user_id' => $admin->id,
                'subtotal' => $lineTotal,
                'discount' => 0,
                'tax' => $tax,
                'total' => $total,
                'amount_paid' => round($total / 2, 2),
                'change_due' => 0,
                'payment_method' => 'credit',
                'status' => 'completed',
                'created_at' => now()->subDays(12),
                'updated_at' => now()->subDays(12),
            ]);

            $sale->items()->create([
                'product_id' => $creditProduct->id,
                'product_name' => $creditProduct->name,
                'product_sku' => $creditProduct->sku,
                'quantity' => $creditQty,
                'unit_price' => $creditProduct->selling_price,
                'cost_price' => $creditProduct->cost_price,
                'discount' => 0,
                'subtotal' => $lineTotal,
            ]);

            $creditProduct->recordMovement(StockMovement::TYPE_SALE, -$creditQty, "Sale {$sale->invoice_number}", Sale::class, $sale->id);
        }

        $supplier1 = $supplierModels->first();
        $supplier2 = $supplierModels->skip(1)->first() ?? $supplier1;
        $supplier3 = $supplierModels->skip(2)->first() ?? $supplier1;

        // Seed sample Accounts Payable (Bills needed to be paid)
        $payable1 = AccountPayable::create([
            'supplier_id' => $supplier1->id,
            'vendor_name' => $supplier1->name,
            'bill_number' => 'BILL-202610-001',
            'bill_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(), // Overdue
            'amount' => 45000.00,
            'amount_paid' => 15000.00,
            'status' => AccountPayable::STATUS_PARTIALLY_PAID,
            'notes' => 'Bulk shipment of hydraulic hoses and JIC fittings.',
            'user_id' => $admin->id,
        ]);

        // Seed corresponding delivery receipt
        $purchase1 = Purchase::create([
            'purchase_number' => 'PO-202610-0001',
            'supplier_id' => $supplier1->id,
            'supplier_name' => $supplier1->name,
            'reference_number' => 'DR-9921',
            'receipt_date' => now()->subDays(20)->toDateString(),
            'total_amount' => 45000.00,
            'payment_status' => Purchase::PAYMENT_PARTIAL,
            'payment_method' => 'cash',
            'amount_paid' => 15000.00,
            'account_payable_id' => $payable1->id,
            'due_date' => now()->subDays(5)->toDateString(),
            'status' => Purchase::STATUS_RECEIVED,
            'notes' => 'Received by warehouse team. Partial cash advance given.',
            'user_id' => $admin->id,
        ]);

        $hoseProduct = Product::where('part_number', 'HH-050')->first();
        if ($hoseProduct) {
            $purchase1->items()->create([
                'product_id' => $hoseProduct->id,
                'product_name' => $hoseProduct->name,
                'product_sku' => $hoseProduct->sku,
                'quantity' => 150,
                'unit_cost' => 150.00,
                'subtotal' => 22500.00,
            ]);
        }

        $jicProduct = Product::where('part_number', 'JIC-050M')->first();
        if ($jicProduct) {
            $purchase1->items()->create([
                'product_id' => $jicProduct->id,
                'product_name' => $jicProduct->name,
                'product_sku' => $jicProduct->sku,
                'quantity' => 500,
                'unit_cost' => 45.00,
                'subtotal' => 22500.00,
            ]);
        }

        AccountPayable::create([
            'supplier_id' => $supplier2->id,
            'vendor_name' => $supplier2->name,
            'bill_number' => 'BILL-202610-002',
            'bill_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(), // Due soon
            'amount' => 28500.00,
            'amount_paid' => 0.00,
            'status' => AccountPayable::STATUS_UNPAID,
            'notes' => 'Replacement filters and brake pad inventory stock.',
            'user_id' => $manager->id,
        ]);

        AccountPayable::create([
            'supplier_id' => $supplier3->id,
            'vendor_name' => $supplier3->name,
            'bill_number' => 'BILL-202610-003',
            'bill_date' => now()->subDays(3)->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'amount' => 62000.00,
            'amount_paid' => 0.00,
            'status' => AccountPayable::STATUS_UNPAID,
            'notes' => 'Hydraulic gear pumps and cylinder components order.',
            'user_id' => $admin->id,
        ]);

        AccountPayable::create([
            'supplier_id' => $supplier1->id,
            'vendor_name' => $supplier1->name,
            'bill_number' => 'BILL-202609-088',
            'bill_date' => now()->subDays(40)->toDateString(),
            'due_date' => now()->subDays(10)->toDateString(),
            'amount' => 35000.00,
            'amount_paid' => 35000.00,
            'status' => AccountPayable::STATUS_PAID,
            'payment_method' => 'bank',
            'reference_number' => 'BDO-TRF-99412',
            'notes' => 'Settled in full via bank transfer.',
            'user_id' => $admin->id,
        ]);
    }
}
