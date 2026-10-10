<?php

namespace Tests\Feature;

use App\Models\CreditMemo;
use App\Models\Customer;
use App\Models\Payment;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoCustomersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoCustomersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_customers_get_a_consistent_account_history(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoCustomersSeeder::class);
        $this->seed(DemoCustomersSeeder::class); // re-running must not duplicate

        $haulers = Customer::where('name', 'Mindanao Haulers Corporation')->firstOrFail();
        $this->assertSame(2, $haulers->sales()->count());
        $this->assertSame(1, Customer::where('name', 'Mindanao Haulers Corporation')->count());
        $this->assertGreaterThan(0, $haulers->sales->sum(fn ($s) => $s->balance()));

        // Settled accounts end at zero.
        $settled = Customer::where('name', 'Northern Mindanao Construction Supply')->firstOrFail();
        $this->assertEquals(0, $settled->sales->sum(fn ($s) => $s->balance()));

        // Documents carry the date they happened, not the seeding date.
        $this->assertStringStartsWith('CR-'.now()->subDays(30)->format('Ymd'), $haulers->payments()->oldest('id')->value('receipt_number'));
        $this->assertGreaterThanOrEqual(8, Payment::count());
        $this->assertSame(3, CreditMemo::count());
    }
}
