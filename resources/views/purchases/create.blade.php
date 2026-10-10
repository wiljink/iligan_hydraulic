<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('purchases.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">
                    &larr; Deliveries
                </a>
                <span class="text-gray-300">/</span>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Receive Stock Delivery (Stock In)</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Record incoming supplier stocks, update inventory counts, and log supplier invoices
                    </p>
                </div>
            </div>
            <a
                href="{{ route('purchases.index') }}"
                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs rounded-md font-semibold transition"
            >
                Cancel
            </a>
        </div>
    </x-slot>

    <div
        x-data="stockInManager({
            availableProducts: {{ Js::from($products->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode ?: $p->sku,
                'unit' => $p->unit,
                'cost_price' => (float) $p->cost_price,
                'current_stock' => (int) $p->branch_stock,
            ])) }},
            initialSuppliers: {{ Js::from($suppliers) }}
        })"
        class="max-w-7xl mx-auto"
    >
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">
                <p class="font-bold mb-1">Please fix the following issues:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4 p-3 rounded-lg bg-orange-50 border border-orange-200 text-sm text-orange-900">
            Receiving stock into <strong>{{ $branch?->label }}</strong>. Stock levels shown are for this branch.
            @if (auth()->user()->canSwitchBranch())
                <span class="text-orange-700">Switch branches from the sidebar to receive elsewhere.</span>
            @endif
        </div>

        <form method="POST" action="{{ route('purchases.store') }}" @submit="return validateForm($event)">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Columns: Delivery Header & Line Items -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Delivery Information Card -->
                    <div class="bg-white rounded-xl shadow-xs p-5 border border-gray-100">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span>📄</span>
                            <span>Delivery Receipt Information</span>
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Supplier -->
                            <div>
                                <x-input-label for="supplier_id" value="Supplier / Vendor *" />
                                <select
                                    id="supplier_id"
                                    name="supplier_id"
                                    x-model="selectedSupplierId"
                                    @change="onSupplierChange()"
                                    class="w-full rounded-md border-gray-300 text-xs py-2 bg-white text-gray-900 focus:border-orange-500 focus:ring-orange-500"
                                    required
                                >
                                    <option value="">-- Select Supplier --</option>
                                    <template x-for="s in initialSuppliers" :key="s.id">
                                        <option :value="s.id" x-text="s.name"></option>
                                    </template>
                                    <option value="custom">+ Other / Direct Vendor</option>
                                </select>
                            </div>

                            <!-- Custom Vendor Name (if custom selected) -->
                            <div x-show="selectedSupplierId === 'custom'" x-cloak>
                                <x-input-label for="supplier_name" value="Custom Vendor Name *" />
                                <x-text-input
                                    id="supplier_name"
                                    name="supplier_name"
                                    type="text"
                                    placeholder="e.g. Direct Wholesaler"
                                    class="w-full text-xs"
                                    x-model="customSupplierName"
                                />
                            </div>

                            <!-- Supplier DR / Invoice # -->
                            <div>
                                <x-input-label for="reference_number" value="Supplier DR # / Invoice #" />
                                <x-text-input
                                    id="reference_number"
                                    name="reference_number"
                                    type="text"
                                    placeholder="e.g. DR-2026-9901"
                                    class="w-full text-xs font-mono"
                                    required
                                />
                            </div>

                            <!-- Receipt Date -->
                            <div>
                                <x-input-label for="receipt_date" value="Delivery Date *" />
                                <x-text-input
                                    id="receipt_date"
                                    name="receipt_date"
                                    type="date"
                                    value="{{ now()->toDateString() }}"
                                    class="w-full text-xs"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Items Search & Scanner Card -->
                    <div class="bg-white rounded-xl shadow-xs p-5 border border-gray-100">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                                    <span>📦</span>
                                    <span>Delivered Items</span>
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Scan barcode or search by name / SKU to add delivered stocks
                                </p>
                            </div>
                            <span class="text-xs font-bold px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                <span x-text="items.length"></span> line(s) added
                            </span>
                        </div>

                        <!-- Search Autocomplete Input -->
                        <div class="relative mb-4">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <span>🔍</span>
                                </div>
                                <input
                                    type="text"
                                    x-model="searchQuery"
                                    @input="filterProducts()"
                                    @keydown.arrow-down.prevent="navigateResults(1)"
                                    @keydown.arrow-up.prevent="navigateResults(-1)"
                                    @keydown.enter.prevent="selectHighlightedOrFirst()"
                                    placeholder="Scan barcode or type product name, SKU, or part number..."
                                    class="w-full pl-9 pr-4 py-2.5 text-xs rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 bg-gray-50 focus:bg-white transition"
                                />
                            </div>

                            <!-- Search Results Dropdown -->
                            <div
                                x-show="searchResults.length > 0"
                                x-cloak
                                @click.outside="searchResults = []"
                                class="absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-64 overflow-y-auto divide-y divide-gray-100"
                            >
                                <template x-for="(prod, idx) in searchResults" :key="prod.id">
                                    <div
                                        @click="addItem(prod)"
                                        :class="{'bg-orange-50': highlightedIndex === idx}"
                                        class="p-2.5 hover:bg-orange-50 cursor-pointer flex items-center justify-between text-xs transition"
                                    >
                                        <div>
                                            <span class="font-bold text-gray-900 block" x-text="prod.name"></span>
                                            <div class="flex items-center gap-2 text-[11px] text-gray-400 font-mono mt-0.5">
                                                <span>SKU: <span x-text="prod.sku"></span></span>
                                                <span x-show="prod.barcode">&bull; Barcode: <span x-text="prod.barcode"></span></span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-semibold text-gray-800 block">Cost: ₱<span x-text="prod.cost_price.toFixed(2)"></span></span>
                                            <span class="text-[11px] text-gray-500">Current Stock: <strong x-text="prod.current_stock"></strong> <span x-text="prod.unit"></span></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-gray-50 text-gray-600 border-b">
                                    <tr>
                                        <th class="py-2.5 px-3">Item Description</th>
                                        <th class="py-2.5 px-3 text-center">Current</th>
                                        <th class="py-2.5 px-3 text-center w-28">Received Qty *</th>
                                        <th class="py-2.5 px-3 text-right w-32">Unit Cost (₱) *</th>
                                        <th class="py-2.5 px-3 text-right">Subtotal</th>
                                        <th class="py-2.5 px-3 text-center" title="Update master cost price in catalog">Sync Cost</th>
                                        <th class="py-2.5 px-3 text-center w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="(item, index) in items" :key="item.product_id">
                                        <tr class="hover:bg-gray-50/50">
                                            <!-- Product info -->
                                            <td class="py-2.5 px-3">
                                                <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.product_id">
                                                <span class="font-bold text-gray-900 block" x-text="item.name"></span>
                                                <span class="text-[11px] font-mono text-gray-400" x-text="item.sku"></span>
                                            </td>

                                            <!-- Current Stock -->
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-700">
                                                    <span x-text="item.current_stock"></span> <span x-text="item.unit"></span>
                                                </span>
                                            </td>

                                            <!-- Quantity Delivered -->
                                            <td class="py-2.5 px-3 text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <input
                                                        type="number"
                                                        :name="'items[' + index + '][quantity]'"
                                                        x-model.number="item.quantity"
                                                        @input="recalculate()"
                                                        min="1"
                                                        class="w-20 text-center font-bold text-xs py-1 rounded border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                                                        required
                                                    />
                                                    <span class="text-[11px] text-gray-400" x-text="item.unit"></span>
                                                </div>
                                            </td>

                                            <!-- Unit Cost Price -->
                                            <td class="py-2.5 px-3 text-right">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    :name="'items[' + index + '][unit_cost]'"
                                                    x-model.number="item.unit_cost"
                                                    @input="recalculate()"
                                                    class="w-28 text-right font-mono text-xs py-1 rounded border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                                                    required
                                                />
                                            </td>

                                            <!-- Line Subtotal -->
                                            <td class="py-2.5 px-3 text-right font-black text-gray-900">
                                                ₱<span x-text="(item.quantity * item.unit_cost).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                            </td>

                                            <!-- Update master cost price -->
                                            <td class="py-2.5 px-3 text-center">
                                                <input
                                                    type="checkbox"
                                                    :name="'items[' + index + '][update_cost_price]'"
                                                    value="1"
                                                    x-model="item.update_cost_price"
                                                    class="rounded border-gray-300 text-orange-600 focus:ring-orange-500 h-4 w-4"
                                                    title="Check to update master product cost price to this delivery's unit cost"
                                                />
                                            </td>

                                            <!-- Remove button -->
                                            <td class="py-2.5 px-3 text-center">
                                                <button
                                                    type="button"
                                                    @click="removeItem(index)"
                                                    class="text-gray-400 hover:text-red-600 text-sm p-1 cursor-pointer"
                                                    title="Remove item"
                                                >
                                                    &times;
                                                </button>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Empty state -->
                                    <tr x-show="items.length === 0">
                                        <td colspan="7" class="py-10 text-center text-gray-400">
                                            <span class="text-3xl block mb-2">📥</span>
                                            <p class="text-xs font-semibold text-gray-600">No products added to this delivery yet</p>
                                            <p class="text-[11px] text-gray-400 mt-0.5">Use the search box above or scan barcodes to begin adding incoming inventory.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Payment Terms & Summary -->
                <div class="space-y-6">
                    <!-- Summary Card -->
                    <div class="bg-white rounded-xl shadow-xs p-5 border border-gray-100">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span>📊</span>
                            <span>Delivery Summary</span>
                        </h3>

                        <div class="space-y-2.5 text-xs text-gray-600 border-b pb-4">
                            <div class="flex justify-between">
                                <span>Unique Line Items:</span>
                                <strong class="text-gray-900" x-text="items.length"></strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Total Units Received:</span>
                                <strong class="text-gray-900" x-text="totalUnits"></strong>
                            </div>
                        </div>

                        <div class="pt-4 flex justify-between items-baseline">
                            <span class="text-sm font-bold text-gray-700">Total Delivery Cost:</span>
                            <span class="text-2xl font-black text-orange-600 tracking-tight">
                                ₱<span x-text="totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Payment Terms & Accounts Payable Card -->
                    <div class="bg-white rounded-xl shadow-xs p-5 border border-gray-100">
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <span>💳</span>
                            <span>Payment Terms</span>
                        </h3>

                        <div class="space-y-3">
                            <!-- Option 1: Credit / Pay Later -->
                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition" :class="paymentTerms === 'credit' ? 'border-orange-500 bg-orange-50/40' : 'border-gray-200 hover:bg-gray-50'">
                                <input
                                    type="radio"
                                    name="payment_terms"
                                    value="credit"
                                    x-model="paymentTerms"
                                    class="mt-0.5 text-orange-600 focus:ring-orange-500"
                                />
                                <div>
                                    <span class="font-bold text-xs text-gray-900 block">Charge on Credit (Pay Later)</span>
                                    <span class="text-[11px] text-gray-500 block mt-0.5">
                                        Auto-creates an <strong>Accounts Payable</strong> bill in your dashboard to track payment.
                                    </span>
                                </div>
                            </label>

                            <!-- Credit Options (Due Date & Downpayment) -->
                            <div x-show="paymentTerms === 'credit'" x-cloak class="p-3 bg-gray-50 rounded-lg border border-gray-200 space-y-3 text-xs">
                                <div>
                                    <x-input-label for="due_date" value="Payment Due Date" />
                                    <input
                                        type="date"
                                        id="due_date"
                                        name="due_date"
                                        value="{{ now()->addDays(30)->toDateString() }}"
                                        class="w-full rounded-md border-gray-300 text-xs py-1.5 bg-white text-gray-900 focus:border-orange-500 focus:ring-orange-500"
                                    />
                                </div>

                                <div>
                                    <x-input-label for="downpayment" value="Downpayment Paid Now (₱, optional)" />
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        id="downpayment"
                                        name="downpayment"
                                        x-model.number="downpayment"
                                        placeholder="0.00"
                                        class="w-full rounded-md border-gray-300 text-xs py-1.5 bg-white text-gray-900 focus:border-orange-500 focus:ring-orange-500"
                                    />
                                </div>

                                <div x-show="downpayment > 0" class="flex justify-between pt-2 border-t border-gray-200 font-semibold text-gray-800">
                                    <span>Remaining Balance Due:</span>
                                    <span class="text-red-600">₱<span x-text="Math.max(0, totalAmount - downpayment).toFixed(2)"></span></span>
                                </div>
                            </div>

                            <!-- Option 2: Paid Immediately -->
                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition" :class="paymentTerms === 'paid' ? 'border-emerald-500 bg-emerald-50/40' : 'border-gray-200 hover:bg-gray-50'">
                                <input
                                    type="radio"
                                    name="payment_terms"
                                    value="paid"
                                    x-model="paymentTerms"
                                    class="mt-0.5 text-emerald-600 focus:ring-emerald-500"
                                />
                                <div>
                                    <span class="font-bold text-xs text-gray-900 block">Paid in Full Immediately</span>
                                    <span class="text-[11px] text-gray-500 block mt-0.5">
                                        Settled right away via cash, bank transfer, or check.
                                    </span>
                                </div>
                            </label>

                            <!-- Paid Immediately Options -->
                            <div x-show="paymentTerms === 'paid'" x-cloak class="p-3 bg-gray-50 rounded-lg border border-gray-200 space-y-3 text-xs">
                                <div>
                                    <x-input-label for="payment_method" value="Payment Method" />
                                    <select
                                        id="payment_method"
                                        name="payment_method"
                                        class="w-full rounded-md border-gray-300 text-xs py-1.5 bg-white text-gray-900 focus:border-orange-500 focus:ring-orange-500"
                                    >
                                        <option value="cash">Cash</option>
                                        <option value="bank">Bank Transfer</option>
                                        <option value="check">Check</option>
                                        <option value="gcash">GCash</option>
                                    </select>
                                </div>

                                <label class="flex items-center gap-2 cursor-pointer pt-1">
                                    <input
                                        type="checkbox"
                                        name="record_as_expense"
                                        value="1"
                                        checked
                                        class="rounded border-gray-300 text-orange-600 focus:ring-orange-500 h-4 w-4"
                                    />
                                    <span class="text-xs text-gray-700">Record as store expense in Profit &amp; Loss</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white rounded-xl shadow-xs p-5 border border-gray-100">
                        <x-input-label for="notes" value="Delivery Notes / Remarks" />
                        <textarea
                            id="notes"
                            name="notes"
                            rows="2"
                            placeholder="e.g. Delivered via forwarder, inspected and verified..."
                            class="w-full text-xs rounded-md border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                        ></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="items.length === 0"
                        class="w-full py-3 bg-orange-600 hover:bg-orange-700 disabled:opacity-50 text-white rounded-xl font-bold text-sm shadow-md transition flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span>✅</span>
                        <span>Confirm &amp; Receive Stocks</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Alpine.js Stock In Logic -->
    <script>
        function stockInManager(config) {
            return {
                availableProducts: config.availableProducts || [],
                initialSuppliers: config.initialSuppliers || [],
                selectedSupplierId: '',
                customSupplierName: '',
                searchQuery: '',
                searchResults: [],
                highlightedIndex: 0,
                items: [],
                paymentTerms: 'credit',
                downpayment: 0,
                totalAmount: 0,
                totalUnits: 0,

                onSupplierChange() {
                    // Placeholder for future supplier-specific filters
                },

                filterProducts() {
                    const q = this.searchQuery.trim().toLowerCase();
                    if (!q) {
                        this.searchResults = [];
                        return;
                    }

                    // Look for exact barcode match first
                    const exactBarcode = this.availableProducts.find(p => p.barcode && p.barcode.toLowerCase() === q);
                    if (exactBarcode) {
                        this.addItem(exactBarcode);
                        this.searchQuery = '';
                        this.searchResults = [];
                        return;
                    }

                    // Otherwise filter matches
                    this.searchResults = this.availableProducts.filter(p =>
                        (p.name && p.name.toLowerCase().includes(q)) ||
                        (p.sku && p.sku.toLowerCase().includes(q)) ||
                        (p.barcode && p.barcode.toLowerCase().includes(q))
                    ).slice(0, 8);
                    this.highlightedIndex = 0;
                },

                navigateResults(dir) {
                    if (this.searchResults.length === 0) return;
                    this.highlightedIndex = (this.highlightedIndex + dir + this.searchResults.length) % this.searchResults.length;
                },

                selectHighlightedOrFirst() {
                    if (this.searchResults.length > 0) {
                        this.addItem(this.searchResults[this.highlightedIndex]);
                        this.searchQuery = '';
                        this.searchResults = [];
                    }
                },

                addItem(prod) {
                    const existing = this.items.find(i => i.product_id === prod.id);
                    if (existing) {
                        existing.quantity += 1;
                    } else {
                        this.items.push({
                            product_id: prod.id,
                            name: prod.name,
                            sku: prod.sku,
                            unit: prod.unit,
                            current_stock: prod.current_stock,
                            quantity: 1,
                            unit_cost: prod.cost_price || 0,
                            update_cost_price: false
                        });
                    }
                    this.searchQuery = '';
                    this.searchResults = [];
                    this.recalculate();
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.recalculate();
                },

                recalculate() {
                    let total = 0;
                    let units = 0;
                    for (const item of this.items) {
                        const qty = Math.max(1, parseInt(item.quantity) || 1);
                        const cost = Math.max(0, parseFloat(item.unit_cost) || 0);
                        total += qty * cost;
                        units += qty;
                    }
                    this.totalAmount = total;
                    this.totalUnits = units;
                },

                validateForm(event) {
                    if (this.items.length === 0) {
                        alert('Please add at least one delivered item.');
                        event.preventDefault();
                        return false;
                    }
                    return true;
                }
            };
        }
    </script>
</x-app-layout>
