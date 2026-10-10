<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\AccountPayable;
use App\Models\Expense;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AccountPayableController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->get('status', 'pending');

        $query = AccountPayable::with(['supplier', 'user']);

        if ($statusFilter === 'pending') {
            $query->pending();
        } elseif ($statusFilter === 'overdue') {
            $query->overdue();
        } elseif (in_array($statusFilter, [AccountPayable::STATUS_UNPAID, AccountPayable::STATUS_PARTIALLY_PAID, AccountPayable::STATUS_PAID], true)) {
            $query->where('status', $statusFilter);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        if ($request->filled('q')) {
            $term = '%' . $request->get('q') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('vendor_name', 'like', $term)
                    ->orWhere('bill_number', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        $totalOutstanding = (float) AccountPayable::pending()
            ->selectRaw('COALESCE(SUM(amount - amount_paid), 0) as balance')
            ->value('balance');

        $totalOverdue = (float) AccountPayable::overdue()
            ->selectRaw('COALESCE(SUM(amount - amount_paid), 0) as balance')
            ->value('balance');

        $overdueCount = AccountPayable::overdue()->count();
        $pendingCount = AccountPayable::pending()->count();

        $payables = $query->orderByRaw('CASE WHEN status = "paid" THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();

        return view('reports.payables', compact(
            'payables',
            'suppliers',
            'statusFilter',
            'totalOutstanding',
            'totalOverdue',
            'overdueCount',
            'pendingCount'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'bill_number' => ['required', 'string', 'max:100'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        if (empty($validated['vendor_name']) && ! empty($validated['supplier_id'])) {
            $validated['vendor_name'] = Supplier::find($validated['supplier_id'])?->name ?? 'Supplier';
        }

        if (empty($validated['vendor_name'])) {
            $validated['vendor_name'] = 'General Vendor';
        }

        $validated['user_id'] = $request->user()->id;
        $validated['amount_paid'] = 0;
        $validated['status'] = AccountPayable::STATUS_UNPAID;

        AccountPayable::create($validated);

        return redirect()->route('payables.index')->with('success', "Payable bill {$validated['bill_number']} created successfully.");
    }

    public function recordPayment(Request $request, AccountPayable $accountPayable): RedirectResponse
    {
        $balance = $accountPayable->balance;

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $balance],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
            'record_as_expense' => ['nullable', 'boolean'],
            'expense_category' => ['nullable', 'string', 'max:100'],
        ]);

        $amountToPay = (float) $validated['amount'];
        $newAmountPaid = (float) $accountPayable->amount_paid + $amountToPay;

        $newStatus = $newAmountPaid >= (float) $accountPayable->amount
            ? AccountPayable::STATUS_PAID
            : AccountPayable::STATUS_PARTIALLY_PAID;

        $accountPayable->update([
            'amount_paid' => $newAmountPaid,
            'status' => $newStatus,
            'payment_method' => $validated['payment_method'] ?? $accountPayable->payment_method,
            'reference_number' => $validated['reference_number'] ?? $accountPayable->reference_number,
        ]);

        if ($request->boolean('record_as_expense')) {
            $byCheck = str_contains(strtolower((string) ($validated['payment_method'] ?? '')), 'check');

            Expense::create([
                'voucher_type' => $byCheck ? Expense::TYPE_CHECK : Expense::TYPE_PETTY_CASH,
                'check_number' => $byCheck ? ($validated['reference_number'] ?? null) : null,
                'check_date' => $byCheck ? now()->toDateString() : null,
                'payee' => $accountPayable->vendor_name,
                'expense_date' => now()->toDateString(),
                'category' => $validated['expense_category'] ?? 'Supplies & Parts',
                'description' => "Payment for Bill #{$accountPayable->bill_number} ({$accountPayable->vendor_name})",
                'amount' => $amountToPay,
                'user_id' => $request->user()->id,
            ]);
        }

        return redirect()->route('payables.index')->with('success', "Payment of " . money($amountToPay) . " recorded for {$accountPayable->bill_number}.");
    }

    public function destroy(AccountPayable $accountPayable): RedirectResponse
    {
        $billNo = $accountPayable->bill_number;
        $accountPayable->delete();

        return back()->with('success', "Payable bill {$billNo} deleted.");
    }

    public function export(Request $request): BinaryFileResponse
    {
        $rows = AccountPayable::with('supplier')
            ->orderBy('due_date')
            ->get()
            ->map(fn (AccountPayable $ap) => [
                $ap->bill_number,
                $ap->bill_date?->format('Y-m-d'),
                $ap->due_date?->format('Y-m-d') ?? 'N/A',
                $ap->vendor_name,
                (float) $ap->amount,
                (float) $ap->amount_paid,
                (float) $ap->balance,
                ucfirst(str_replace('_', ' ', $ap->status)),
                $ap->is_overdue ? "{$ap->days_overdue} days overdue" : 'On time',
                $ap->notes ?? '',
            ])->toArray();

        $headings = [
            'Bill #',
            'Bill Date',
            'Due Date',
            'Vendor / Supplier',
            'Amount',
            'Amount Paid',
            'Balance Due',
            'Status',
            'Overdue Status',
            'Notes',
        ];

        return Excel::download(new ReportExport($rows, $headings, 'Accounts Payable'), 'accounts-payable.xlsx');
    }
}
