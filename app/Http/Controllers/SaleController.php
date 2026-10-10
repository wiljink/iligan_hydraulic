<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CreditMemo;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\CheckoutService;
use App\Services\ReceivablesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        // Users assigned to a single branch only see that branch's sales.
        $branchId = $request->user()->canSwitchBranch() ? $request->get('branch_id') : $request->user()->branch_id;

        $sales = Sale::with(['branch', 'customer', 'user'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->get('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->get('to')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('invoice_number', 'like', '%'.$request->get('q').'%');
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $branches = $request->user()->canSwitchBranch() ? Branch::orderBy('name')->get() : collect();

        return view('sales.index', compact('sales', 'branches'));
    }

    public function show(Request $request, Sale $sale): View
    {
        $this->authorizeBranch($request, $sale);

        $sale->load(['branch', 'items', 'customer', 'user', 'voidedBy', 'payments.user', 'creditMemos']);

        return view('sales.show', compact('sale'));
    }

    public function recordPayment(Request $request, Sale $sale, ReceivablesService $receivables): RedirectResponse
    {
        $this->authorizeBranch($request, $sale);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'in:'.implode(',', array_keys(Payment::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $payment = $receivables->recordPayment($sale, (float) $data['amount'], $data['payment_method'] ?? 'cash', $data['reference'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('payments.show', $payment)->with('success', "Payment recorded. Collection Receipt {$payment->receipt_number} issued.");
    }

    public function showPayment(Request $request, Payment $payment): View
    {
        $this->authorizeBranch($request, $payment->sale);

        $payment->load(['sale', 'customer', 'user']);

        return view('payments.show', compact('payment'));
    }

    public function createReturn(Request $request, Sale $sale, ReceivablesService $receivables): View|RedirectResponse
    {
        $this->authorizeBranch($request, $sale);

        if ($sale->isVoided()) {
            return redirect()->route('sales.show', $sale)->with('error', 'Cannot return items from a voided sale.');
        }

        $sale->load(['items', 'customer']);
        $returned = $receivables->returnedQuantities($sale);

        return view('sales.return', compact('sale', 'returned'));
    }

    public function storeReturn(Request $request, Sale $sale, ReceivablesService $receivables): RedirectResponse
    {
        $this->authorizeBranch($request, $sale);

        $data = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
            'approved_by' => ['required', 'string', 'max:255'],
        ]);

        try {
            $memo = $receivables->issueCreditMemo($sale, array_map('intval', array_filter($data['quantities'])), $data['reason'], $data['approved_by']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('credit-memos.show', $memo)->with('success', "Items returned to stock. Credit Memo {$memo->memo_number} issued.");
    }

    public function showCreditMemo(Request $request, CreditMemo $creditMemo): View
    {
        $this->authorizeBranch($request, $creditMemo->sale);

        $creditMemo->load(['sale', 'customer', 'user', 'items']);

        return view('credit-memos.show', compact('creditMemo'));
    }

    public function void(Request $request, Sale $sale, CheckoutService $checkoutService): RedirectResponse
    {
        if (! $request->user()->can('sales.void')) {
            abort(403, 'You do not have permission to void sales.');
        }

        $this->authorizeBranch($request, $sale);

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

    /**
     * Users assigned to a single branch may only work with that branch's sales.
     */
    private function authorizeBranch(Request $request, Sale $sale): void
    {
        $user = $request->user();

        abort_if(! $user->canSwitchBranch() && $sale->branch_id !== $user->branch_id, 403, 'This sale belongs to another branch.');
    }
}
