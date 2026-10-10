<?php

namespace App\Services;

use App\Models\CreditMemo;
use App\Models\CreditMemoItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReceivablesService
{
    /**
     * Record a payment against an invoice and issue its Collection Receipt.
     */
    public function recordPayment(Sale $sale, float $amount, string $method = 'cash', ?string $reference = null): Payment
    {
        return DB::transaction(function () use ($sale, $amount, $method, $reference) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->isVoided()) {
                throw new InvalidArgumentException('Cannot collect payment on a voided sale.');
            }

            $amount = round($amount, 2);
            $balance = $sale->balance();

            if ($amount <= 0) {
                throw new InvalidArgumentException('Payment amount must be greater than zero.');
            }
            if ($amount > $balance) {
                throw new InvalidArgumentException('Payment amount exceeds the outstanding balance of '.money($balance).'.');
            }

            $sale->increment('amount_paid', $amount);

            return $this->createPayment($sale, $amount, $method, $reference);
        });
    }

    /**
     * Issue a Collection Receipt for money already counted in the sale's amount_paid
     * (e.g. a down payment taken at checkout).
     */
    public function createPayment(Sale $sale, float $amount, string $method = 'cash', ?string $reference = null): Payment
    {
        return Payment::create([
            'receipt_number' => Payment::generateReceiptNumber(),
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'branch_id' => $sale->branch_id,
            'user_id' => Auth::id(),
            'amount' => round($amount, 2),
            'payment_method' => $method,
            'reference' => $reference,
        ]);
    }

    /**
     * Take back items from an invoice: restock them and issue a Credit Memo.
     * The credit first reduces any outstanding balance; the rest is refunded.
     *
     * @param  array<int, int>  $quantities  sale_item_id => quantity to return
     */
    public function issueCreditMemo(Sale $sale, array $quantities, string $reason, ?string $approvedBy = null): CreditMemo
    {
        return DB::transaction(function () use ($sale, $quantities, $reason, $approvedBy) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->isVoided()) {
                throw new InvalidArgumentException('Cannot return items from a voided sale.');
            }

            $returned = $this->returnedQuantities($sale);
            // Spread the invoice-level discount and tax over the lines so the credit matches what was charged.
            $ratio = (float) $sale->subtotal > 0 ? (float) $sale->total / (float) $sale->subtotal : 0;

            $lines = [];
            foreach ($sale->items as $item) {
                $quantity = (int) ($quantities[$item->id] ?? 0);
                if ($quantity <= 0) {
                    continue;
                }

                $returnable = $item->quantity - ($returned[$item->id] ?? 0);
                if ($quantity > $returnable) {
                    throw new InvalidArgumentException("Only {$returnable} of {$item->product_name} can still be returned.");
                }

                $unitPrice = round((float) $item->subtotal / $item->quantity * $ratio, 2);
                $lines[] = ['item' => $item, 'quantity' => $quantity, 'unit_price' => $unitPrice, 'amount' => round($unitPrice * $quantity, 2)];
            }

            if (empty($lines)) {
                throw new InvalidArgumentException('Select at least one item to return.');
            }

            // Rounding must never credit more than is left on the invoice.
            $total = min(round(array_sum(array_column($lines, 'amount')), 2), $sale->netTotal());
            $applied = min($total, $sale->balance());
            $refunded = round($total - $applied, 2);

            $memo = CreditMemo::create([
                'memo_number' => CreditMemo::generateMemoNumber(),
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'branch_id' => $sale->branch_id,
                'user_id' => Auth::id(),
                'reason' => $reason,
                'approved_by' => $approvedBy,
                'total' => $total,
                'applied_to_balance' => $applied,
                'refunded' => $refunded,
            ]);

            foreach ($lines as $line) {
                $item = $line['item'];
                $memo->items()->create([
                    'sale_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'amount' => $line['amount'],
                ]);

                $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
                $product?->recordMovement(
                    StockMovement::TYPE_RETURN,
                    $line['quantity'],
                    "Return {$memo->memo_number} ({$sale->invoice_number})",
                    CreditMemo::class,
                    $memo->id,
                    $sale->branch_id
                );
            }

            $sale->increment('returned_amount', $total);
            if ($refunded > 0) {
                $sale->increment('refunded_amount', $refunded);
            }

            return $memo->load('items');
        });
    }

    /**
     * @return array<int, int> sale_item_id => quantity already returned
     */
    public function returnedQuantities(Sale $sale): array
    {
        return CreditMemoItem::query()
            ->whereIn('credit_memo_id', $sale->creditMemos()->select('id'))
            ->selectRaw('sale_item_id, SUM(quantity) as qty')
            ->groupBy('sale_item_id')
            ->pluck('qty', 'sale_item_id')
            ->map(fn ($qty) => (int) $qty)
            ->all();
    }
}
