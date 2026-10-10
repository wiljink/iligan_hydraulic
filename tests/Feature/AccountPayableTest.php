<?php

namespace Tests\Feature;

use App\Models\AccountPayable;
use App\Models\Expense;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountPayableTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_payables(): void
    {
        $response = $this->get(route('payables.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_payables_index(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $supplier = Supplier::create(['name' => 'Apex Hydraulic Supplies']);

        AccountPayable::create([
            'supplier_id' => $supplier->id,
            'vendor_name' => $supplier->name,
            'bill_number' => 'BILL-2026-001',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'amount' => 15000,
            'amount_paid' => 0,
            'status' => AccountPayable::STATUS_UNPAID,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('payables.index'));

        $response->assertStatus(200);
        $response->assertSee('Accounts Payable');
        $response->assertSee('BILL-2026-001');
        $response->assertSee('Apex Hydraulic Supplies');
    }

    public function test_user_can_create_a_payable_bill(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $supplier = Supplier::create(['name' => 'Metro Seal Parts']);

        $response = $this->actingAs($user)->post(route('payables.store'), [
            'supplier_id' => $supplier->id,
            'bill_number' => 'BILL-NEW-999',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'amount' => 25000,
            'notes' => 'O-rings and gasket bulk purchase',
        ]);

        $response->assertRedirect(route('payables.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('accounts_payable', [
            'bill_number' => 'BILL-NEW-999',
            'vendor_name' => 'Metro Seal Parts',
            'amount' => 25000,
            'amount_paid' => 0,
            'status' => AccountPayable::STATUS_UNPAID,
        ]);
    }

    public function test_user_can_record_partial_payment(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $payable = AccountPayable::create([
            'vendor_name' => 'Direct Parts Co',
            'bill_number' => 'BILL-PARTIAL-101',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'amount' => 10000,
            'amount_paid' => 0,
            'status' => AccountPayable::STATUS_UNPAID,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('payables.record-payment', $payable), [
            'amount' => 4000,
            'payment_method' => 'gcash',
            'reference_number' => 'GCASH-REF-1234',
        ]);

        $response->assertRedirect(route('payables.index'));
        $response->assertSessionHas('success');

        $payable->refresh();
        $this->assertEquals(4000, $payable->amount_paid);
        $this->assertEquals(AccountPayable::STATUS_PARTIALLY_PAID, $payable->status);
        $this->assertEquals('gcash', $payable->payment_method);
        $this->assertEquals(6000, $payable->balance);
    }

    public function test_user_can_settle_bill_in_full_and_record_expense(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $payable = AccountPayable::create([
            'vendor_name' => 'Fast Bearings Inc',
            'bill_number' => 'BILL-FULL-202',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'amount' => 8000,
            'amount_paid' => 2000,
            'status' => AccountPayable::STATUS_PARTIALLY_PAID,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('payables.record-payment', $payable), [
            'amount' => 6000,
            'payment_method' => 'bank',
            'reference_number' => 'BDO-987654',
            'record_as_expense' => '1',
            'expense_category' => 'Supplies & Parts',
        ]);

        $response->assertRedirect(route('payables.index'));
        $payable->refresh();
        $this->assertEquals(8000, $payable->amount_paid);
        $this->assertEquals(AccountPayable::STATUS_PAID, $payable->status);
        $this->assertEquals(0, $payable->balance);

        $this->assertDatabaseHas('expenses', [
            'category' => 'Supplies & Parts',
            'amount' => 6000,
        ]);
    }

    public function test_dashboard_displays_payables_and_urgent_bills_to_pay(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Overdue payable
        AccountPayable::create([
            'vendor_name' => 'Overdue Hydraulic Supplier',
            'bill_number' => 'BILL-OVERDUE-01',
            'bill_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
            'amount' => 30000,
            'amount_paid' => 10000,
            'status' => AccountPayable::STATUS_PARTIALLY_PAID,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Accounts Payable (A/P)');
        $response->assertSee('Accounts Needed to be Paid');
        $response->assertSee('BILL-OVERDUE-01');
        $response->assertSee('Overdue Hydraulic Supplier');
        $response->assertSee('Overdue');
    }

    public function test_payables_can_be_exported_to_excel(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        AccountPayable::create([
            'vendor_name' => 'Export Vendor Test',
            'bill_number' => 'BILL-EXP-01',
            'bill_date' => now()->toDateString(),
            'amount' => 5000,
            'amount_paid' => 0,
            'status' => AccountPayable::STATUS_UNPAID,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('payables.export'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-disposition') ?? '', 'accounts-payable.xlsx'));
    }
}
