<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Sales Report</h2>
            <a href="{{ route('reports.sales.export', request()->query()) }}" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-md">Export Excel</a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs text-gray-500 block">From</label>
                <input type="date" name="from" value="{{ request('from', $from->toDateString()) }}" class="rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block">To</label>
                <input type="date" name="to" value="{{ request('to', $to->toDateString()) }}" class="rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Apply</button>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4"><p class="text-xs text-gray-500">Transactions</p><p class="text-xl font-bold">{{ $summary->count }}</p></div>
        <div class="bg-white rounded-lg shadow-sm p-4"><p class="text-xs text-gray-500">Total Sales</p><p class="text-xl font-bold">{{ money($summary->total) }}</p></div>
        <div class="bg-white rounded-lg shadow-sm p-4"><p class="text-xs text-gray-500">Tax Collected</p><p class="text-xl font-bold">{{ money($summary->tax) }}</p></div>
        <div class="bg-white rounded-lg shadow-sm p-4"><p class="text-xs text-gray-500">Discounts Given</p><p class="text-xl font-bold">{{ money($summary->discount) }}</p></div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2">Invoice</th>
                    <th class="py-2">Date</th>
                    <th class="py-2">Customer</th>
                    <th class="py-2">Cashier</th>
                    <th class="py-2">Status</th>
                    <th class="py-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr class="border-b last:border-0">
                        <td class="py-2"><a href="{{ route('sales.show', $sale) }}" class="text-orange-600 hover:underline">{{ $sale->invoice_number }}</a></td>
                        <td class="py-2">{{ $sale->created_at->format('M d, Y g:ia') }}</td>
                        <td class="py-2">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                        <td class="py-2">{{ $sale->user?->name }}</td>
                        <td class="py-2">{{ ucfirst($sale->status) }}</td>
                        <td class="py-2 text-right">{{ money($sale->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-gray-400">No sales in this range.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $sales->links() }}</div>
    </div>
</x-app-layout>
