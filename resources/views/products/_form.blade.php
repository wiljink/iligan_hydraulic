@php $product = $product ?? null; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <x-input-label for="name" value="Product Name *" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" value="{{ old('name', $product?->name) }}" required />
    </div>
    <div>
        <x-input-label for="sku" value="SKU (leave blank to auto-generate)" />
        <x-text-input id="sku" name="sku" class="mt-1 block w-full font-mono" value="{{ old('sku', $product?->sku) }}" />
    </div>
    <div x-data="{
        barcodeValue: '{{ old('barcode', $product?->barcode) }}',
        generating: false,
        async generateBarcode() {
            this.generating = true;
            try {
                const res = await fetch('{{ route('products.generate-barcode') }}');
                const data = await res.json();
                if (data.barcode) {
                    this.barcodeValue = data.barcode;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.generating = false;
            }
        }
    }">
        <div class="flex items-center justify-between">
            <x-input-label for="barcode" value="Barcode" />
            <button
                type="button"
                @click="generateBarcode()"
                :disabled="generating"
                class="text-xs text-orange-600 hover:text-orange-700 font-medium inline-flex items-center gap-1 focus:outline-none cursor-pointer"
            >
                <span x-show="!generating">⚡ Auto-Generate</span>
                <span x-show="generating" x-cloak>Generating...</span>
            </button>
        </div>
        <div class="mt-1 flex rounded-md shadow-sm">
            <input
                id="barcode"
                name="barcode"
                type="text"
                x-model="barcodeValue"
                placeholder="Leave blank to use SKU or auto-generate"
                class="block w-full rounded-md border-gray-300 bg-white text-gray-900 font-mono text-sm focus:border-orange-500 focus:ring-orange-500"
            />
        </div>
        <p class="text-[11px] text-gray-500 mt-1">Supports standard 13-digit EAN, UPC, or custom barcodes. If empty, SKU will be used.</p>
    </div>
    <div>
        <x-input-label for="unit" value="Unit *" />
        <select id="unit" name="unit" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm text-sm focus:border-orange-500 focus:ring-orange-500" required>
            @foreach ($units as $unit)
                <option value="{{ $unit }}" @selected(old('unit', $product?->unit ?? 'pcs') === $unit)>{{ $unit }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="part_number" value="Part Number" />
        <x-text-input id="part_number" name="part_number" class="mt-1 block w-full" value="{{ old('part_number', $product?->part_number) }}" />
    </div>
    <div>
        <x-input-label for="oem_number" value="OEM Number(s)" />
        <textarea id="oem_number" name="oem_number" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="Separate multiple numbers with commas">{{ old('oem_number', $product?->oem_number) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <x-input-label for="fitment" value="Fitment / Compatible Vehicles" />
        <x-text-input id="fitment" name="fitment" class="mt-1 block w-full" value="{{ old('fitment', $product?->fitment) }}" placeholder="e.g. Toyota Hilux 2015-2020, Isuzu D-Max" />
    </div>
    <div>
        <x-input-label for="category_id" value="Category" />
        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm text-sm focus:border-orange-500 focus:ring-orange-500">
            <option value="">-- None --</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $product?->category_id) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="brand_id" value="Brand" />
        <select id="brand_id" name="brand_id" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm text-sm focus:border-orange-500 focus:ring-orange-500">
            <option value="">-- None --</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected(old('brand_id', $product?->brand_id) == $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="supplier_id" value="Supplier" />
        <select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm text-sm focus:border-orange-500 focus:ring-orange-500">
            <option value="">-- None --</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $product?->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="shelf_location" value="Shelf Location" />
        <x-text-input id="shelf_location" name="shelf_location" class="mt-1 block w-full" value="{{ old('shelf_location', $product?->shelf_location) }}" placeholder="e.g. A1-01" />
    </div>
    @php
        $initialCost = (float) old('cost_price', $product?->cost_price ?? 0);
        $initialSelling = (float) old('selling_price', $product?->selling_price ?? 0);
        $initialMarkup = $initialCost > 0 && $initialSelling > 0 ? round(($initialSelling / $initialCost - 1) * 100, 2) : '';
    @endphp
    {{-- Selling price = cost + markup %. Editing the selling price directly back-computes the markup. --}}
    <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4" x-data="{
        cost: '{{ $initialCost }}',
        markup: '{{ $initialMarkup }}',
        selling: '{{ $initialSelling }}',
        fromMarkup() {
            const cost = parseFloat(this.cost), markup = parseFloat(this.markup);
            if (!isNaN(cost) && !isNaN(markup)) this.selling = (cost * (1 + markup / 100)).toFixed(2);
        },
        fromSelling() {
            const cost = parseFloat(this.cost), selling = parseFloat(this.selling);
            this.markup = cost > 0 && !isNaN(selling) ? (+((selling / cost - 1) * 100).toFixed(2)).toString() : '';
        },
    }">
        <div>
            <x-input-label for="cost_price" value="Cost Price *" />
            <x-text-input id="cost_price" type="number" step="0.01" min="0" name="cost_price" class="mt-1 block w-full" x-model="cost" @input="fromMarkup()" required />
        </div>
        <div>
            <x-input-label for="markup_percent" value="Markup (%)" />
            <x-text-input id="markup_percent" type="number" step="0.01" class="mt-1 block w-full" x-model="markup" @input="fromMarkup()" placeholder="e.g. 30" />
            <p class="mt-1 text-xs text-gray-400">Added to the cost to get the selling price.</p>
        </div>
        <div>
            <x-input-label for="selling_price" value="Selling Price *" />
            <x-text-input id="selling_price" type="number" step="0.01" min="0" name="selling_price" class="mt-1 block w-full" x-model="selling" @input="fromSelling()" required />
        </div>
    </div>
    <div>
        <x-input-label for="reorder_level" value="Reorder Level *" />
        <x-text-input id="reorder_level" type="number" min="0" name="reorder_level" class="mt-1 block w-full" value="{{ old('reorder_level', $product?->reorder_level ?? config('pos.low_stock_default')) }}" required />
    </div>
    @if (! $product)
        <div>
            <x-input-label for="opening_stock" value="Opening Stock" />
            <x-text-input id="opening_stock" type="number" min="0" name="opening_stock" class="mt-1 block w-full" value="{{ old('opening_stock', 0) }}" />
        </div>
    @endif
    <div class="md:col-span-2">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm text-sm focus:border-orange-500 focus:ring-orange-500">{{ old('description', $product?->description) }}</textarea>
    </div>
    @if ($product)
        <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="rounded border-gray-300">
            <x-input-label for="is_active" value="Active (visible in POS)" />
        </div>
    @endif
</div>
