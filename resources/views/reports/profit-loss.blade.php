<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Profit &amp; Loss</h2>
            <a href="{{ route('reports.profit-loss.export', request()->query()) }}" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-md">Export Excel</a>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-4">Statement ({{ $from->format('M d, Y') }} &ndash; {{ $to->format('M d, Y') }})</h3>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between"><dt>Gross Sales</dt><dd>{{ money($grossSales) }}</dd></div>
                <div class="flex justify-between text-gray-500"><dt>Less: Discounts</dt><dd>-{{ money($discounts) }}</dd></div>
                <div class="flex justify-between font-medium border-t pt-2"><dt>Net Sales</dt><dd>{{ money($netSales) }}</dd></div>
                <div class="flex justify-between text-gray-500"><dt>Less: Cost of Goods Sold</dt><dd>-{{ money($cogs) }}</dd></div>
                <div class="flex justify-between font-semibold border-t pt-2"><dt>Gross Profit</dt><dd>{{ money($grossProfit) }}</dd></div>
                <div class="flex justify-between text-gray-500"><dt>Less: Operating Expenses</dt><dd>-{{ money($expenses) }}</dd></div>
                <div class="flex justify-between text-lg font-bold border-t pt-2 {{ $netProfit < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                    <dt>Net Profit</dt><dd>{{ money($netProfit) }}</dd>
                </div>
            </dl>
            <p class="text-xs text-gray-400 mt-4">Sales tax collected in this period: {{ money($tax) }} (a liability, not counted as profit).</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-4">Expenses by Category</h3>
            <ul class="space-y-2 text-sm">
                @forelse ($expensesByCategory as $row)
                    <li class="flex justify-between border-b pb-2 last:border-0">
                        <span>{{ $row->category }}</span>
                        <span class="font-medium">{{ money($row->total) }}</span>
                    </li>
                @empty
                    <li class="text-gray-400">No expenses recorded in this period.</li>
                @endforelse
            </ul>
            <a href="{{ route('expenses.index') }}" class="text-xs text-orange-600 hover:underline mt-4 inline-block">Manage expenses &rarr;</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-4">Daily Net Sales</h3>
            <ul class="space-y-2 text-sm max-h-72 overflow-y-auto">
                @forelse ($daily as $row)
                    <li class="flex justify-between border-b pb-2 last:border-0">
                        <span>{{ \Illuminate\Support\Carbon::parse($row->date)->format('M d, Y') }}</span>
                        <span class="font-medium">{{ money($row->net_sales) }}</span>
                    </li>
                @empty
                    <li class="text-gray-400">No sales in this period.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
