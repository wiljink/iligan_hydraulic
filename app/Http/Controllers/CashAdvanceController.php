<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashAdvanceController extends Controller
{
    public function index(Request $request): View
    {
        $advances = CashAdvance::with(['employee', 'deductions.payrollItem.payroll'])
            ->when($request->get('status', 'open') === 'open', fn ($q) => $q->where('balance', '>', 0))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->get('employee_id')))
            ->latest('advance_date')
            ->paginate(25)
            ->withQueryString();

        return view('hr.cash-advances.index', [
            'advances' => $advances,
            'employees' => Employee::active()->orderBy('last_name')->get(),
            'outstanding' => CashAdvance::sum('balance'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'advance_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1'],
            'deduction_per_payroll' => ['required', 'numeric', 'min:1', 'lte:amount'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        CashAdvance::create($data + ['balance' => $data['amount'], 'user_id' => $request->user()->id]);

        return back()->with('success', 'Cash advance recorded. It will be deducted starting with the next payroll.');
    }

    public function destroy(CashAdvance $cashAdvance): RedirectResponse
    {
        if ($cashAdvance->deductions()->exists()) {
            return back()->with('error', 'This cash advance has payroll deductions and can no longer be deleted.');
        }

        $cashAdvance->delete();

        return back()->with('success', 'Cash advance deleted.');
    }
}
