<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    private Branch $main;

    private Branch $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->main = Branch::where('code', 'MAIN')->firstOrFail();
        $this->warehouse = Branch::create(['code' => 'WH1', 'name' => 'Central Warehouse', 'type' => Branch::TYPE_WAREHOUSE]);
    }

    private function product(int $mainQty = 0, int $warehouseQty = 0): Product
    {
        $product = Product::create(['name' => 'Hydraulic Pump', 'unit' => 'pcs', 'cost_price' => 100, 'selling_price' => 150]);

        if ($mainQty) {
            $product->recordMovement(StockMovement::TYPE_OPENING, $mainQty, null, null, null, $this->main->id);
        }
        if ($warehouseQty) {
            $product->recordMovement(StockMovement::TYPE_OPENING, $warehouseQty, null, null, null, $this->warehouse->id);
        }

        return $product->fresh();
    }

    public function test_migration_creates_default_main_branch(): void
    {
        $this->assertTrue($this->main->is_active);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->assertTrue(Branch::current()->is($this->main));
    }

    public function test_stock_is_tracked_per_branch_with_company_total(): void
    {
        $product = $this->product(mainQty: 4, warehouseQty: 10);

        $this->assertSame(4, $product->stockAt($this->main));
        $this->assertSame(10, $product->stockAt($this->warehouse));
        $this->assertSame(14, $product->current_stock);
    }

    public function test_only_admin_can_manage_branches(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($manager)->get('/branches')->assertForbidden();

        $this->actingAs($admin)->get('/branches')->assertOk()->assertSee('Central Warehouse');
        $this->actingAs($admin)->post('/branches', ['code' => 'BR2', 'name' => 'Tibanga Branch', 'type' => 'store'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('branches', ['code' => 'BR2', 'name' => 'Tibanga Branch']);
    }

    public function test_branch_holding_stock_cannot_be_deleted(): void
    {
        $this->product(warehouseQty: 3);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->delete("/branches/{$this->warehouse->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('branches', ['id' => $this->warehouse->id]);
    }

    public function test_manager_can_transfer_stock_between_branches(): void
    {
        $product = $this->product(mainQty: 2, warehouseQty: 10);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($manager)->get('/transfers/create')->assertOk();

        $this->actingAs($manager)->post('/transfers', [
            'from_branch_id' => $this->warehouse->id,
            'to_branch_id' => $this->main->id,
            'transfer_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 6]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame(4, $product->stockAt($this->warehouse));
        $this->assertSame(8, $product->stockAt($this->main));
        $this->assertSame(12, $product->current_stock);
        $this->assertDatabaseHas('stock_transfers', ['from_branch_id' => $this->warehouse->id, 'to_branch_id' => $this->main->id]);
        $this->assertSame(2, StockMovement::where('reference_type', \App\Models\StockTransfer::class)->count());
    }

    public function test_transfer_cannot_exceed_source_stock(): void
    {
        $product = $this->product(mainQty: 1, warehouseQty: 2);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($manager)->post('/transfers', [
            'from_branch_id' => $this->warehouse->id,
            'to_branch_id' => $this->main->id,
            'transfer_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ])->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(2, $product->fresh()->stockAt($this->warehouse));
        $this->assertDatabaseCount('stock_transfers', 0);
    }

    public function test_cashier_cannot_access_transfers(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);

        $this->actingAs($cashier)->get('/transfers')->assertForbidden();
    }

    public function test_checkout_uses_stock_of_the_users_branch_only(): void
    {
        $product = $this->product(mainQty: 1, warehouseQty: 50);
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER, 'branch_id' => $this->main->id]);

        $this->actingAs($cashier)->postJson('/pos/checkout', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
            'amount_paid' => 1000,
        ])->assertStatus(422);

        $this->actingAs($cashier)->postJson('/pos/checkout', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'amount_paid' => 1000,
        ])->assertOk();

        $product->refresh();
        $this->assertSame(0, $product->stockAt($this->main));
        $this->assertSame(50, $product->stockAt($this->warehouse));
        $this->assertSame($this->main->id, Sale::first()->branch_id);
    }

    public function test_void_returns_stock_to_the_sale_branch(): void
    {
        $product = $this->product(mainQty: 5, warehouseQty: 5);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);

        $sale = app(CheckoutService::class)->checkout([['product_id' => $product->id, 'quantity' => 2]], ['branch_id' => $this->warehouse->id]);
        $this->assertSame(3, $product->fresh()->stockAt($this->warehouse));

        // Admin is now working in another branch; the void must still credit the warehouse.
        $this->withSession([Branch::SESSION_KEY => $this->main->id]);
        app(CheckoutService::class)->void($sale, 'Customer returned');

        $this->assertSame(5, $product->fresh()->stockAt($this->warehouse));
        $this->assertSame(5, $product->fresh()->stockAt($this->main));
    }

    public function test_admin_can_switch_branch_but_assigned_user_cannot(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->post('/branch/switch', ['branch_id' => $this->warehouse->id])
            ->assertSessionHas(Branch::SESSION_KEY, $this->warehouse->id);

        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER, 'branch_id' => $this->main->id]);
        $this->actingAs($cashier)->post('/branch/switch', ['branch_id' => $this->warehouse->id])->assertForbidden();
    }

    public function test_assigned_user_cannot_view_another_branchs_sale(): void
    {
        $product = $this->product(warehouseQty: 5);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);
        $sale = app(CheckoutService::class)->checkout([['product_id' => $product->id, 'quantity' => 1]], ['branch_id' => $this->warehouse->id]);

        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER, 'branch_id' => $this->main->id]);
        $this->actingAs($cashier)->get("/sales/{$sale->id}")->assertForbidden();
        $this->actingAs($cashier)->get('/sales')->assertOk()->assertDontSee($sale->invoice_number);
    }

    public function test_stock_delivery_is_received_into_current_branch(): void
    {
        $product = $this->product();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->withSession([Branch::SESSION_KEY => $this->warehouse->id])->post('/purchases', [
            'supplier_name' => 'Apex',
            'reference_number' => 'DR-1',
            'receipt_date' => now()->toDateString(),
            'payment_terms' => 'paid',
            'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 7, 'unit_cost' => 90]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(7, $product->fresh()->stockAt($this->warehouse));
        $this->assertSame(0, $product->fresh()->stockAt($this->main));
    }
}
