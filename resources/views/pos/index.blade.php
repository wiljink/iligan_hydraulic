<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Point of Sale <span class="text-sm font-normal text-gray-500">&middot; {{ \App\Models\Branch::current()?->name }}</span></h2>
    </x-slot>

    <div x-data="pos()" x-init="init()" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5">
            <div class="relative mb-4">
                <input
                    type="text"
                    x-ref="scanInput"
                    x-model="search"
                    @keydown.enter.prevent="scan()"
                    @input.debounce.300ms="doSearch()"
                    placeholder="Scan barcode or type product name / SKU / part no. / OEM no..."
                    autofocus
                    class="w-full rounded-md border-gray-300 shadow-sm text-lg py-3"
                >
                <div x-show="searchResults.length" x-cloak class="absolute z-10 mt-1 w-full bg-white border rounded-md shadow-lg max-h-72 overflow-y-auto">
                    <template x-for="product in searchResults" :key="product.id">
                        <button type="button" @click="addItem(product)" class="w-full text-left px-4 py-2 hover:bg-orange-50 border-b last:border-0 flex justify-between text-sm">
                            <span>
                                <span class="font-medium" x-text="product.name"></span>
                                <span class="text-gray-400 block text-xs" x-text="product.sku + (product.barcode ? ' / ' + product.barcode : '') + (product.part_number ? ' / Part: ' + product.part_number : '')"></span>
                                <span x-show="product.oem_number" class="text-gray-400 block text-xs truncate max-w-md" x-text="'OEM: ' + product.oem_number"></span>
                            </span>
                            <span class="text-right">
                                <span class="block font-medium" x-text="currency(product.selling_price)"></span>
                                <span class="text-xs text-gray-400" x-text="'Stock: ' + product.current_stock"></span>
                            </span>
                        </button>
                    </template>
                </div>
            </div>

            <div x-show="errorMessage" x-cloak class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-2 text-sm text-red-800" x-text="errorMessage"></div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2">Product</th>
                            <th class="py-2 w-24">Qty</th>
                            <th class="py-2 text-right w-28">Price</th>
                            <th class="py-2 text-right w-28">Subtotal</th>
                            <th class="py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in items" :key="item.product_id">
                            <tr class="border-b last:border-0">
                                <td class="py-2">
                                    <span class="font-medium" x-text="item.name"></span>
                                    <span class="text-xs text-gray-400 block" x-text="item.sku"></span>
                                </td>
                                <td class="py-2">
                                    <input type="number" min="1" :max="item.stock" x-model.number="item.quantity" @change="clampQty(item)" class="w-16 rounded-md border-gray-300 shadow-sm text-sm">
                                </td>
                                <td class="py-2 text-right" x-text="currency(item.price)"></td>
                                <td class="py-2 text-right font-medium" x-text="currency(item.price * item.quantity)"></td>
                                <td class="py-2 text-right">
                                    <button type="button" @click="items.splice(index, 1)" class="text-red-500">&times;</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="items.length === 0">
                            <td colspan="5" class="py-8 text-center text-gray-400">Cart is empty. Scan or search to add items.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5 h-fit">
            <h3 class="font-semibold mb-3">Checkout</h3>

            <div class="mb-3">
                <label class="text-xs text-gray-500">Customer</label>
                <select x-model="customerId" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">Walk-in Customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} @if($customer->phone) ({{ $customer->phone }}) @endif</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1 text-sm border-t pt-3">
                <div class="flex justify-between"><span>Subtotal</span><span x-text="currency(subtotal)"></span></div>
                <div class="flex justify-between items-center">
                    <span>Discount</span>
                    <input type="number" min="0" step="0.01" x-model.number="discount" class="w-24 rounded-md border-gray-300 shadow-sm text-sm text-right">
                </div>
                <div class="flex justify-between"><span>Tax ({{ number_format($taxRate * 100, 0) }}%)</span><span x-text="currency(tax)"></span></div>
                <div class="flex justify-between text-lg font-bold border-t pt-2"><span>Total</span><span x-text="currency(total)"></span></div>
            </div>

            <div class="mt-4 space-y-2">
                <label class="text-xs text-gray-500">Payment Method</label>
                <select x-model="paymentMethod" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="cash">Cash</option>
                    <option value="gcash">GCash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="card">Card</option>
                    <option value="credit">Credit / Charge to Account</option>
                </select>

                <template x-if="paymentMethod !== 'credit'">
                    <div>
                        <label class="text-xs text-gray-500">Amount Paid</label>
                        <input type="number" min="0" step="0.01" x-model.number="amountPaid" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <p class="text-xs text-gray-500 mt-1">Change: <span class="font-medium" x-text="currency(Math.max(amountPaid - total, 0))"></span></p>
                    </div>
                </template>

                <template x-if="paymentMethod !== 'cash'">
                    <div>
                        <label class="text-xs text-gray-500">Reference #</label>
                        <input type="text" x-model="paymentReference" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                </template>
            </div>

            <button type="button" @click="checkout()" :disabled="items.length === 0 || processing"
                class="w-full mt-4 px-4 py-3 bg-orange-500 text-white font-semibold rounded-md hover:bg-orange-600 disabled:opacity-50">
                <span x-show="!processing">Complete Sale</span>
                <span x-show="processing" x-cloak>Processing...</span>
            </button>
            <button type="button" @click="items = []; discount = 0" class="w-full mt-2 px-4 py-2 text-gray-500 text-sm">Clear Cart</button>
        </div>
    </div>

    <script>
