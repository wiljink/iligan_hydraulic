<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_successfully_for_authenticated_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $category = Category::create(['name' => 'Hydraulics', 'slug' => 'hydraulics']);
        $product = Product::create([
            'name' => 'Hydraulic Hose 1/2in',
            'barcode' => '2000000000015',
            'unit' => 'pcs',
            'cost_price' => 100,
            'selling_price' => 150,
            'current_stock' => 10,
            'reorder_level' => 5,
            'category_id' => $category->id,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-TEST-001',
            'user_id' => $user->id,
            'subtotal' => 150,
            'discount' => 0,
            'tax' => 18,
            'total' => 168,
            'amount_paid' => 168,
            'change_due' => 0,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => 'HH-050',
            'quantity' => 1,
            'unit_price' => 150,
            'cost_price' => 100,
            'subtotal' => 150,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard &amp; Analytics', false);
        $response->assertSee('Revenue &amp; Gross Profit Trend', false);
        $response->assertSee('Payment Breakdown');
        $response->assertSee('Top Categories');
        $response->assertSee('INV-TEST-001');
    }

    public function test_dashboard_supports_custom_day_ranges(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response7 = $this->actingAs($user)->get(route('dashboard', ['days' => 7]));
        $response7->assertStatus(200);
        $response7->assertSee('over the last 7 days');

        $response30 = $this->actingAs($user)->get(route('dashboard', ['days' => 30]));
        $response30->assertStatus(200);
        $response30->assertSee('over the last 30 days');
    }
}
