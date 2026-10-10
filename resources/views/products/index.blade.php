<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Products</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchases.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-md shadow-xs transition">
                    <span>🚚</span>
                    <span>Stock In</span>
                </a>
                <a href="{{ route('products.barcodes.print') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700 shadow-sm">
                    🖨️ Print Barcodes
                </a>
                <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 shadow-sm">+ Add Product</a>
            </div>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-5">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, SKU, barcode, part #, OEM #..." class="rounded-md border-gray-300 shadow-sm text-sm sm:col-span-2">
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

        <div x-data="{
            selected: [],
            allIds: {{ Js::from($products->pluck('id')) }},
            toggleAll() {
                if (this.selected.length === this.allIds.length) {
                    this.selected = [];
                } else {
                    this.selected = [...this.allIds];
                }
            },
            printSelected() {
                if (this.selected.length > 0) {
                    window.location.href = '{{ route('products.barcodes.print') }}?ids=' + this.selected.join(',');
                }
            }
        }">
            <!-- Batch Selection Action Bar -->
            <div x-show="selected.length > 0" x-cloak class="mb-4 bg-orange-50 border border-orange-200 rounded-md p-3 flex flex-wrap items-center justify-between gap-3 text-sm">
                <span class="font-medium text-orange-900">
                    <span x-text="selected.length"></span> product(s) selected
                </span>
                <div class="flex items-center gap-2">
                    <button type="button" @click="printSelected()" class="px-3.5 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded font-medium text-xs shadow-sm flex items-center gap-1.5">
                        <span>🖨️</span> Print Barcodes for Selected
                    </button>
                    <button type="button" @click="selected = []" class="px-2.5 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded text-xs">
                        Clear
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2 px-2 w-8 text-center">
                                <input type="checkbox" @change="toggleAll()" :checked="selected.length === allIds.length && allIds.length > 0" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            </th>
                            <th class="py-2">SKU / Barcode</th>
                            <th class="py-2">Name</th>
                            <th class="py-2">OEM No.</th>
                            <th class="py-2">Category</th>
                            <th class="py-2">Brand</th>
                            <th class="py-2 text-right">Stock @ {{ $branch?->name }}</th>
                            <th class="py-2 text-right">All Branches</th>
                            <th class="py-2 text-right">Price</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr class="border-b last:border-0 {{ $product->isLowStock() ? 'bg-red-50' : '' }}" :class="selected.includes({{ $product->id }}) ? 'bg-orange-50/50' : ''">
                                <td class="py-2 px-2 text-center">
                                    <input type="checkbox" value="{{ $product->id }}" x-model.number="selected" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                </td>
                                <td class="py-2">
                                    <div class="font-mono text-xs">{{ $product->sku }}</div>
                                    <div class="font-mono text-xs text-gray-400">{{ $product->barcode ?: 'No Barcode' }}</div>
                                </td>
                                <td class="py-2">
                                    <div class="font-medium">{{ $product->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $product->part_number }}</div>
                                </td>
                                <td class="py-2 text-xs text-gray-600 max-w-xs">
                                    <div class="line-clamp-2" title="{{ $product->oem_number }}">{{ $product->oem_number }}</div>
                                </td>
                                <td class="py-2">{{ $product->category?->name }}</td>
                                <td class="py-2">{{ $product->brand?->name }}</td>
                                <td class="py-2 text-right {{ $product->branch_stock <= $product->reorder_level ? 'text-red-600 font-semibold' : '' }}">
                                    {{ (int) $product->branch_stock }} {{ $product->unit }}
                                </td>
                                <td class="py-2 text-right text-gray-500 {{ $product->isLowStock() ? 'text-red-600' : '' }}">
                                    {{ $product->current_stock }}
                                </td>
                                <td class="py-2 text-right">{{ money($product->selling_price) }}</td>
                                <td class="py-2 text-right whitespace-nowrap space-x-2">
                                    <a href="{{ route('products.barcode.print', $product) }}" class="text-orange-600 hover:text-orange-800 font-medium hover:underline inline-flex items-center gap-0.5">
                                        <span>🖨️</span> Print
                                    </a>
                                    <a href="{{ route('products.edit', $product) }}" class="text-blue-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline" onsubmit="return confirm('Delete this product?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="py-6 text-center text-gray-400">No products found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    </div>
</x-app-layout>
