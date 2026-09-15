<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PosController extends Controller
{
    public function index(): View
    {
        $customers = Customer::orderBy('name')->limit(200)->get();

        return view('pos.index', [
            'customers' => $customers,
            'taxRate' => config('pos.tax_rate'),
            'taxInclusive' => config('pos.tax_inclusive'),
            'currencySymbol' => setting('currency_symbol', config('pos.currency_symbol')),
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $code = trim((string) $request->get('code'));

        $product = Product::where('is_active', true)
            ->where(function ($q) use ($code) {
                $q->where('barcode', $code)->orWhere('sku', $code);
            })
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'unit' => $product->unit,
            'price' => (float) $product->selling_price,
            'stock' => $product->current_stock,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->get('term'));

        $products = Product::where('is_active', true)
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.$term.'%';
                $q->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhere('barcode', 'like', $like)
                        ->orWhere('part_number', 'like', $like);
                });
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'sku', 'barcode', 'unit', 'selling_price', 'current_stock']);

        return response()->json($products);
    }

    public function checkout(Request $request, CheckoutService $checkoutService): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,gcash,bank_transfer,credit,card'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $sale = $checkoutService->checkout($data['items'], [
                'customer_id' => $data['customer_id'] ?? null,
                'discount' => $data['discount'] ?? 0,
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'amount_paid' => $data['amount_paid'] ?? null,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'redirect' => route('sales.show', $sale),
        ]);
    }
}
