<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Payslips &mdash; {{ $payroll->periodLabel() }}</h2>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('hr.payroll.show', $payroll) }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md">Back to Payroll</a>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Print</button>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6 print:space-y-0">
        @foreach ($items as $item)
            <div class="bg-white rounded-lg shadow-sm p-6 print:shadow-none print:rounded-none print:border-b print:border-dashed print:border-gray-400 print:py-4 break-inside-avoid">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="font-extrabold uppercase">{{ setting('company_name', config('app.name')) }}</p>
                        <p class="text-xs text-gray-500">{{ setting('company_address') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold tracking-widest">PAYSLIP</p>
                        <p class="text-xs text-gray-500">{{ $payroll->periodLabel() }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-x-6 text-sm border-y py-2 mb-3">
                    <p><span class="text-gray-500">Employee:</span> <strong>{{ $item->employee->full_name }}</strong></p>
                    <p><span class="text-gray-500">Employee No.:</span> {{ $item->employee->employee_no }}</p>
                    <p><span class="text-gray-500">Position:</span> {{ $item->employee->position ?: '—' }}</p>
                    <p><span class="text-gray-500">Daily Rate:</span> {{ money($item->daily_rate) }}</p>
                </div>

                <div class="grid grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="font-semibold border-b mb-1">Earnings</p>
                        <div class="flex justify-between"><span>Basic ({{ qty($item->days_worked) }} days)</span><span>{{ money($item->basic_pay) }}</span></div>
                        @if ($item->late_undertime_deduction > 0)
                            <div class="flex justify-between"><span>Late/Undertime ({{ $item->late_minutes + $item->undertime_minutes }} min)</span><span>-{{ money($item->late_undertime_deduction) }}</span></div>
                        @endif
                        @if ((float) $item->adjustments)
                            <div class="flex justify-between"><span>Adjustment{{ $item->adjustment_note ? ': '.$item->adjustment_note : '' }}</span><span>{{ money($item->adjustments) }}</span></div>
                        @endif
                        <div class="flex justify-between font-semibold border-t mt-1 pt-1"><span>Gross Pay</span><span>{{ money($item->gross_pay) }}</span></div>
                    </div>
                    <div>
                        <p class="font-semibold border-b mb-1">Deductions</p>
                        <div class="flex justify-between"><span>SSS</span><span>{{ money($item->sss) }}</span></div>
                        <div class="flex justify-between"><span>PhilHealth</span><span>{{ money($item->philhealth) }}</span></div>
                        <div class="flex justify-between"><span>Pag-IBIG</span><span>{{ money($item->pagibig) }}</span></div>
                        @if ((float) $item->cash_advance)
                            <div class="flex justify-between"><span>Cash Advance</span><span>{{ money($item->cash_advance) }}</span></div>
                        @endif
                        <div class="flex justify-between font-semibold border-t mt-1 pt-1"><span>Total Deductions</span><span>{{ money($item->total_deductions) }}</span></div>
                    </div>
                </div>

                <div class="flex justify-between items-end mt-4">
                    <div class="text-lg font-bold">NET PAY: {{ money($item->net_pay) }}</div>
                    <div class="w-56 text-center text-xs text-gray-500">
                        <div class="border-t border-gray-400 pt-1 mt-8">Received by (employee signature)</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
