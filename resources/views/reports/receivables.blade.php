<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Accounts Receivable</h2>
            <a href="{{ route('reports.receivables.export') }}" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-md">Export Excel</a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 sm:col-span-1">
            <p class="text-xs text-gray-500">Total Outstanding</p>
            <p class="text-2xl font-bold text-red-600">{{ money($totalOutstanding) }}</p>
        </div>
        <form method="GET" class="sm:col-span-2 bg-white rounded-lg shadow-sm p-4 flex items-end gap-3">
            <div class="flex-1">
                <label class="text-xs text-gray-500 block">Customer</label>
                <select name="customer_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">All Customers</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2">Invoice</th>
                    <th class="py-2">Date</th>
                    <th class="py-2">Customer</th>
                    <th class="py-2 text-right">Total</th>
                    <th class="py-2 text-right">Paid</th>
                    <th class="py-2 text-right">Balance</th>
                    <th class="py-2 text-right">Days</th>
                    <th class="py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody x-data="{ paying: null }">
                @forelse ($sales as $sale)
                    @php $balance = $sale->total - $sale->amount_paid; @endphp
                    <tr class="border-b last:border-0" x-show="paying !== {{ $sale->id }}">
                        <td class="py-2"><a href="{{ route('sales.show', $sale) }}" class="text-orange-600 hover:underline">{{ $sale->invoice_number }}</a></td>
                        <td class="py-2">{{ $sale->created_at->format('M d, Y') }}</td>
                        <td class="py-2">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td class="py-2 text-right">{{ money($sale->total) }}</td>
                        <td class="py-2 text-right">{{ money($sale->amount_paid) }}</td>
                        <td class="py-2 text-right font-medium text-red-600">{{ money($balance) }}</td>
                        @php $daysOutstanding = (int) $sale->created_at->diffInDays(); @endphp
                        <td class="py-2 text-right {{ $daysOutstanding > 30 ? 'text-red-600 font-semibold' : '' }}">{{ $daysOutstanding }}</td>
                        <td class="py-2 text-right">
                            <button type="button" @click="paying = {{ $sale->id }}" class="text-blue-600 hover:underline">Record Payment</button>
                        </td>
                    </tr>
                    <tr x-show="paying === {{ $sale->id }}" x-cloak>
                        <td colspan="8" class="py-2">
                            <form method="POST" action="{{ route('sales.record-payment', $sale) }}" class="flex items-center gap-2">
                                @csrf
                                <span class="text-sm text-gray-500">{{ $sale->invoice_number }} &mdash; balance {{ money($balance) }}:</span>
                                <input type="number" step="0.01" min="0.01" max="{{ $balance }}" name="amount" value="{{ $balance }}" class="w-32 rounded-md border-gray-300 shadow-sm text-sm">
                                <button type="submit" class="text-green-600 text-sm hover:underline">Save Payment</button>
                                <button type="button" @click="paying = null" class="text-gray-500 text-sm hover:underline">Cancel</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-6 text-center text-gray-400">No outstanding credit sales.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $sales->links() }}</div>
    </div>
</x-app-layout>
