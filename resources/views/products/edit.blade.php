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
                @php($branchQty = $product->stockAt($branch))
                <h3 class="font-semibold mb-1">Stock @ {{ $branch?->name }}</h3>
                <p class="text-3xl font-bold {{ $branchQty <= $product->reorder_level ? 'text-red-600' : 'text-gray-800' }}">{{ $branchQty }} <span class="text-base font-normal text-gray-400">{{ $product->unit }}</span></p>
                <p class="text-xs text-gray-400">Reorder level: {{ $product->reorder_level }} &middot; All branches: {{ $product->current_stock }}</p>

                @if ($product->branchStocks->count() > 1)
                    <ul class="mt-3 text-xs divide-y border rounded-md">
                        @foreach ($product->branchStocks->sortBy('branch.name') as $stock)
                            <li class="flex justify-between px-3 py-1.5 {{ $stock->branch_id === $branch?->id ? 'bg-orange-50 font-semibold' : '' }}">
                                <span>{{ $stock->branch?->label }}</span>
                                <span>{{ $stock->quantity }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <p class="text-xs text-gray-500 mt-4 mb-2">Adjustments apply to <strong>{{ $branch?->name }}</strong>.</p>

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
                    @forelse ($product->stockMovements()->with('branch')->latest()->limit(15)->get() as $movement)
                        <li class="flex justify-between border-b pb-1 last:border-0">
                            <span>
                                <span class="capitalize">{{ str_replace('_', ' ', $movement->type) }}</span>
                                <span class="text-gray-500 text-xs">{{ $movement->branch?->name }}</span>
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

            <div class="bg-white rounded-lg shadow-sm p-5 text-center space-y-3">
                <div class="flex items-center justify-between border-b pb-2">
                    <h3 class="font-semibold text-sm text-gray-800">Barcode</h3>
                    <span class="text-[11px] px-2 py-0.5 rounded bg-gray-100 text-gray-600 font-mono font-semibold">{{ $product->barcode_type }}</span>
                </div>

                <div class="p-3 bg-gray-50 rounded-md border border-gray-100">
                    <img src="{{ route('products.barcode', $product) }}?format=svg&scale=2&height=50" alt="Barcode" class="mx-auto max-h-14 w-auto object-contain">
                    <p class="font-mono text-sm tracking-widest font-semibold text-gray-800 mt-2">{{ $product->barcode ?: $product->sku }}</p>
                    @if (!$product->barcode)
                        <p class="text-[11px] text-gray-400 mt-0.5">(Generated from SKU)</p>
                    @endif
                </div>

                <div class="space-y-2 pt-1">
                    <a href="{{ route('products.barcode.print', $product) }}" class="inline-flex items-center justify-center gap-2 w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-md text-sm font-semibold shadow-sm transition">
                        <span>🖨️</span> Print Barcode Labels
                    </a>
                    <a href="{{ route('products.barcode', $product) }}?download=1" class="inline-flex items-center justify-center gap-1.5 w-full px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-xs font-medium transition">
                        <span>⬇️</span> Download PNG
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
