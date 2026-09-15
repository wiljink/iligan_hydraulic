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
    <div>
        <x-input-label for="barcode" value="Barcode" />
        <x-text-input id="barcode" name="barcode" class="mt-1 block w-full font-mono" value="{{ old('barcode', $product?->barcode) }}" />
    </div>
    <div>
        <x-input-label for="unit" value="Unit *" />
        <select id="unit" name="unit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
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
        <x-input-label for="oem_number" value="OEM Number" />
        <x-text-input id="oem_number" name="oem_number" class="mt-1 block w-full" value="{{ old('oem_number', $product?->oem_number) }}" />
    </div>
    <div class="md:col-span-2">
        <x-input-label for="fitment" value="Fitment / Compatible Vehicles" />
        <x-text-input id="fitment" name="fitment" class="mt-1 block w-full" value="{{ old('fitment', $product?->fitment) }}" placeholder="e.g. Toyota Hilux 2015-2020, Isuzu D-Max" />
    </div>
    <div>
        <x-input-label for="category_id" value="Category" />
        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            <option value="">-- None --</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $product?->category_id) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="brand_id" value="Brand" />
        <select id="brand_id" name="brand_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
            <option value="">-- None --</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" @selected(old('brand_id', $product?->brand_id) == $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="supplier_id" value="Supplier" />
        <select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
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
    <div>
        <x-input-label for="cost_price" value="Cost Price *" />
        <x-text-input id="cost_price" type="number" step="0.01" min="0" name="cost_price" class="mt-1 block w-full" value="{{ old('cost_price', $product?->cost_price ?? 0) }}" required />
    </div>
    <div>
        <x-input-label for="selling_price" value="Selling Price *" />
        <x-text-input id="selling_price" type="number" step="0.01" min="0" name="selling_price" class="mt-1 block w-full" value="{{ old('selling_price', $product?->selling_price ?? 0) }}" required />
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
        <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('description', $product?->description) }}</textarea>
    </div>
    @if ($product)
        <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="rounded border-gray-300">
            <x-input-label for="is_active" value="Active (visible in POS)" />
        </div>
    @endif
</div>
