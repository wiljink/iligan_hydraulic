<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $sales = Sale::with(['customer', 'user'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->get('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->get('to')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('invoice_number', 'like', '%'.$request->get('q').'%');
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function show(Sale $sale): View
    {
        $sale->load(['items', 'customer', 'user', 'voidedBy']);

        return view('sales.show', compact('sale'));
    }

    public function recordPayment(Request $request, Sale $sale): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $balance = (float) $sale->total - (float) $sale->amount_paid;

        if ($data['amount'] > $balance) {
            return back()->with('error', 'Payment amount exceeds the outstanding balance of '.money($balance).'.');
        }

        $sale->increment('amount_paid', $data['amount']);

        return back()->with('success', 'Payment recorded.');
    }

    public function void(Request $request, Sale $sale, CheckoutService $checkoutService): RedirectResponse
    {
        if (! $request->user()->isManager()) {
            abort(403, 'Only managers and admins can void sales.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $checkoutService->void($sale, $data['reason']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Sale voided and stock returned.');
    }
}
