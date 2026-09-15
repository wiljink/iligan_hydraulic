<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Product Inventory Report</h2>
            <a href="{{ route('reports.products.export') }}" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-md">Export Excel</a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
        <a href="{{ route('reports.products') }}" class="text-sm {{ !request('stock') ? 'font-semibold text-orange-600' : 'text-gray-500' }}">All</a>
        <span class="mx-2 text-gray-300">|</span>
        <a href="{{ route('reports.products', ['stock' => 'low']) }}" class="text-sm {{ request('stock') === 'low' ? 'font-semibold text-orange-600' : 'text-gray-500' }}">Low Stock Only</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2">SKU</th>
                    <th class="py-2">Name</th>
                    <th class="py-2">Category</th>
                    <th class="py-2">Brand</th>
                    <th class="py-2 text-right">Stock</th>
                    <th class="py-2 text-right">Cost</th>
                    <th class="py-2 text-right">Price</th>
                    <th class="py-2 text-right">Stock Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr class="border-b last:border-0 {{ $product->isLowStock() ? 'bg-red-50' : '' }}">
                        <td class="py-2 font-mono text-xs">{{ $product->sku }}</td>
                        <td class="py-2">{{ $product->name }}</td>
                        <td class="py-2">{{ $product->category?->name }}</td>
                        <td class="py-2">{{ $product->brand?->name }}</td>
                        <td class="py-2 text-right">{{ $product->current_stock }} {{ $product->unit }}</td>
                        <td class="py-2 text-right">{{ money($product->cost_price) }}</td>
                        <td class="py-2 text-right">{{ money($product->selling_price) }}</td>
                        <td class="py-2 text-right">{{ money($product->current_stock * $product->cost_price) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-6 text-center text-gray-400">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $products->links() }}</div>
    </div>
</x-app-layout>