function pos() {
    return {
        items: [],
        search: '',
        searchResults: [],
        customerId: '',
        discount: 0,
        paymentMethod: 'cash',
        paymentReference: '',
        amountPaid: 0,
        processing: false,
        errorMessage: '',
        taxRate: {{ (float) $taxRate }},
        taxInclusive: {{ $taxInclusive ? 'true' : 'false' }},

        init() {
            this.$watch('total', (val) => { if (this.paymentMethod !== 'credit') this.amountPaid = val; });
        },

        get subtotal() {
            return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        },
        get taxableAmount() {
            return Math.max(this.subtotal - (this.discount || 0), 0);
        },
        get tax() {
            return this.taxInclusive ? 0 : Math.round(this.taxableAmount * this.taxRate * 100) / 100;
        },
        get total() {
            return Math.round((this.taxableAmount + this.tax) * 100) / 100;
        },

        currency(value) {
            return '{{ $currencySymbol }}' + Number(value || 0).toFixed(2);
        },

        clampQty(item) {
            if (item.quantity < 1) item.quantity = 1;
            if (item.quantity > item.stock) item.quantity = item.stock;
        },

        addItem(product) {
            const existing = this.items.find(i => i.product_id === product.id);
            if (existing) {
                if (existing.quantity < product.current_stock) existing.quantity++;
            } else {
                this.items.push({
                    product_id: product.id,
                    name: product.name,
                    sku: product.sku,
                    price: parseFloat(product.selling_price || product.price),
                    quantity: 1,
                    stock: product.current_stock ?? product.stock ?? 9999,
                });
            }
            this.search = '';
            this.searchResults = [];
            this.errorMessage = '';
            this.$refs.scanInput.focus();
        },

        async scan() {
            const code = this.search.trim();
            if (!code) return;

            try {
                const res = await fetch(`{{ route('pos.lookup') }}?code=${encodeURIComponent(code)}`);
                if (!res.ok) {
                    // Not a barcode/SKU: fall back to the text search (name, part no., OEM no.).
                    await this.doSearch();
                    if (this.searchResults.length === 1) {
                        this.addItem(this.searchResults[0]);
                    } else if (this.searchResults.length === 0) {
                        this.errorMessage = 'Product not found for: ' + code;
                        this.search = '';
                    }
                    return;
                }
                const product = await res.json();
                this.addItem(product);
            } catch (e) {
                this.errorMessage = 'Lookup failed. Please try again.';
            }
        },

        async doSearch() {
            const term = this.search.trim();
            if (term.length < 2) { this.searchResults = []; return; }

            const res = await fetch(`{{ route('pos.search') }}?term=${encodeURIComponent(term)}`);
            this.searchResults = res.ok ? await res.json() : [];
        },

        async checkout() {
            if (this.items.length === 0) return;
            this.processing = true;
            this.errorMessage = '';

            try {
                const res = await fetch(`{{ route('pos.checkout') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        items: this.items.map(i => ({ product_id: i.product_id, quantity: i.quantity })),
                        customer_id: this.customerId || null,
                        discount: this.discount || 0,
                        payment_method: this.paymentMethod,
                        payment_reference: this.paymentReference || null,
                        amount_paid: this.paymentMethod === 'credit' ? 0 : this.amountPaid,
                    }),
                });

                const data = await res.json();

                if (!res.ok) {
                    this.errorMessage = data.message || 'Checkout failed.';
                    this.processing = false;
                    return;
                }

                window.location.href = data.redirect;
            } catch (e) {
                this.errorMessage = 'Checkout failed. Please try again.';
                this.processing = false;
            }
        },
    };
}
    </script>
</x-app-layout>
