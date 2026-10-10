<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_ONLY = ['/settings', '/settings/backup', '/users'];

    private const MANAGER_PAGES = [
        '/dashboard', '/pos', '/sales', '/customers',
        '/products', '/purchases', '/categories', '/brands', '/suppliers', '/imports',
        '/reports/sales', '/reports/inventory', '/reports/products',
        '/reports/profit-loss', '/reports/receivables', '/payables', '/expenses',
    ];

    private const CASHIER_PAGES = ['/pos', '/sales', '/customers'];

    public function test_admin_can_access_every_module(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        foreach ([...self::MANAGER_PAGES, ...self::ADMIN_ONLY] as $uri) {
            $this->actingAs($admin)->get($uri)->assertOk();
        }
    }

    public function test_manager_cannot_access_administration(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        foreach (self::MANAGER_PAGES as $uri) {
            $this->actingAs($manager)->get($uri)->assertOk();
        }

        foreach (self::ADMIN_ONLY as $uri) {
            $this->actingAs($manager)->get($uri)->assertForbidden();
        }
    }

    public function test_cashier_is_limited_to_pos_sales_and_customers(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);

        foreach (self::CASHIER_PAGES as $uri) {
            $this->actingAs($cashier)->get($uri)->assertOk();
        }

        $blocked = array_diff([...self::MANAGER_PAGES, ...self::ADMIN_ONLY], self::CASHIER_PAGES, ['/dashboard']);
        foreach ($blocked as $uri) {
            $this->actingAs($cashier)->get($uri)->assertForbidden();
        }

        $this->actingAs($cashier)->get('/dashboard')->assertRedirect(route('pos.index'));
    }

    public function test_menu_only_shows_permitted_items(): void
    {
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER]);
        $this->actingAs($cashier)->get('/pos')
            ->assertSee('Point of Sale')
            ->assertSee('Customers')
            ->assertDontSee('Stock In / Deliveries')
            ->assertDontSee('Profit &amp; Loss', false)
            ->assertDontSee('Backup &amp; Restore', false);

        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $this->actingAs($manager)->get('/pos')
            ->assertSee('Stock In / Deliveries')
            ->assertSee('Accounts Payable')
            ->assertDontSee('Administration')
            ->assertDontSee('Backup &amp; Restore', false);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get('/pos')
            ->assertSee('Administration')
            ->assertSee('Backup &amp; Restore', false);
    }
}
