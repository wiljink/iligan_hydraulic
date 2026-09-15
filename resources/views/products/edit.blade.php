<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Edit Product: {{ $product->name }}</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
            <form method="POST" action="{{ route('products.update', $product) }}">
                @csrf @method('PUT')
                @include('products._form')
                <div class="mt-6 flex gap-3">
                    <x-primary-button>Update Product</x-primary-button>
                    <a href="{{ route('products.index') }}" class="px-4 py-2 text-gray-500 text-sm">Cancel</a>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-semibold mb-1">Current Stock</h3>
                <p class="text-3xl font-bold {{ $product->isLowStock() ? 'text-red-600' : 'text-gray-800' }}">{{ $product->current_stock }} <span class="text-base font-normal text-gray-400">{{ $product->unit }}</span></p>
                <p class="text-xs text-gray-400 mb-4">Reorder level: {{ $product->reorder_level }}</p>

                <form method="POST" action="{{ route('products.adjust-stock', $product) }}" class="space-y-2">
                    @csrf
                    <select name="type" class="w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                        <option value="purchase">Purchase / Restock (+)</option>
                        <option value="adjustment">Manual Adjustment (+/-)</option>
                        <option value="wastage">Wastage / Damage (-)</option>
                        <option value="return">Return to Stock (+)</option>
                    </select>
                    <input type="number" name="quantity" placeholder="Quantity" class="w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                    <input type="text" name="note" placeholder="Note (optional)" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <button class="w-full px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Apply Adjustment</button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-semibold mb-3">Recent Stock Movements</h3>
                <ul class="space-y-2 text-sm max-h-80 overflow-y-auto">
                    @forelse ($product->stockMovements()->latest()->limit(15)->get() as $movement)
                        <li class="flex justify-between border-b pb-1 last:border-0">
                            <span>
                                <span class="capitalize">{{ $movement->type }}</span>
                                <span class="text-gray-400 block text-xs">{{ $movement->created_at->format('M d, Y g:ia') }}</span>
                            </span>
                            <span class="{{ $movement->quantity < 0 ? 'text-red-600' : 'text-green-600' }} font-medium">
                                {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                            </span>
                        </li>
                    @empty
                        <li class="text-gray-400">No movements yet.</li>
                    @endforelse
                </ul>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-5 text-center">
                <img src="{{ route('products.barcode', $product) }}" alt="Barcode" class="mx-auto mb-2">
                <p class="text-xs text-gray-400">{{ $product->barcode ?: $product->sku }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
