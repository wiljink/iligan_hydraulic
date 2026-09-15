<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Products</h2>
            <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-md hover:bg-orange-600">+ Add Product</a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-5">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, SKU, barcode, part #..." class="rounded-md border-gray-300 shadow-sm text-sm sm:col-span-2">
            <select name="category_id" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All Categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="stock" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All Stock</option>
                <option value="low" @selected(request('stock') === 'low')>Low Stock</option>
                <option value="out" @selected(request('stock') === 'out')>Out of Stock</option>
            </select>
            <div class="sm:col-span-4">
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Filter</button>
                <a href="{{ route('products.index') }}" class="px-4 py-2 text-gray-500 text-sm">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">SKU / Barcode</th>
                        <th class="py-2">Name</th>
                        <th class="py-2">Category</th>
                        <th class="py-2">Brand</th>
                        <th class="py-2 text-right">Stock</th>
                        <th class="py-2 text-right">Price</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-b last:border-0 {{ $product->isLowStock() ? 'bg-red-50' : '' }}">
                            <td class="py-2">
                                <div class="font-mono text-xs">{{ $product->sku }}</div>
                                <div class="font-mono text-xs text-gray-400">{{ $product->barcode }}</div>
                            </td>
                            <td class="py-2">
                                <div class="font-medium">{{ $product->name }}</div>
                                <div class="text-xs text-gray-400">{{ $product->part_number }}</div>
                            </td>
                            <td class="py-2">{{ $product->category?->name }}</td>
                            <td class="py-2">{{ $product->brand?->name }}</td>
                            <td class="py-2 text-right {{ $product->isLowStock() ? 'text-red-600 font-semibold' : '' }}">
                                {{ $product->current_stock }} {{ $product->unit }}
                            </td>
                            <td class="py-2 text-right">{{ money($product->selling_price) }}</td>
                            <td class="py-2 text-right whitespace-nowrap space-x-2">
                                <a href="{{ route('products.barcode', $product) }}" target="_blank" class="text-gray-500 hover:underline">Barcode</a>
                                <a href="{{ route('products.edit', $product) }}" class="text-blue-600 hover:underline">Edit</a>
                                <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline" onsubmit="return confirm('Delete this product?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-gray-400">No products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    </div>
</x-app-layout>
