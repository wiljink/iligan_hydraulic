<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseVoucherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
    }

    public function test_petty_cash_voucher_is_issued_and_printable(): void
    {
        $response = $this->post(route('expenses.store'), [
            'voucher_type' => 'petty_cash',
            'expense_date' => now()->toDateString(),
            'payee' => 'Petron Lapasan',
            'category' => 'Fuel & Transportation',
            'description' => 'Delivery van fuel',
            'amount' => 1250.50,
            'approved_by' => 'Store Manager',
        ]);

        $expense = Expense::firstOrFail();
        $response->assertRedirect(route('expenses.show', $expense));
        $this->assertStringStartsWith('PCV-'.now()->format('Ymd'), $expense->voucher_number);
        $this->assertNull($expense->check_number);

        $this->get(route('expenses.show', $expense))->assertOk()
            ->assertSee('Petty Cash Voucher')
            ->assertSee('Petron Lapasan')
            ->assertSee('One Thousand Two Hundred Fifty Pesos and 50/100');
    }

    public function test_check_voucher_requires_check_details(): void
    {
        $base = [
            'voucher_type' => 'check',
            'expense_date' => now()->toDateString(),
            'payee' => 'S & A Building Management',
            'category' => 'Rent',
            'amount' => 15000,
        ];

        $this->post(route('expenses.store'), $base)->assertSessionHasErrors(['bank_name', 'check_number', 'check_date']);
        $this->assertSame(0, Expense::count());

        $this->post(route('expenses.store'), $base + ['bank_name' => 'BDO', 'check_number' => '0001021', 'check_date' => now()->toDateString()]);

        $expense = Expense::firstOrFail();
        $this->assertStringStartsWith('CV-', $expense->voucher_number);
        $this->get(route('expenses.show', $expense))->assertOk()->assertSee('Check Voucher')->assertSee('0001021');
    }

    public function test_voucher_numbers_run_in_separate_series(): void
    {
        $pcv1 = Expense::create(['expense_date' => now(), 'category' => 'Other', 'payee' => 'A', 'amount' => 10]);
        $cv1 = Expense::create(['voucher_type' => 'check', 'expense_date' => now(), 'category' => 'Other', 'payee' => 'B', 'amount' => 10]);
        $pcv2 = Expense::create(['expense_date' => now(), 'category' => 'Other', 'payee' => 'C', 'amount' => 10]);

        $this->assertStringEndsWith('-0001', $pcv1->voucher_number);
        $this->assertStringEndsWith('-0001', $cv1->voucher_number);
        $this->assertStringEndsWith('-0002', $pcv2->voucher_number);
    }

    public function test_index_filters_by_voucher_type(): void
    {
        Expense::create(['expense_date' => now(), 'category' => 'Other', 'payee' => 'Cash Payee', 'amount' => 10]);
        Expense::create(['voucher_type' => 'check', 'expense_date' => now(), 'category' => 'Other', 'payee' => 'Check Payee', 'amount' => 10, 'check_number' => '555']);

        $this->get(route('expenses.index', ['voucher_type' => 'check']))->assertOk()
            ->assertSee('Check Payee')
            ->assertDontSee('Cash Payee');
    }
}
