<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Cash Advances</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-5 h-fit">
            <h3 class="font-semibold mb-3">New Cash Advance</h3>
            <form method="POST" action="{{ route('hr.cash-advances.store') }}" class="space-y-3">
                @csrf
                <div>
                    <x-input-label for="employee_id" value="Employee *" />
                    <select id="employee_id" name="employee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                        <option value="">Select...</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="advance_date" value="Date *" />
                    <x-text-input id="advance_date" type="date" name="advance_date" class="mt-1 block w-full" value="{{ old('advance_date', now()->toDateString()) }}" required />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <x-input-label for="amount" value="Amount *" />
                        <x-text-input id="amount" type="number" step="0.01" min="1" name="amount" class="mt-1 block w-full" value="{{ old('amount') }}" required />
                    </div>
                    <div>
                        <x-input-label for="deduction_per_payroll" value="Deduct per payroll *" />
                        <x-text-input id="deduction_per_payroll" type="number" step="0.01" min="1" name="deduction_per_payroll" class="mt-1 block w-full" value="{{ old('deduction_per_payroll') }}" required />
                    </div>
                </div>
                <div>
                    <x-input-label for="notes" value="Notes" />
                    <x-text-input id="notes" name="notes" class="mt-1 block w-full" value="{{ old('notes') }}" />
                </div>
                <x-primary-button>Record Cash Advance</x-primary-button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
            <form method="GET" class="flex flex-wrap gap-3 mb-4">
                <select name="employee_id" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">All employees</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>{{ $employee->full_name }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="open" @selected(request('status', 'open') === 'open')>With balance</option>
                    <option value="all" @selected(request('status') === 'all')>All</option>
                </select>
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Filter</button>
                <span class="ml-auto text-sm text-gray-500 self-center">Total outstanding: <strong class="text-gray-800">{{ money($outstanding) }}</strong></span>
            </form>

            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Date</th>
                        <th class="py-2">Employee</th>
                        <th class="py-2 text-right">Amount</th>
                        <th class="py-2 text-right">Per Payroll</th>
                        <th class="py-2 text-right">Balance</th>
                        <th class="py-2">Deducted In</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($advances as $advance)
                        <tr class="border-b last:border-0 align-top">
                            <td class="py-2 whitespace-nowrap">{{ $advance->advance_date->format('M d, Y') }}</td>
                            <td class="py-2">{{ $advance->employee->full_name }}<span class="block text-xs text-gray-400">{{ $advance->notes }}</span></td>
                            <td class="py-2 text-right">{{ money($advance->amount) }}</td>
                            <td class="py-2 text-right">{{ money($advance->deduction_per_payroll) }}</td>
                            <td class="py-2 text-right font-medium {{ $advance->balance > 0 ? 'text-red-600' : 'text-green-700' }}">{{ money($advance->balance) }}</td>
                            <td class="py-2 text-xs text-gray-500">
                                @foreach ($advance->deductions as $deduction)
                                    <a href="{{ route('hr.payroll.show', $deduction->payrollItem->payroll) }}" class="block hover:underline">{{ $deduction->payrollItem->payroll->periodLabel() }}: {{ money($deduction->amount) }}</a>
                                @endforeach
                            </td>
                            <td class="py-2 text-right">
                                @if ($advance->deductions->isEmpty())
                                    <form method="POST" action="{{ route('hr.cash-advances.destroy', $advance) }}" onsubmit="return confirm('Delete this cash advance?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-gray-400">No cash advances.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $advances->links() }}</div>
        </div>
    </div>
</x-app-layout>
