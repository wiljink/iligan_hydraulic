<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 print:hidden">
            <div class="flex items-center gap-3">
                <a href="{{ route('products.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">
                    &larr; Products
                </a>
                <span class="text-gray-300">/</span>
                <h2 class="text-xl font-semibold text-gray-800">Batch Barcode Printing</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('products.index') }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-md font-medium">
                    Back to Inventory
                </a>
            </div>
        </div>
    </x-slot>

    <div
        x-data="bulkBarcodePrinter({
            products: {{ Js::from($products->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode ?: $p->sku,
                'part_number' => $p->part_number ?? '',
                'shelf_location' => $p->shelf_location ?? '',
                'price' => (float)$p->selling_price,
                'formatted_price' => money($p->selling_price),
                'stock' => (int)$p->current_stock,
                'unit' => $p->unit,
                'barcode_url' => route('products.barcode', $p) . '?format=svg&scale=3&height=70',
                'selected' => $selectedIds->isNotEmpty() && $selectedIds->contains($p->id),
                'quantity' => 1,
            ])) }}
        })"
        class="max-w-7xl mx-auto space-y-6"
    >
        <!-- Filter and Options Card (Print Hidden) -->
        <div class="bg-white rounded-lg shadow-sm p-5 print:hidden space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b pb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Select Products &amp; Quantities</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Choose products and how many labels to print for each item.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="text-right text-xs">
                        <span class="text-gray-500">Total Labels:</span>
                        <strong class="text-base text-gray-900 block" x-text="totalLabelsCount"></strong>
                    </div>
                    <button
                        type="button"
                        @click="print()"
                        :disabled="totalLabelsCount === 0"
                        class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 disabled:opacity-50 text-white rounded-md text-sm font-semibold shadow flex items-center gap-2"
                    >
                        <span>🖨️</span>
                        <span>Print <span x-text="totalLabelsCount"></span> Labels</span>
                    </button>
                </div>
            </div>

            <!-- Global Quick Actions & Settings -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Bulk Quantity Actions -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Bulk Selection</label>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="selectAll(true)" class="px-2.5 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded font-medium text-gray-700">Select All</button>
                        <button type="button" @click="selectAll(false)" class="px-2.5 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded font-medium text-gray-700">Deselect All</button>
                        <button type="button" @click="setAllQty(1)" class="px-2.5 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded font-medium text-gray-700">All Qty = 1</button>
                        <button type="button" @click="setAllQtyStock()" class="px-2.5 py-1 text-xs bg-orange-100 hover:bg-orange-200 text-orange-800 rounded font-medium">All Qty = Stock</button>
                    </div>
                </div>

                <!-- Label Layout & Size -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Label Layout &amp; Size</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-[11px] text-gray-500 block mb-1">Layout</span>
                            <select x-model="layout" class="w-full rounded-md border-gray-300 shadow-sm text-xs py-1.5 font-medium">
                                <option value="grid">Sheet Grid (3-Col)</option>
                                <option value="thermal">Thermal Roll (1-Col)</option>
                                <option value="shelftag">Shelf Price Tag</option>
                            </select>
                        </div>
                        <div>
                            <span class="text-[11px] text-gray-500 block mb-1">Visibility / Size</span>
                            <select x-model="labelSize" class="w-full rounded-md border-gray-300 shadow-sm text-xs py-1.5 font-medium">
                                <option value="large">🌟 Large (High Visibility)</option>
                                <option value="standard">Standard</option>
                                <option value="compact">Compact</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2.5 flex items-center gap-4 text-xs text-gray-700">
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showBorders" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="font-medium">Border Outline</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="dashedBorder" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span>Dashed cut lines</span>
                        </label>
                    </div>
                </div>

                <!-- Display Options -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Display Details</label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showStore" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span>Store Name</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showName" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span>Product Name</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showPrice" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span>Selling Price</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showSku" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span>SKU / Part #</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="showCode" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span>Barcode Text</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Product Selection Table Header & Search -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="font-semibold text-gray-700">Products</span>
                    <span>&bull;</span>
                    <span x-text="selectedProductsCount + ' of ' + products.length + ' products selected'"></span>
                </div>
                <div class="relative w-full sm:w-64">
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="Search product, SKU, barcode..."
                        class="w-full rounded-md border-gray-300 shadow-sm text-xs py-1.5 pl-8 focus:border-orange-500 focus:ring-orange-500"
                    >
                    <span class="absolute left-2.5 top-2 text-gray-400 text-xs">🔍</span>
                </div>
            </div>

            <!-- Product Selection Table -->
            <div class="border rounded-md overflow-hidden">
                <div class="max-h-72 overflow-y-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 text-gray-600 sticky top-0 border-b">
                            <tr>
                                <th class="py-2 px-3 w-10 text-center">
                                    <input type="checkbox" @change="selectAll($event.target.checked)" :checked="isAllSelected" class="rounded border-gray-300 text-orange-600">
                                </th>
                                <th class="py-2 px-3">Product Name</th>
                                <th class="py-2 px-3">SKU / Barcode</th>
                                <th class="py-2 px-3 text-right">Price</th>
                                <th class="py-2 px-3 text-right">Stock</th>
                                <th class="py-2 px-3 text-center w-28">Label Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="p in filteredProducts" :key="p.id">
                                <tr :class="p.selected ? 'bg-orange-50/40' : 'opacity-60 hover:opacity-100'">
                                    <td class="py-2 px-3 text-center">
                                        <input type="checkbox" x-model="p.selected" class="rounded border-gray-300 text-orange-600">
                                    </td>
                                    <td class="py-2 px-3 font-medium text-gray-900" x-text="p.name"></td>
                                    <td class="py-2 px-3 font-mono text-gray-500">
                                        <div x-text="p.sku"></div>
                                        <div class="text-[10px] text-gray-400" x-text="p.barcode"></div>
                                    </td>
                                    <td class="py-2 px-3 text-right font-semibold" x-text="p.formatted_price"></td>
                                    <td class="py-2 px-3 text-right text-gray-600" x-text="p.stock + ' ' + p.unit"></td>
                                    <td class="py-2 px-3 text-center">
                                        <input
                                            type="number"
                                            min="0"
                                            max="200"
                                            x-model.number="p.quantity"
                                            :disabled="!p.selected"
                                            class="w-20 rounded border-gray-300 shadow-sm text-xs text-center py-1 font-semibold"
                                        >
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredProducts.length === 0">
                                <td colspan="6" class="py-6 text-center text-gray-400">No products match your search.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Printable Canvas Container -->
        <div class="bg-white rounded-lg shadow-sm p-6 print:p-0 print:shadow-none print:border-0">
            <!-- Preview Controls Bar (Screen Only) -->
            <div class="mb-4 print:hidden flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                <div class="flex items-center gap-2">
                    <h4 class="font-bold text-gray-800 text-sm">Print Preview</h4>
                    <span
                        class="text-xs px-2.5 py-0.5 rounded-full font-semibold"
                        :class="totalLabelsCount === 0 ? 'bg-gray-100 text-gray-500' : (previewMinimized ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-700')"
                        x-text="totalLabelsCount === 0 ? 'No items selected' : (previewMinimized ? 'Minimized (Sample Only)' : 'Full Preview (' + totalLabelsCount + ' Labels)')"
                    ></span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        x-show="totalLabelsCount > 0"
                        @click="previewMinimized = !previewMinimized"
                        class="px-3 py-1.5 text-xs font-semibold rounded-md border border-gray-300 hover:bg-gray-100 text-gray-700 flex items-center gap-1.5 transition cursor-pointer"
                    >
                        <span x-text="previewMinimized ? '👁️ Show All (' + totalLabelsCount + ' Labels)' : '➖ Minimize to Samples'"></span>
                    </button>
                    <button
                        type="button"
                        @click="print()"
                        :disabled="totalLabelsCount === 0"
                        class="px-3.5 py-1.5 bg-orange-600 hover:bg-orange-700 disabled:opacity-50 text-white rounded-md text-xs font-semibold shadow-sm flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>🖨️</span>
                        <span>Print</span>
                    </button>
                </div>
            </div>

            <!-- Empty Selection Notice -->
            <div x-show="totalLabelsCount === 0" class="text-center py-12 text-gray-400 text-sm print:hidden">
                <div class="text-3xl mb-2">🏷️</div>
                <p class="font-medium text-gray-600">No products selected for printing</p>
                <p class="text-xs text-gray-400 mt-1">Check the boxes next to the products you want to print in the table above.</p>
            </div>

            <!-- Minimized Notice banner on screen -->
            <div x-show="previewMinimized && totalLabelsCount > 0" class="mb-4 text-xs text-gray-600 bg-orange-50/70 border border-orange-200 rounded-md p-3 flex flex-wrap items-center justify-between gap-2 print:hidden">
                <span class="flex items-center gap-2">
                    <span>💡</span>
                    <span>Preview is <strong>minimized to sample labels</strong> to save screen space. When printed, all <strong><span x-text="totalLabelsCount"></span> labels</strong> across <strong><span x-text="selectedProductsCount"></span> products</strong> will be generated.</span>
                </span>
                <button type="button" @click="previewMinimized = false" class="text-orange-600 hover:text-orange-800 font-semibold underline text-xs cursor-pointer">
                    Expand all (<span x-text="totalLabelsCount"></span>)
                </button>
            </div>

            <!-- Print Grid Container -->
            <div
                x-show="totalLabelsCount > 0"
                :class="{
                    'preview-minimized-box': previewMinimized,
                    'preview-full-scroll': !previewMinimized,
                    'barcode-sheet-grid': layout === 'grid',
                    'barcode-thermal-list': layout === 'thermal',
                    'barcode-shelftag-grid': layout === 'shelftag'
                }"
            >
                <template x-for="p in selectedProducts" :key="p.id">
                    <template x-for="i in Array.from({ length: Math.max(0, p.quantity) }, (_, idx) => idx + 1)" :key="p.id + '-' + i">
                        <div
                            class="barcode-label"
                            :class="{
                                'border-2 border-gray-800': showBorders && !dashedBorder,
                                'border-2 border-dashed border-gray-400': showBorders && dashedBorder,
                                'border-transparent': !showBorders,
                                'label-size-large': labelSize === 'large',
                                'label-size-standard': labelSize === 'standard',
                                'label-size-compact': labelSize === 'compact',
                                'label-grid': layout === 'grid',
                                'label-thermal': layout === 'thermal',
                                'label-shelftag': layout === 'shelftag'
                            }"
                        >
                            <!-- Store Header -->
                            <div x-show="showStore" class="label-store text-center font-extrabold tracking-wider uppercase text-slate-900 truncate">
                                {{ setting('company_name', config('app.name')) }}
                            </div>

                            <!-- Product Name -->
                            <div x-show="showName" class="label-name text-center font-black text-black leading-tight" x-text="p.name">
                            </div>

                            <!-- SKU / Part # -->
                            <div x-show="showSku" class="label-sku text-center font-mono font-bold text-slate-800 truncate">
                                <span x-text="p.sku + (p.part_number ? ' • ' + p.part_number : '')"></span>
                                <template x-if="p.shelf_location">
                                    <span class="text-[10px] ml-1 px-1 bg-gray-200 rounded text-black font-semibold font-sans" x-text="'[' + p.shelf_location + ']'"></span>
                                </template>
                            </div>

                            <!-- Barcode Image (High Resolution Vector) -->
                            <div class="label-barcode flex justify-center items-center my-1 bg-white">
                                <img :src="p.barcode_url" alt="Barcode" class="max-w-full h-auto object-contain">
                            </div>

                            <!-- Barcode Digits -->
                            <div x-show="showCode" class="label-code text-center font-mono font-black tracking-widest text-black" x-text="p.barcode">
                            </div>

                            <!-- Price Tag -->
                            <div x-show="showPrice" class="label-price text-center font-black text-black">
                                <span class="inline-block bg-slate-100 print:bg-transparent px-2.5 py-0.5 rounded font-black text-black" x-text="p.formatted_price">
                                </span>
                            </div>
                        </div>
                    </template>
                </template>
            </div>
        </div>
    </div>

    <style>
        .barcode-sheet-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .barcode-thermal-list {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .barcode-shelftag-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .barcode-label {
            box-sizing: border-box;
            background: white;
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }

        /* 3-Column Sheet (Avery 5160 / A4 standard) */
        .label-grid {
            width: 100%;
            border-radius: 6px;
        }

        /* Thermal 50x30mm roll */
        .label-thermal {
            width: 220px;
            border-radius: 6px;
        }

        /* Shelf Price Tag (Wide) */
        .label-shelftag {
            width: 100%;
            border-radius: 6px;
        }

        /* Large (High Visibility - Default) */
        .label-size-large {
            min-height: 165px;
            padding: 10px 14px;
        }
        .label-size-large .label-store { font-size: 11px; letter-spacing: 0.08em; }
        .label-size-large .label-name { font-size: 15px; margin: 3px 0; }
        .label-size-large .label-sku { font-size: 12px; }
        .label-size-large .label-barcode img { max-height: 65px; height: 65px; }
        .label-size-large .label-code { font-size: 13px; letter-spacing: 0.15em; }
        .label-size-large .label-price { font-size: 21px; font-weight: 900; margin-top: 4px; }

        /* Standard */
        .label-size-standard {
            min-height: 135px;
            padding: 8px 10px;
        }
        .label-size-standard .label-store { font-size: 10px; }
        .label-size-standard .label-name { font-size: 13px; margin: 2px 0; }
        .label-size-standard .label-sku { font-size: 11px; }
        .label-size-standard .label-barcode img { max-height: 52px; height: 52px; }
        .label-size-standard .label-code { font-size: 12px; letter-spacing: 0.12em; }
        .label-size-standard .label-price { font-size: 17px; font-weight: 900; margin-top: 3px; }

        /* Compact */
        .label-size-compact {
            min-height: 110px;
            padding: 6px 8px;
        }
        .label-size-compact .label-store { font-size: 9px; }
        .label-size-compact .label-name { font-size: 11px; }
        .label-size-compact .label-sku { font-size: 9px; }
        .label-size-compact .label-barcode img { max-height: 42px; height: 42px; }
        .label-size-compact .label-code { font-size: 10px; letter-spacing: 0.1em; }
        .label-size-compact .label-price { font-size: 14px; font-weight: 900; }

        /* Screen Preview Minimization Styles */
        @media screen {
            .preview-minimized-box {
                display: flex !important;
                flex-wrap: wrap !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 16px !important;
                padding: 2rem !important;
                background-color: #f8fafc !important;
                border-radius: 0.75rem !important;
                border: 2px dashed #cbd5e1 !important;
                min-height: 200px !important;
            }
            .preview-minimized-box .barcode-label {
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08) !important;
                background-color: #ffffff !important;
                max-width: 320px !important;
                width: 100% !important;
            }
            .preview-minimized-box .barcode-label:nth-child(n+4) {
                display: none !important;
            }
            .preview-full-scroll {
                max-height: 550px;
                overflow-y: auto;
                padding: 1rem;
                background-color: #f8fafc;
                border-radius: 0.75rem;
                border: 1px solid #e2e8f0;
            }
        }

        @media print {
            @page {
                margin: 5mm;
                size: auto;
            }

            body, html {
                background: white !important;
                color: black !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            aside, header, nav, .print\:hidden {
                display: none !important;
            }

            main {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            .barcode-sheet-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 4mm !important;
                width: 100% !important;
            }

            .barcode-thermal-list {
                display: block !important;
                width: 100% !important;
            }

            .barcode-thermal-list .barcode-label {
                width: 50mm !important;
                min-height: 30mm !important;
                margin: 0 auto 3mm auto !important;
                page-break-after: always;
                break-after: page;
            }

            .barcode-shelftag-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 5mm !important;
            }

            .barcode-label {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                background: white !important;
                color: black !important;
            }
        }
    </style>

    <script>
        function bulkBarcodePrinter(data) {
            return {
                products: data.products,
                layout: 'grid',
                labelSize: 'large',
                previewMinimized: true,
                searchQuery: '',
                showBorders: true,
                dashedBorder: false,
                showStore: true,
                showName: true,
                showPrice: true,
                showSku: true,
                showCode: true,

                get filteredProducts() {
                    if (!this.searchQuery.trim()) return this.products;
                    const q = this.searchQuery.toLowerCase();
                    return this.products.filter(p =>
                        p.name.toLowerCase().includes(q) ||
                        p.sku.toLowerCase().includes(q) ||
                        p.barcode.toLowerCase().includes(q) ||
                        (p.part_number && p.part_number.toLowerCase().includes(q))
                    );
                },

                get selectedProducts() {
                    return this.products.filter(p => p.selected && p.quantity > 0);
                },

                get selectedProductsCount() {
                    return this.selectedProducts.length;
                },

                get totalLabelsCount() {
                    return this.selectedProducts.reduce((sum, p) => sum + (parseInt(p.quantity) || 0), 0);
                },

                get isAllSelected() {
                    return this.products.length > 0 && this.products.every(p => p.selected);
                },

                selectAll(val) {
                    this.products.forEach(p => p.selected = val);
                },

                setAllQty(qty) {
                    this.products.forEach(p => {
                        if (p.selected) p.quantity = qty;
                    });
                },

                setAllQtyStock() {
                    this.products.forEach(p => {
                        if (p.selected) p.quantity = Math.max(1, p.stock);
                    });
                },

                print() {
                    window.print();
                }
            };
        }
    </script>
</x-app-layout>
