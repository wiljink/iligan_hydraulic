<?php

namespace Tests\Feature;

use App\Models\AccountPayable;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_purchases(): void
    {
        $response = $this->get(route('purchases.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_purchases_index_and_create(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $responseIndex = $this->actingAs($user)->get(route('purchases.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Stock In &amp; Deliveries', false);

        $responseCreate = $this->actingAs($user)->get(route('purchases.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('Receive Stock Delivery');
    }

    public function test_user_can_record_stock_delivery_on_credit_and_auto_create_payable(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $supplier = Supplier::create([
            'name' => 'Cebu Hydraulics Supply Corp',
            'phone' => '0917-111-2222',
        ]);

        $category = Category::create(['name' => 'Hoses', 'slug' => 'hoses']);
        $product = Product::create([
            'name' => 'Hydraulic Hose 1/2in R2',
            'barcode' => '2000000001012',
            'unit' => 'meter',
            'cost_price' => 150.00,
            'selling_price' => 220.00,
            'current_stock' => 10,
            'reorder_level' => 5,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
        ]);

        $postData = [
            'supplier_id' => $supplier->id,
            'reference_number' => 'DR-2026-9901',
            'receipt_date' => now()->toDateString(),
            'payment_terms' => 'credit',
            'due_date' => now()->addDays(30)->toDateString(),
            'downpayment' => 0,
            'notes' => 'Received by warehouse team.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 40,
                    'unit_cost' => 155.00,
                    'update_cost_price' => '1',
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('purchases.store'), $postData);

        $purchase = Purchase::first();
        $this->assertNotNull($purchase);
        $response->assertRedirect(route('purchases.show', $purchase));

        // Verify purchase record
        $this->assertEquals('DR-2026-9901', $purchase->reference_number);
        $this->assertEquals('Cebu Hydraulics Supply Corp', $purchase->supplier_name);
        $this->assertEquals(6200.00, $purchase->total_amount);
        $this->assertEquals(Purchase::PAYMENT_UNPAID, $purchase->payment_status);

        // Verify product stock increased
        $product->refresh();
        $this->assertEquals(50, $product->current_stock); // 10 initial + 40 delivered
        $this->assertEquals(155.00, $product->cost_price); // Updated from sync

        // Verify StockMovement logged
        $movement = StockMovement::where('product_id', $product->id)->latest('id')->first();
        $this->assertNotNull($movement);
        $this->assertEquals(StockMovement::TYPE_PURCHASE, $movement->type);
        $this->assertEquals(40, $movement->quantity);
        $this->assertEquals(50, $movement->balance_after);

        // Verify Accounts Payable bill automatically created
        $this->assertDatabaseHas('accounts_payable', [
            'bill_number' => 'DR-2026-9901',
            'vendor_name' => 'Cebu Hydraulics Supply Corp',
            'amount' => 6200.00,
            'amount_paid' => 0,
            'status' => AccountPayable::STATUS_UNPAID,
        ]);
        $this->assertEquals($purchase->account_payable_id, AccountPayable::where('bill_number', 'DR-2026-9901')->value('id'));
    }

    public function test_user_can_record_stock_delivery_paid_immediately_with_expense(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = Category::create(['name' => 'Fittings', 'slug' => 'fittings']);
        $product = Product::create([
            'name' => 'JIC Fitting 1/2 Male',
            'barcode' => '2000000002019',
            'unit' => 'pcs',
            'cost_price' => 45.00,
            'selling_price' => 75.00,
            'current_stock' => 20,
            'reorder_level' => 10,
            'category_id' => $category->id,
        ]);

        $postData = [
            'supplier_name' => 'Walk-in Vendor',
            'reference_number' => 'CASH-REC-101',
            'receipt_date' => now()->toDateString(),
            'payment_terms' => 'paid',
            'payment_method' => 'cash',
            'record_as_expense' => '1',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 100,
                    'unit_cost' => 45.00,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('purchases.store'), $postData);

        $purchase = Purchase::first();
        $response->assertRedirect(route('purchases.show', $purchase));

        $this->assertEquals(Purchase::PAYMENT_PAID, $purchase->payment_status);
        $this->assertEquals(4500.00, $purchase->amount_paid);

        $product->refresh();
        $this->assertEquals(120, $product->current_stock);

        // Expense was logged
        $this->assertDatabaseHas('expenses', [
            'amount' => 4500.00,
            'category' => 'Supplies & Parts',
        ]);

        // No pending accounts payable bill
        $this->assertEquals(0, AccountPayable::count());
    }

    public function test_delivery_detail_renders_successfully(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = Category::create(['name' => 'Pumps', 'slug' => 'pumps']);
        $product = Product::create([
            'name' => 'Hydraulic Gear Pump 20cc',
            'barcode' => '2000000003016',
            'unit' => 'pcs',
            'cost_price' => 3500.00,
            'selling_price' => 5200.00,
            'current_stock' => 5,
            'reorder_level' => 2,
            'category_id' => $category->id,
        ]);

        $purchase = Purchase::create([
            'purchase_number' => 'PO-202610-0099',
            'supplier_name' => 'Komatsu Parts Trading',
            'reference_number' => 'DR-7788',
            'receipt_date' => now()->toDateString(),
            'total_amount' => 17500.00,
            'payment_status' => Purchase::PAYMENT_UNPAID,
            'payment_method' => 'credit',
            'amount_paid' => 0,
            'status' => Purchase::STATUS_RECEIVED,
            'user_id' => $user->id,
        ]);

        $purchase->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 5,
            'unit_cost' => 3500.00,
            'subtotal' => 17500.00,
        ]);

        $response = $this->actingAs($user)->get(route('purchases.show', $purchase));
        $response->assertStatus(200);
        $response->assertSee('PO-202610-0099');
        $response->assertSee('DR-7788');
        $response->assertSee('Komatsu Parts Trading');
        $response->assertSee('Hydraulic Gear Pump 20cc');
    }

    public function test_purchases_can_be_exported_to_excel(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        Purchase::create([
            'purchase_number' => 'PO-EXP-001',
            'supplier_name' => 'Export Supplier',
            'receipt_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'payment_status' => Purchase::PAYMENT_PAID,
            'amount_paid' => 1000.00,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('purchases.export'));
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-disposition') ?? '', 'stock-deliveries.xlsx'));
    }
}
