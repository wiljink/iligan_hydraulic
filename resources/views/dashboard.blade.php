<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Dashboard</h2>
    </x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-orange-500">
            <p class="text-sm text-gray-500">Today's Sales</p>
            <p class="text-2xl font-bold">{{ money($todayTotal) }}</p>
            <p class="text-xs text-gray-400">{{ $todayCount }} transaction(s)</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-emerald-500">
            <p class="text-sm text-gray-500">This Month</p>
            <p class="text-2xl font-bold">{{ money($monthTotal) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500">Active Products</p>
            <p class="text-2xl font-bold">{{ number_format($totalProducts) }}</p>
            <p class="text-xs text-gray-400">Stock value: {{ money($inventoryValue) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-red-500">
            <p class="text-sm text-gray-500">Low Stock Alerts</p>
            <p class="text-2xl font-bold">{{ number_format($lowStockCount) }}</p>
            <a href="{{ route('products.index', ['stock' => 'low']) }}" class="text-xs text-orange-600 hover:underline">View items</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Recent Sales</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Invoice</th>
                        <th class="py-2">Cashier</th>
                        <th class="py-2">Status</th>
                        <th class="py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentSales as $sale)
                        <tr class="border-b last:border-0">
                            <td class="py-2"><a href="{{ route('sales.show', $sale) }}" class="text-orange-600 hover:underline">{{ $sale->invoice_number }}</a></td>
                            <td class="py-2">{{ $sale->user?->name }}</td>
                            <td class="py-2">
                                <span class="px-2 py-0.5 rounded text-xs {{ $sale->status === 'voided' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">{{ ucfirst($sale->status) }}</span>
                            </td>
                            <td class="py-2 text-right">{{ money($sale->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-gray-400">No sales yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Low Stock</h3>
            <ul class="space-y-2">
                @forelse ($lowStockProducts as $product)
                    <li class="flex justify-between text-sm border-b pb-2 last:border-0">
                        <a href="{{ route('products.edit', $product) }}" class="hover:underline">{{ $product->name }}</a>
                        <span class="text-red-600 font-medium">{{ $product->current_stock }} {{ $product->unit }}</span>
                    </li>
                @empty
                    <li class="text-sm text-gray-400">All stock levels healthy.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 mt-6">
        <h3 class="font-semibold mb-3">Top Movers (last 30 days)</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2">Product</th>
                    <th class="py-2 text-right">Qty Sold</th>
                    <th class="py-2 text-right">Sales</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($topProducts as $row)
                    <tr class="border-b last:border-0">
                        <td class="py-2">{{ $row->product_name }}</td>
                        <td class="py-2 text-right">{{ (int) $row->total_qty }}</td>
                        <td class="py-2 text-right">{{ money($row->total_sales) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-gray-400">No sales data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
