<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CreditMemo;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivablesTest extends TestCase
{
    use RefreshDatabase;

    private Branch $main;

    private Customer $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->main = Branch::where('code', 'MAIN')->firstOrFail();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->customer = Customer::create(['name' => 'Roberto Dela Cruz', 'tin_number' => '123-456-789-000', 'address' => 'Cagayan de Oro']);
        $this->product = Product::create(['name' => 'VIC Oil Filter C-226', 'unit' => 'pcs', 'cost_price' => 200, 'selling_price' => 250]);
        $this->product->recordMovement(StockMovement::TYPE_OPENING, 10, null, null, null, $this->main->id);
    }

    /** 4 x 250 = 1,000 with no tax, on credit unless told otherwise. */
    private function sale(string $method = 'credit', float $amountPaid = 0): Sale
    {
        return app(CheckoutService::class)->checkout(
            [['product_id' => $this->product->id, 'quantity' => 4]],
            ['branch_id' => $this->main->id, 'customer_id' => $this->customer->id, 'payment_method' => $method, 'amount_paid' => $amountPaid, 'tax_rate' => 0]
        );
    }

    public function test_credit_sales_get_charge_invoices_and_cash_sales_get_sales_invoices(): void
    {
        $charge = $this->sale('credit');
        $cash = $this->sale('cash', 1000);

        $this->assertStringStartsWith('CI-', $charge->invoice_number);
        $this->assertStringStartsWith('SI-', $cash->invoice_number);

        $this->get(route('sales.show', $charge))->assertOk()->assertSee('Charge Invoice')->assertSee('123-456-789-000');
        $this->get(route('sales.show', $cash))->assertOk()->assertSee('Sales Invoice');
    }

    public function test_recording_a_payment_issues_a_collection_receipt(): void
    {
        $sale = $this->sale();

        $response = $this->post(route('sales.record-payment', $sale), ['amount' => 400, 'payment_method' => 'gcash', 'reference' => 'GC-778']);

        $payment = Payment::firstOrFail();
        $response->assertRedirect(route('payments.show', $payment));
        $this->assertStringStartsWith('CR-', $payment->receipt_number);
        $this->assertSame($this->customer->id, $payment->customer_id);
        $this->assertEquals(600, $sale->fresh()->balance());

        $this->get(route('payments.show', $payment))->assertOk()
            ->assertSee('Collection Receipt')
            ->assertSee($sale->invoice_number)
            ->assertSee('Four Hundred Pesos Only');
    }

    public function test_payment_cannot_exceed_balance(): void
    {
        $sale = $this->sale();

        $this->post(route('sales.record-payment', $sale), ['amount' => 1500])->assertSessionHas('error');
        $this->assertSame(0, Payment::count());
    }

    public function test_return_on_charge_invoice_restocks_and_reduces_balance(): void
    {
        $sale = $this->sale();
        $item = $sale->items->first();

        $response = $this->post(route('sales.return.store', $sale), ['quantities' => [$item->id => 1], 'approved_by' => 'Store Manager', 'reason' => 'Wrong part']);

        $memo = CreditMemo::firstOrFail();
        $response->assertRedirect(route('credit-memos.show', $memo));
        $this->assertStringStartsWith('CM-', $memo->memo_number);
        $this->assertEquals(250, $memo->total);
        $this->assertEquals(250, $memo->applied_to_balance);
        $this->assertEquals(0, $memo->refunded);
        $this->assertEquals(750, $sale->fresh()->balance());
        $this->assertSame(7, $this->product->fresh()->stockAt($this->main));

        $this->get(route('credit-memos.show', $memo))->assertOk()
            ->assertSee('Credit Memo')
            ->assertSee('Wrong part')
            ->assertSee('Prepared by')
            ->assertSee('Approved by')
            ->assertSee('Store Manager')
            ->assertSee('Customer signature');
    }

    public function test_return_requires_an_approver(): void
    {
        $sale = $this->sale();

        $this->post(route('sales.return.store', $sale), ['quantities' => [$sale->items->first()->id => 1], 'reason' => 'Wrong part'])
            ->assertSessionHasErrors('approved_by');
        $this->assertSame(0, CreditMemo::count());
    }

    public function test_return_on_paid_cash_sale_is_refunded(): void
    {
        $sale = $this->sale('cash', 1000);
        $item = $sale->items->first();

        $this->post(route('sales.return.store', $sale), ['quantities' => [$item->id => 2], 'approved_by' => 'Store Manager', 'reason' => 'Defective']);

        $memo = CreditMemo::firstOrFail();
        $this->assertEquals(500, $memo->refunded);
        $this->assertEquals(0, $memo->applied_to_balance);
        $this->assertEquals(0, $sale->fresh()->balance());
        $this->assertEquals(500, $sale->fresh()->netTotal());
    }

    public function test_cannot_return_more_than_was_sold(): void
    {
        $sale = $this->sale();
        $item = $sale->items->first();

        $this->post(route('sales.return.store', $sale), ['quantities' => [$item->id => 3], 'approved_by' => 'Store Manager', 'reason' => 'x']);
        $this->post(route('sales.return.store', $sale), ['quantities' => [$item->id => 2], 'approved_by' => 'Store Manager', 'reason' => 'y'])->assertSessionHas('error');

        $this->assertSame(1, CreditMemo::count());
        $this->assertSame(9, $this->product->fresh()->stockAt($this->main));
    }

    public function test_sale_with_returns_cannot_be_voided(): void
    {
        $sale = $this->sale();
        $this->post(route('sales.return.store', $sale), ['quantities' => [$sale->items->first()->id => 1], 'approved_by' => 'Store Manager', 'reason' => 'x']);

        $this->post(route('sales.void', $sale), ['reason' => 'oops'])->assertSessionHas('error');
        $this->assertFalse($sale->fresh()->isVoided());
    }

    public function test_customer_page_shows_details_payments_and_balance(): void
    {
        $sale = $this->sale();
        $this->post(route('sales.record-payment', $sale), ['amount' => 300]);
        $this->post(route('sales.return.store', $sale), ['quantities' => [$sale->items->first()->id => 1], 'approved_by' => 'Store Manager', 'reason' => 'x']);

        $this->get(route('customers.show', $this->customer))->assertOk()
            ->assertSee('123-456-789-000')
            ->assertSee(Payment::first()->receipt_number)
            ->assertSee(CreditMemo::first()->memo_number)
            ->assertSee(money(450)); // 1,000 - 300 paid - 250 returned

        $this->get(route('customers.index'))->assertOk()->assertSee(money(450));
        $this->get(route('reports.receivables'))->assertOk()->assertSee(money(450));
    }

    public function test_cashier_cannot_issue_credit_memos(): void
    {
        $sale = $this->sale();
        $cashier = User::factory()->create(['role' => User::ROLE_CASHIER, 'branch_id' => $this->main->id]);

        $this->actingAs($cashier)->get(route('sales.return.create', $sale))->assertForbidden();
    }
}
