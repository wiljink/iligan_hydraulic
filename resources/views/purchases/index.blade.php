<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Stock In &amp; Deliveries</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Receive inventory deliveries, verify supplier delivery receipts (DR), and log supplier bills
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('purchases.export', request()->query()) }}"
                    class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-md text-xs font-semibold shadow-xs flex items-center gap-1.5 transition"
                >
                    <span>📊</span>
                    <span>Export Excel</span>
                </a>
                <a
                    href="{{ route('purchases.create') }}"
                    class="px-3.5 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-md text-xs font-semibold shadow-xs flex items-center gap-1.5 transition"
                >
                    <span>🚚</span>
                    <span>+ Receive Delivery (Stock In)</span>
                </a>
            </div>
        </div>
    </x-slot>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Deliveries This Month -->
        <div class="bg-white rounded-xl shadow-xs p-5 border-l-4 border-orange-500 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Deliveries This Month</span>
                <span class="w-8 h-8 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center text-sm font-bold">📦</span>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 tracking-tight">{{ number_format($monthDeliveriesCount) }}</p>
                <div class="flex items-center justify-between text-xs mt-2 pt-2 border-t border-gray-100 text-gray-500">
                    <span>Stock received</span>
                    <span class="text-orange-600 font-bold">{{ now()->format('F Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Total Delivery Value (This Month) -->
        <div class="bg-white rounded-xl shadow-xs p-5 border-l-4 border-emerald-500 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Received Value</span>
                <span class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">📈</span>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 tracking-tight">{{ money($monthDeliveriesTotal) }}</p>
                <div class="flex items-center justify-between text-xs mt-2 pt-2 border-t border-gray-100 text-gray-500">
                    <span>This month's purchases</span>
                    <span class="text-emerald-700 font-semibold">Replenished</span>
                </div>
            </div>
        </div>

        <!-- Deliveries on Credit (Pending A/P) -->
        <div class="bg-white rounded-xl shadow-xs p-5 border-l-4 border-amber-500 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Deliveries on Credit</span>
                <span class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">🧾</span>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 tracking-tight">{{ number_format($pendingCreditDeliveries) }}</p>
                <div class="flex items-center justify-between text-xs mt-2 pt-2 border-t border-gray-100 text-gray-500">
                    <span>Unsettled deliveries</span>
                    <a href="{{ route('payables.index') }}" class="text-amber-600 hover:underline font-bold">View A/P &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Outstanding Payable from Deliveries -->
        <div class="bg-white rounded-xl shadow-xs p-5 border-l-4 border-red-500 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Unpaid Balance to Suppliers</span>
                <span class="w-8 h-8 rounded-full bg-red-50 text-red-600 flex items-center justify-center text-sm font-bold">💳</span>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 tracking-tight">{{ money($pendingCreditAmount) }}</p>
                <div class="flex items-center justify-between text-xs mt-2 pt-2 border-t border-gray-100 text-gray-500">
                    <span>Balance to pay</span>
                    <span class="text-red-600 font-bold">Active Payables</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-xl shadow-xs p-4 mb-6 border border-gray-100">
        <form method="GET" action="{{ route('purchases.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <x-input-label for="search" value="Search DR # / PO # / Supplier" />
                <x-text-input
                    id="search"
                    name="search"
                    type="text"
                    value="{{ request('search') }}"
                    placeholder="e.g. DR-8812, Cebu Hydraulics..."
                    class="w-full text-xs"
                />
            </div>

            <div>
                <x-input-label for="supplier_id" value="Supplier" />
                <select
                    id="supplier_id"
                    name="supplier_id"
                    class="w-full rounded-md border-gray-300 text-xs py-2 bg-white text-gray-900 focus:border-orange-500 focus:ring-orange-500"
                >
                    <option value="">All Suppliers</option>
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="payment_status" value="Payment Status" />
                <select
                    id="payment_status"
                    name="payment_status"
                    class="w-full rounded-md border-gray-300 text-xs py-2 bg-white text-gray-900 focus:border-orange-500 focus:ring-orange-500"
                >
                    <option value="">All Payment Types</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid Immediately</option>
                    <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partially Paid</option>
                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid / On Credit</option>
                </select>
            </div>

            <div>
                <x-input-label for="date_from" value="Delivery Date From" />
                <x-text-input
                    id="date_from"
                    name="date_from"
                    type="date"
                    value="{{ request('date_from') }}"
                    class="w-full text-xs"
                />
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="flex-1 py-2 px-3 bg-gray-800 hover:bg-gray-900 text-white rounded-md text-xs font-semibold transition"
                >
                    Filter
                </button>
                @if (request()->hasAny(['search', 'supplier_id', 'payment_status', 'date_from', 'date_to']))
                    <a
                        href="{{ route('purchases.index') }}"
                        class="py-2 px-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md text-xs font-semibold transition"
                    >
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Deliveries Table -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-600 border-b">
                    <tr>
                        <th class="py-3 px-4 font-bold">Delivery # / PO</th>
                        <th class="py-3 px-4 font-bold">Supplier DR / Ref #</th>
                        <th class="py-3 px-4 font-bold">Date Received</th>
                        <th class="py-3 px-4 font-bold">Supplier / Vendor</th>
                        <th class="py-3 px-4 text-center font-bold">Items &amp; Units</th>
                        <th class="py-3 px-4 text-right font-bold">Delivery Total</th>
                        <th class="py-3 px-4 text-center font-bold">Payment Status</th>
                        <th class="py-3 px-4 font-bold">Received By</th>
                        <th class="py-3 px-4 text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($purchases as $purchase)
                        <tr class="hover:bg-gray-50/70 transition">
                            <td class="py-3 px-4 font-mono font-bold">
                                <a href="{{ route('purchases.show', $purchase) }}" class="text-orange-600 hover:underline">
                                    {{ $purchase->purchase_number }}
                                </a>
                            </td>
                            <td class="py-3 px-4 font-mono">
                                @if ($purchase->reference_number)
                                    <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-800 font-bold text-[11px]">
                                        {{ $purchase->reference_number }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-600">
                                {{ $purchase->receipt_date->format('M j, Y') }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-gray-900">{{ $purchase->supplier_name }}</span>
                                @if ($purchase->supplier && $purchase->supplier->phone)
                                    <span class="block text-[11px] text-gray-400">{{ $purchase->supplier->phone }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="font-bold text-gray-800">{{ $purchase->items->count() }} line(s)</span>
                                <span class="block text-[11px] text-gray-500">({{ $purchase->total_quantity }} total units)</span>
                            </td>
                            <td class="py-3 px-4 text-right font-black text-gray-900 text-sm">
                                {{ money($purchase->total_amount) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                    {{ $purchase->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $purchase->payment_status === 'partial' ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $purchase->payment_status === 'unpaid' ? 'bg-red-100 text-red-800' : '' }}
                                ">
                                    {{ $purchase->payment_status === 'unpaid' ? 'On Credit (Unpaid)' : $purchase->payment_status }}
                                </span>
                                @if ($purchase->accountPayable)
                                    <a
                                        href="{{ route('payables.index', ['search' => $purchase->accountPayable->bill_number]) }}"
                                        class="block text-[10px] text-orange-600 hover:underline mt-0.5 font-medium"
                                    >
                                        A/P Bill &rarr;
                                    </a>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-600">
                                {{ $purchase->user?->name ?? 'System' }}
                                <span class="block text-[10px] text-gray-400">{{ $purchase->branch?->name }}</span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a
                                        href="{{ route('purchases.show', $purchase) }}"
                                        class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-semibold transition"
                                        title="View Delivery Details"
                                    >
                                        View
                                    </a>
                                    <a
                                        href="{{ route('purchases.barcodes', $purchase) }}"
                                        class="px-2 py-1 bg-orange-50 hover:bg-orange-100 text-orange-700 rounded text-xs font-semibold transition flex items-center gap-1"
                                        title="Print Barcodes for Delivered Items"
                                    >
                                        <span>🖨️</span>
                                        <span>Barcodes</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-gray-400">
                                <span class="text-4xl block mb-2">🚚</span>
                                <p class="text-sm font-semibold text-gray-600">No stock deliveries recorded yet</p>
                                <p class="text-xs text-gray-400 mt-1 mb-4">When a supplier delivers inventory, record it here to update stock and link bills.</p>
                                <a
                                    href="{{ route('purchases.create') }}"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-md text-xs font-bold transition shadow-xs"
                                >
                                    <span>+ Receive First Delivery</span>
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($purchases->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
