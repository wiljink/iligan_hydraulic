<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">New Stock Transfer</h2>
            <a href="{{ route('transfers.index') }}" class="px-4 py-2 text-gray-500 text-sm">Cancel</a>
        </div>
    </x-slot>

    <div
        x-data="{
            products: {{ Js::from($products) }},
            from: '{{ old('from_branch_id', $fromBranch?->id) }}',
            search: '',
            items: [],
            get results() {
                const term = this.search.trim().toLowerCase();
                if (!term) return [];
                return this.products
                    .filter(p => !this.items.some(i => i.id === p.id))
                    .filter(p => p.name.toLowerCase().includes(term) || (p.sku || '').toLowerCase().includes(term))
                    .slice(0, 10);
            },
            available(p) { return Number(p.stock[this.from] ?? 0); },
            add(p) { this.items.push({ ...p, quantity: 1 }); this.search = ''; },
            remove(index) { this.items.splice(index, 1); },
        }"
        class="max-w-5xl"
    >
        @if ($errors->any())
            <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('transfers.store') }}" class="space-y-6">
            @csrf
            <div class="bg-white rounded-lg shadow-sm p-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <x-input-label for="from_branch_id" value="From (source)" />
                    <select id="from_branch_id" name="from_branch_id" x-model="from" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="to_branch_id" value="To (destination)" />
                    <select id="to_branch_id" name="to_branch_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                        <option value="">-- Select branch --</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('to_branch_id') == $b->id) x-bind:disabled="from == '{{ $b->id }}'">{{ $b->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="transfer_date" value="Transfer Date" />
                    <x-text-input id="transfer_date" type="date" name="transfer_date" value="{{ old('transfer_date', now()->toDateString()) }}" class="mt-1 block w-full" required />
                </div>
                <div class="sm:col-span-3">
                    <x-input-label for="notes" value="Notes" />
                    <x-text-input id="notes" name="notes" value="{{ old('notes') }}" class="mt-1 block w-full" placeholder="Optional, e.g. restock for weekend" />
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="relative mb-4">
                    <x-input-label for="search" value="Add products" />
                    <x-text-input id="search" x-model="search" class="mt-1 block w-full" placeholder="Search by name or SKU..." autocomplete="off" @keydown.enter.prevent="results.length && add(results[0])" />
                    <ul x-show="results.length" x-cloak class="absolute z-10 mt-1 w-full bg-white border rounded-md shadow-lg max-h-64 overflow-y-auto text-sm">
                        <template x-for="p in results" :key="p.id">
                            <li>
                                <button type="button" @click="add(p)" class="w-full text-left px-3 py-2 hover:bg-orange-50 flex justify-between">
                                    <span><span x-text="p.name"></span> <span class="text-gray-400 font-mono text-xs" x-text="p.sku"></span></span>
                                    <span class="text-xs" :class="available(p) > 0 ? 'text-gray-500' : 'text-red-500'" x-text="'Available: ' + available(p)"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2">Product</th>
                            <th class="py-2 text-right">Available at Source</th>
                            <th class="py-2 text-right w-32">Quantity</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="item.id">
                            <tr class="border-b last:border-0">
                                <td class="py-2">
                                    <span x-text="item.name"></span>
                                    <span class="block text-xs text-gray-400 font-mono" x-text="item.sku"></span>
                                    <input type="hidden" :name="`items[${index}][product_id]`" :value="item.id">
                                </td>
                                <td class="py-2 text-right" :class="available(item) < item.quantity ? 'text-red-600 font-semibold' : ''">
                                    <span x-text="available(item)"></span> <span x-text="item.unit"></span>
                                </td>
                                <td class="py-2 text-right">
                                    <input type="number" min="1" :max="available(item)" :name="`items[${index}][quantity]`" x-model.number="item.quantity" class="w-24 rounded-md border-gray-300 shadow-sm text-sm text-right" required>
                                </td>
                                <td class="py-2 text-right">
                                    <button type="button" @click="remove(index)" class="text-red-600 hover:underline text-xs">Remove</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!items.length">
                            <td colspan="4" class="py-6 text-center text-gray-400">Search above to add products to transfer.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <x-primary-button x-bind:disabled="!items.length">Complete Transfer</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
