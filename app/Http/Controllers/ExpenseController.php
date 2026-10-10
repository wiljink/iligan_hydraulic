<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Expense::query()
            ->when($request->filled('from'), fn ($q) => $q->whereDate('expense_date', '>=', $request->get('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('expense_date', '<=', $request->get('to')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->get('category')))
            ->when($request->filled('voucher_type'), fn ($q) => $q->where('voucher_type', $request->get('voucher_type')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->get('q').'%';
                $q->where(fn ($q) => $q->where('voucher_number', 'like', $term)
                    ->orWhere('payee', 'like', $term)
                    ->orWhere('check_number', 'like', $term)
                    ->orWhere('description', 'like', $term));
            });

        $total = (clone $query)->sum('amount');
        $totalsByType = (clone $query)->selectRaw('voucher_type, SUM(amount) as total')->groupBy('voucher_type')->pluck('total', 'voucher_type');

        $expenses = $query->with('user')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'total' => $total,
            'totalsByType' => $totalsByType,
            'categories' => Expense::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);
        $data['user_id'] = $request->user()->id;

        $expense = Expense::create($data);

        return redirect()->route('expenses.show', $expense)
            ->with('success', "{$expense->voucherTitle()} {$expense->voucher_number} issued.");
    }

    public function show(Expense $expense): View
    {
        $expense->load('user');

        return view('expenses.show', compact('expense'));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        // The voucher type is fixed once issued: it determines the voucher number series.
        $expense->update($this->validated($request, false, $expense->isCheck()));

        return back()->with('success', 'Voucher updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return back()->with('success', 'Voucher deleted.');
    }

    private function validated(Request $request, bool $creating, bool $isCheck = false): array
    {
        $isCheck = $creating ? $request->input('voucher_type') === Expense::TYPE_CHECK : $isCheck;
        $checkRule = $isCheck ? 'required' : 'nullable';

        $data = $request->validate([
            'voucher_type' => [$creating ? 'required' : 'prohibited', Rule::in(array_keys(Expense::VOUCHER_TYPES))],
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:100'],
            'payee' => ['required', 'string', 'max:255'],
            'payee_address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'bank_name' => [$checkRule, 'string', 'max:100'],
            'check_number' => [$checkRule, 'string', 'max:50'],
            'check_date' => [$checkRule, 'date'],
            'approved_by' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $isCheck) {
            $data = array_merge($data, ['bank_name' => null, 'check_number' => null, 'check_date' => null]);
        }

        return $data;
    }
}
