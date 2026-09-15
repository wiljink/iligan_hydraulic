<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Inventory Movements</h2>
            <a href="{{ route('reports.inventory.export') }}" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-md">Export Excel</a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-5 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs text-gray-500 block">Product</label>
                <select name="product_id" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">All Products</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 block">Type</label>
                <select name="type" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">All Types</option>
                    @foreach (['purchase', 'sale', 'adjustment', 'return', 'wastage', 'opening'] as $type)
                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
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
                    <th class="py-2">Date</th>
                    <th class="py-2">Product</th>
                    <th class="py-2">Type</th>
                    <th class="py-2 text-right">Qty Change</th>
                    <th class="py-2 text-right">Balance</th>
                    <th class="py-2">User</th>
                    <th class="py-2">Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $movement)
                    <tr class="border-b last:border-0">
                        <td class="py-2">{{ $movement->created_at->format('M d, Y g:ia') }}</td>
                        <td class="py-2">{{ $movement->product?->name }}</td>
                        <td class="py-2 capitalize">{{ $movement->type }}</td>
                        <td class="py-2 text-right {{ $movement->quantity < 0 ? 'text-red-600' : 'text-green-600' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td>
                        <td class="py-2 text-right">{{ $movement->balance_after }}</td>
                        <td class="py-2">{{ $movement->user?->name }}</td>
                        <td class="py-2 text-gray-500">{{ $movement->note }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-400">No stock movements found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $movements->links() }}</div>
    </div>
</x-app-layout>
