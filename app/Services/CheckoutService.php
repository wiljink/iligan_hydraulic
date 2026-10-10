<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CheckoutService
{
    /**
     * @param  array<int, array{product_id:int, quantity:int, unit_price?:float, discount?:float}>  $items
     */
    public function checkout(array $items, array $options = []): Sale
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Cannot checkout an empty cart.');
        }

        $branchId = $options['branch_id'] ?? Branch::current()?->id;

        return DB::transaction(function () use ($items, $options, $branchId) {
            $subtotal = 0;
            $lineData = [];

            foreach ($items as $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();
                $quantity = (int) $item['quantity'];

                if ($quantity < 1) {
                    throw new InvalidArgumentException("Invalid quantity for {$product->name}.");
                }

                $available = $branchId ? $product->stockAt($branchId) : $product->current_stock;
                if ($available < $quantity) {
                    throw new InvalidArgumentException("Insufficient stock for {$product->name} at this branch. Available: {$available}.");
                }

                $unitPrice = (float) ($item['unit_price'] ?? $product->selling_price);
                $discount = (float) ($item['discount'] ?? 0);
                $lineSubtotal = ($unitPrice * $quantity) - $discount;
                $subtotal += $lineSubtotal;

                $lineData[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_price' => (float) $product->cost_price,
                    'discount' => $discount,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $discountTotal = (float) ($options['discount'] ?? 0);
            $taxRate = (float) ($options['tax_rate'] ?? config('pos.tax_rate'));
            $taxInclusive = (bool) ($options['tax_inclusive'] ?? config('pos.tax_inclusive'));

            $taxableAmount = max($subtotal - $discountTotal, 0);
            $tax = $taxInclusive ? 0 : round($taxableAmount * $taxRate, 2);
            $total = round($taxableAmount + $tax, 2);

            $amountPaid = (float) ($options['amount_paid'] ?? $total);
            if ($amountPaid < $total && ($options['payment_method'] ?? 'cash') !== 'credit') {
                throw new InvalidArgumentException('Amount paid is less than the total due.');
            }

            $sale = Sale::create([
                'invoice_number' => Sale::generateInvoiceNumber($options['payment_method'] ?? 'cash'),
                'branch_id' => $branchId,
                'customer_id' => $options['customer_id'] ?? null,
                'user_id' => Auth::id(),
                'subtotal' => $subtotal,
                'discount' => $discountTotal,
                'tax' => $tax,
                'total' => $total,
                'amount_paid' => $amountPaid,
                'change_due' => max($amountPaid - $total, 0),
                'payment_method' => $options['payment_method'] ?? 'cash',
                'payment_reference' => $options['payment_reference'] ?? null,
                'status' => 'completed',
            ]);

            foreach ($lineData as $line) {
                $sale->items()->create([
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'product_sku' => $line['product']->sku,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'cost_price' => $line['cost_price'],
                    'discount' => $line['discount'],
                    'subtotal' => $line['subtotal'],
                ]);

                $line['product']->recordMovement(
                    StockMovement::TYPE_SALE,
                    -$line['quantity'],
                    "Sale {$sale->invoice_number}",
                    Sale::class,
                    $sale->id,
                    $branchId
                );
            }

            // A down payment on a charge invoice gets its own Collection Receipt.
            if ($sale->isCharge() && $amountPaid > 0) {
                app(ReceivablesService::class)->createPayment($sale, min($amountPaid, $total), 'cash', $sale->payment_reference);
            }

            return $sale->load('items');
        });
    }

    public function void(Sale $sale, string $reason): Sale
    {
        if ($sale->isVoided()) {
            throw new InvalidArgumentException('Sale is already voided.');
        }
        if ($sale->creditMemos()->exists()) {
            // Returned items are already back in stock; voiding would count them twice.
            throw new InvalidArgumentException('This sale has returns (credit memos) and can no longer be voided.');
        }

        return DB::transaction(function () use ($sale, $reason) {
            foreach ($sale->items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
                if ($product) {
                    $product->recordMovement(
                        StockMovement::TYPE_RETURN,
                        $item->quantity,
                        "Void {$sale->invoice_number}",
                        Sale::class,
                        $sale->id,
                        $sale->branch_id
                    );
                }
            }

            $sale->update([
                'status' => 'voided',
                'voided_by' => Auth::id(),
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            return $sale;
        });
    }
}
