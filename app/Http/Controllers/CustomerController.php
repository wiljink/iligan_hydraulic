<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->get('q').'%';
                $q->where('name', 'like', $term)->orWhere('phone', 'like', $term);
            }))
            ->withSum(['sales as balance' => fn ($q) => $q->where('status', 'completed')], DB::raw(Sale::BALANCE_SQL))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function show(Customer $customer): View
    {
        $sales = $customer->sales()->with('creditMemos')->latest()->get();
        $payments = $customer->payments()->with('sale')->latest()->get();
        $creditMemos = $customer->creditMemos()->with('sale')->latest()->get();

        $completed = $sales->reject(fn (Sale $s) => $s->isVoided());

        $summary = [
            'purchases' => $completed->sum(fn (Sale $s) => (float) $s->total),
            'returns' => $completed->sum(fn (Sale $s) => (float) $s->returned_amount),
            'paid' => $completed->sum(fn (Sale $s) => min((float) $s->amount_paid, (float) $s->total) - (float) $s->refunded_amount),
            'balance' => $completed->sum(fn (Sale $s) => $s->balance()),
            'open_invoices' => $completed->filter(fn (Sale $s) => $s->balance() > 0)->count(),
        ];

        return view('customers.show', compact('customer', 'sales', 'payments', 'creditMemos', 'summary'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Customer::create($data);

        return back()->with('success', 'Customer added.');
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validated($request);

        $customer->update($data);

        return back()->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->sales()->exists()) {
            return back()->with('error', 'Cannot delete a customer that has sales history.');
        }

        $customer->delete();

        return back()->with('success', 'Customer deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'tin_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
