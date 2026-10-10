<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-xl font-semibold text-gray-800">
                Payroll {{ $payroll->periodLabel() }}
                <span class="ml-2 px-2 py-0.5 rounded text-xs align-middle {{ $payroll->isFinalized() ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ $payroll->isFinalized() ? 'Finalized' : 'Draft' }}</span>
            </h2>
            <div class="flex flex-wrap gap-2 print:hidden">
                <a href="{{ route('hr.payroll.payslips', $payroll) }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md">Print All Payslips</a>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md">Print Register</button>
                @unless ($payroll->isFinalized())
                    <form method="POST" action="{{ route('hr.payroll.recompute', $payroll) }}">
                        @csrf
                        <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Recompute</button>
                    </form>
                    <form method="POST" action="{{ route('hr.payroll.finalize', $payroll) }}" onsubmit="return confirm('Finalize this payroll? It will be locked and cash advance balances will be updated.')">
                        @csrf
                        <button class="px-4 py-2 bg-green-600 text-white text-sm rounded-md">Finalize</button>
                    </form>
                    <form method="POST" action="{{ route('hr.payroll.destroy', $payroll) }}" onsubmit="return confirm('Delete this draft payroll?')">
                        @csrf @method('DELETE')
                        <button class="px-4 py-2 text-red-600 text-sm">Delete</button>
                    </form>
                @endunless
            </div>
        </div>
    </x-slot>

    @if (! $payroll->isFinalized() && $items->sum('incomplete_days') > 0)
        <div class="mb-4 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800 print:hidden">
            {{ $items->where('incomplete_days', '>', 0)->count() }} employee(s) have days with a missing punch (marked below). Those days are paid, but late/undertime can't be fully checked.
            Fix them under Attendance, then click Recompute.
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-5 overflow-x-auto print:shadow-none print:p-0">
        <div class="hidden print:block">
            @include('documents._header', ['title' => 'Payroll Register'])
            <p class="text-center text-sm mb-4">Period: {{ $payroll->periodLabel() }}</p>
        </div>

        <table class="w-full text-xs sm:text-sm" x-data="{ editing: null }">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2 pr-2">Employee</th>
                    <th class="py-2 px-1 text-right">Days</th>
                    <th class="py-2 px-1 text-right">Rate</th>
                    <th class="py-2 px-1 text-right">Basic</th>
                    <th class="py-2 px-1 text-right">Late/UT</th>
                    <th class="py-2 px-1 text-right">Adj.</th>
                    <th class="py-2 px-1 text-right">Gross</th>
                    <th class="py-2 px-1 text-right">SSS</th>
                    <th class="py-2 px-1 text-right">PhilHealth</th>
                    <th class="py-2 px-1 text-right">Pag-IBIG</th>
                    <th class="py-2 px-1 text-right">C.A.</th>
                    <th class="py-2 px-1 text-right">Net Pay</th>
                    <th class="py-2 pl-2 print:hidden"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr class="border-b last:border-0">
                        <td class="py-2 pr-2">
                            <span class="font-medium">{{ $item->employee->full_name }}</span>
                            @if ($item->incomplete_days)
                                <a href="{{ route('hr.attendance.show', [$item->employee, 'from' => $payroll->period_start->toDateString(), 'to' => $payroll->period_end->toDateString()]) }}" class="block text-xs text-amber-600 hover:underline print:hidden">{{ $item->incomplete_days }} day(s) missing punch</a>
                            @endif
                            @if ($item->adjustment_note)
                                <span class="block text-xs text-gray-400">{{ $item->adjustment_note }}</span>
                            @endif
                        </td>
                        <td class="py-2 px-1 text-right">{{ qty($item->days_worked) }}</td>
                        <td class="py-2 px-1 text-right">{{ number_format($item->daily_rate, 2) }}</td>
                        <td class="py-2 px-1 text-right">{{ number_format($item->basic_pay, 2) }}</td>
                        <td class="py-2 px-1 text-right" title="{{ $item->late_minutes }} min late, {{ $item->undertime_minutes }} min undertime">{{ $item->late_undertime_deduction > 0 ? '-'.number_format($item->late_undertime_deduction, 2) : '' }}</td>
                        <td class="py-2 px-1 text-right">{{ (float) $item->adjustments ? number_format($item->adjustments, 2) : '' }}</td>
                        <td class="py-2 px-1 text-right">{{ number_format($item->gross_pay, 2) }}</td>
                        <td class="py-2 px-1 text-right">{{ number_format($item->sss, 2) }}</td>
                        <td class="py-2 px-1 text-right">{{ number_format($item->philhealth, 2) }}</td>
                        <td class="py-2 px-1 text-right">{{ number_format($item->pagibig, 2) }}</td>
                        <td class="py-2 px-1 text-right">{{ (float) $item->cash_advance ? number_format($item->cash_advance, 2) : '' }}</td>
                        <td class="py-2 px-1 text-right font-semibold {{ $item->net_pay < 0 ? 'text-red-600' : '' }}">{{ number_format($item->net_pay, 2) }}</td>
                        <td class="py-2 pl-2 text-right whitespace-nowrap print:hidden">
                            <a href="{{ route('hr.payroll.payslips', [$payroll, 'item' => $item->id]) }}" class="text-gray-700 hover:underline">Payslip</a>
                            @unless ($payroll->isFinalized())
                                <button type="button" @click="editing = editing === {{ $item->id }} ? null : {{ $item->id }}" class="ml-2 text-blue-600 hover:underline">Adjust</button>
                            @endunless
                        </td>
                    </tr>
                    @unless ($payroll->isFinalized())
                        <tr x-show="editing === {{ $item->id }}" x-cloak class="bg-gray-50 print:hidden">
                            <td colspan="13" class="py-2 px-2">
                                <form method="POST" action="{{ route('hr.payroll.items.adjust', $item) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf @method('PUT')
                                    <span class="text-sm text-gray-600">Adjustment for {{ $item->employee->full_name }}:</span>
                                    <input type="number" step="0.01" name="adjustments" value="{{ $item->adjustments }}" class="w-32 rounded-md border-gray-300 shadow-sm text-sm">
                                    <input type="text" name="adjustment_note" value="{{ $item->adjustment_note }}" placeholder="e.g. Overtime 4 hrs, allowance, damaged item (-)" class="flex-1 min-w-48 rounded-md border-gray-300 shadow-sm text-sm">
                                    <button class="px-3 py-1.5 bg-gray-800 text-white text-sm rounded-md">Save</button>
                                    <span class="text-xs text-gray-400 w-full">Positive adds to pay (overtime, allowance); negative deducts (other deductions).</span>
                                </form>
                            </td>
                        </tr>
                    @endunless
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 font-semibold">
                    <td class="py-2 pr-2">Totals ({{ $items->count() }})</td>
                    <td></td><td></td>
                    <td class="py-2 px-1 text-right">{{ number_format($items->sum('basic_pay'), 2) }}</td>
                    <td class="py-2 px-1 text-right">{{ $items->sum('late_undertime_deduction') > 0 ? '-'.number_format($items->sum('late_undertime_deduction'), 2) : '' }}</td>
                    <td class="py-2 px-1 text-right">{{ number_format($items->sum('adjustments'), 2) }}</td>
                    <td class="py-2 px-1 text-right">{{ number_format($items->sum('gross_pay'), 2) }}</td>
                    <td class="py-2 px-1 text-right">{{ number_format($items->sum('sss'), 2) }}</td>
                    <td class="py-2 px-1 text-right">{{ number_format($items->sum('philhealth'), 2) }}</td>
                    <td class="py-2 px-1 text-right">{{ number_format($items->sum('pagibig'), 2) }}</td>
                    <td class="py-2 px-1 text-right">{{ number_format($items->sum('cash_advance'), 2) }}</td>
                    <td class="py-2 px-1 text-right">{{ number_format($items->sum('net_pay'), 2) }}</td>
                    <td class="print:hidden"></td>
                </tr>
            </tfoot>
        </table>

        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div class="rounded-md bg-gray-50 p-3">
                <p class="text-xs text-gray-500">Employer share (remit with employee share)</p>
                <p>SSS: <strong>{{ money($items->sum('sss_employer')) }}</strong></p>
                <p>PhilHealth: <strong>{{ money($items->sum('philhealth_employer')) }}</strong></p>
                <p>Pag-IBIG: <strong>{{ money($items->sum('pagibig_employer')) }}</strong></p>
            </div>
            <div class="rounded-md bg-gray-50 p-3">
                <p class="text-xs text-gray-500">Total remittance this cut-off (EE + ER)</p>
                <p>SSS: <strong>{{ money($items->sum('sss') + $items->sum('sss_employer')) }}</strong></p>
                <p>PhilHealth: <strong>{{ money($items->sum('philhealth') + $items->sum('philhealth_employer')) }}</strong></p>
                <p>Pag-IBIG: <strong>{{ money($items->sum('pagibig') + $items->sum('pagibig_employer')) }}</strong></p>
            </div>
            <div class="rounded-md bg-gray-50 p-3 text-xs text-gray-500">
                Prepared by {{ $payroll->user?->name }} on {{ $payroll->created_at->format('M d, Y') }}.
                @if ($payroll->isFinalized())
                    Finalized {{ $payroll->finalized_at->format('M d, Y g:ia') }}.
                @endif
                <br>Contributions use daily rate &times; {{ setting('payroll_days_per_month') ?: 26 }} days as the monthly basis, half deducted each cut-off.
            </div>
        </div>

        <div class="hidden print:grid grid-cols-3 gap-10 mt-12 text-xs text-gray-500 text-center">
            <div class="border-t border-gray-400 pt-1">Prepared by</div>
            <div class="border-t border-gray-400 pt-1">Checked by</div>
            <div class="border-t border-gray-400 pt-1">Approved by</div>
        </div>
    </div>
</x-app-layout>
