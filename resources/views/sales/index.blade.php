<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Sales History</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-5">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-6 gap-3 mb-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Invoice #" class="rounded-md border-gray-300 shadow-sm text-sm">
            <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border-gray-300 shadow-sm text-sm">
            <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border-gray-300 shadow-sm text-sm">
            <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All Status</option>
                <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                <option value="voided" @selected(request('status') === 'voided')>Voided</option>
            </select>
            @if ($branches->isNotEmpty())
                <select name="branch_id" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">All Branches</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            @endif
            <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Filter</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Invoice</th>
                        <th class="py-2">Date</th>
                        <th class="py-2">Customer</th>
                        <th class="py-2">Branch</th>
                        <th class="py-2">Cashier</th>
                        <th class="py-2">Payment</th>
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
                            <td class="py-2">{{ $sale->branch?->name }}</td>
                            <td class="py-2">{{ $sale->user?->name }}</td>
                            <td class="py-2 capitalize">{{ str_replace('_', ' ', $sale->payment_method) }}</td>
                            <td class="py-2">
                                <span class="px-2 py-0.5 rounded text-xs {{ $sale->status === 'voided' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">{{ ucfirst($sale->status) }}</span>
                            </td>
                            <td class="py-2 text-right">{{ money($sale->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-gray-400">No sales found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $sales->links() }}</div>
    </div>
</x-app-layout>
