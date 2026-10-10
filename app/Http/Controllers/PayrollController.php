<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\Payroll\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use InvalidArgumentException;

class PayrollController extends Controller
{
    public function index(): View
    {
        [$nextStart, $nextEnd] = PayrollService::periodFor(now());

        return view('hr.payroll.index', [
            'payrolls' => Payroll::withCount('items')->latest('period_start')->paginate(20),
            'nextStart' => $nextStart,
            'nextEnd' => $nextEnd,
        ]);
    }

    public function store(Request $request, PayrollService $service): RedirectResponse
    {
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        try {
            $payroll = $service->create(Carbon::parse($data['period_start']), Carbon::parse($data['period_end']));
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('hr.payroll.show', $payroll)->with('success', 'Payroll computed from attendance. Review it, then finalize.');
    }

    public function show(Payroll $payroll): View
    {
        $payroll->load(['items.employee', 'user']);

        return view('hr.payroll.show', [
            'payroll' => $payroll,
            'items' => $payroll->items->sortBy(fn (PayrollItem $i) => $i->employee->full_name),
        ]);
    }

    public function recompute(Payroll $payroll, PayrollService $service): RedirectResponse
    {
        try {
            $service->compute($payroll);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payroll recomputed from the latest attendance.');
    }

    public function adjust(Request $request, PayrollItem $item, PayrollService $service): RedirectResponse
    {
        $data = $request->validate([
            'adjustments' => ['required', 'numeric'],
            'adjustment_note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $service->adjust($item, (float) $data['adjustments'], $data['adjustment_note'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Adjustment saved for {$item->employee->full_name}.");
    }

    public function finalize(Payroll $payroll, PayrollService $service): RedirectResponse
    {
        try {
            $service->finalize($payroll);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payroll finalized. Cash advance balances have been updated.');
    }

    public function destroy(Payroll $payroll): RedirectResponse
    {
        if ($payroll->isFinalized()) {
            return back()->with('error', 'A finalized payroll cannot be deleted.');
        }

        $payroll->delete();

        return redirect()->route('hr.payroll.index')->with('success', 'Draft payroll deleted.');
    }

    public function payslips(Request $request, Payroll $payroll): View
    {
        $payroll->load('items.employee');
        $items = $payroll->items
            ->when($request->filled('item'), fn ($items) => $items->where('id', (int) $request->get('item')))
            ->sortBy(fn (PayrollItem $i) => $i->employee->full_name);

        return view('hr.payroll.payslips', compact('payroll', 'items'));
    }
}
