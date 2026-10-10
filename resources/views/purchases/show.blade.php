<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4 print:hidden">
            <div class="flex items-center gap-3">
                <a href="{{ route('purchases.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">
                    &larr; Deliveries
                </a>
                <span class="text-gray-300">/</span>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 font-mono">{{ $purchase->purchase_number }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Delivered on {{ $purchase->receipt_date->format('F j, Y') }} &bull; Supplier: {{ $purchase->supplier_name }}
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    onclick="window.print()"
                    class="px-3.5 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-md text-xs font-semibold shadow-xs flex items-center gap-1.5 transition cursor-pointer"
                >
                    <span>🖨️</span>
                    <span>Print Receipt</span>
                </button>
                <a
                    href="{{ route('purchases.barcodes', $purchase) }}"
                    class="px-3.5 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-md text-xs font-semibold shadow-xs flex items-center gap-1.5 transition"
                >
                    <span>🏷️</span>
                    <span>Print Barcodes</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        @if (session('success'))
            <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between print:hidden">
                <div class="flex items-center gap-2">
                    <span>✅</span>
                    <span>{{ session('success') }}</span>
                </div>
                <a href="{{ route('purchases.barcodes', $purchase) }}" class="underline hover:text-emerald-900 font-bold">
                    Print Barcode Labels &rarr;
                </a>
            </div>
        @endif

        <!-- Main Delivery Slip Card -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-100 p-6 md:p-8 space-y-6">
            <!-- Header section -->
            <div class="flex flex-wrap items-start justify-between gap-4 border-b pb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-black text-gray-900 font-mono tracking-tight">{{ $purchase->purchase_number }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">
                            {{ $purchase->status }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Stock Receiving Receipt &bull; Recorded on {{ $purchase->created_at->format('M j, Y • g:ia') }}
                    </p>
                </div>

                <div class="text-right">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Total Delivery Value</span>
                    <p class="text-2xl font-black text-orange-600">{{ money($purchase->total_amount) }}</p>
                </div>
            </div>

            <!-- Delivery Details Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs bg-gray-50 rounded-lg p-4 border border-gray-100">
                <div>
                    <span class="text-gray-400 uppercase tracking-wider block font-semibold text-[10px]">Supplier / Vendor</span>
                    <strong class="text-gray-900 text-sm block mt-0.5">{{ $purchase->supplier_name }}</strong>
                    @if ($purchase->supplier && $purchase->supplier->phone)
                        <span class="text-gray-500 text-[11px]">{{ $purchase->supplier->phone }}</span>
                    @endif
                </div>

                <div>
                    <span class="text-gray-400 uppercase tracking-wider block font-semibold text-[10px]">Supplier DR / Ref #</span>
                    <strong class="text-gray-900 font-mono text-sm block mt-0.5">
                        {{ $purchase->reference_number ?: 'N/A' }}
                    </strong>
                </div>

                <div>
                    <span class="text-gray-400 uppercase tracking-wider block font-semibold text-[10px]">Date Received</span>
                    <strong class="text-gray-900 text-sm block mt-0.5">
                        {{ $purchase->receipt_date->format('M j, Y') }}
                    </strong>
                </div>

                <div>
                    <span class="text-gray-400 uppercase tracking-wider block font-semibold text-[10px]">Received By</span>
                    <strong class="text-gray-900 text-sm block mt-0.5">
                        {{ $purchase->user?->name ?? 'System' }}
                    </strong>
                </div>

                <div>
                    <span class="text-gray-400 uppercase tracking-wider block font-semibold text-[10px]">Received At</span>
                    <strong class="text-gray-900 text-sm block mt-0.5">
                        {{ $purchase->branch?->label ?? 'N/A' }}
                    </strong>
                </div>
            </div>

            <!-- Accounts Payable / Payment Status Banner -->
            <div class="rounded-lg p-4 text-xs flex flex-wrap items-center justify-between gap-3
                {{ $purchase->payment_status === 'paid' ? 'bg-emerald-50/70 border border-emerald-200' : 'bg-amber-50/70 border border-amber-200' }}
            ">
                <div class="flex items-center gap-3">
                    <span class="text-xl">{{ $purchase->payment_status === 'paid' ? '💵' : '🧾' }}</span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-900">
                                Payment Terms:
                                <span class="uppercase">
                                    {{ $purchase->payment_status === 'unpaid' ? 'Charge on Credit' : $purchase->payment_status }}
                                </span>
                            </span>
                            @if ($purchase->due_date)
                                <span class="text-[11px] text-gray-600">
                                    (Due: {{ $purchase->due_date->format('M j, Y') }})
                                </span>
                            @endif
                        </div>
                        <p class="text-[11px] text-gray-500 mt-0.5">
                            Amount Paid: <strong class="text-gray-800">{{ money($purchase->amount_paid) }}</strong> &bull;
                            Balance Due: <strong class="{{ $purchase->balance > 0 ? 'text-red-600' : 'text-emerald-700' }}">{{ money($purchase->balance) }}</strong>
                        </p>
                    </div>
                </div>

                @if ($purchase->accountPayable)
                    <div class="print:hidden">
                        <a
                            href="{{ route('payables.index', ['search' => $purchase->accountPayable->bill_number]) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-md font-bold text-xs transition shadow-xs"
                        >
                            <span>🧾</span>
                            <span>Manage A/P Bill ({{ $purchase->accountPayable->bill_number }}) &rarr;</span>
                        </a>
                    </div>
                @endif
            </div>

            @if ($purchase->notes)
                <div class="text-xs bg-gray-50 rounded-lg p-3 text-gray-600">
                    <span class="font-bold text-gray-700 block mb-0.5">Notes:</span>
                    {{ $purchase->notes }}
                </div>
            @endif

            <!-- Delivered Items Table -->
            <div>
                <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-3">Items Received in this Delivery</h4>
                <div class="overflow-x-auto border border-gray-100 rounded-lg">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-gray-50 text-gray-600 border-b">
                            <tr>
                                <th class="py-2.5 px-3">#</th>
                                <th class="py-2.5 px-3">Product Name</th>
                                <th class="py-2.5 px-3">SKU / Barcode</th>
                                <th class="py-2.5 px-3 text-center">Delivered Qty</th>
                                <th class="py-2.5 px-3 text-right">Unit Cost</th>
                                <th class="py-2.5 px-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($purchase->items as $idx => $item)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="py-2.5 px-3 text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                    <td class="py-2.5 px-3 font-bold text-gray-900">
                                        @if ($item->product)
                                            <a href="{{ route('products.edit', $item->product) }}" class="hover:text-orange-600 hover:underline">
                                                {{ $item->product_name }}
                                            </a>
                                        @else
                                            {{ $item->product_name }}
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-gray-500">
                                        {{ $item->product_sku ?: ($item->product?->barcode ?? '—') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-bold text-gray-900">
                                        {{ $item->quantity }} {{ $item->product?->unit ?? 'pcs' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-medium text-gray-700">
                                        {{ money($item->unit_cost) }}
                                        @if ($item->update_cost_price)
                                            <span class="block text-[10px] text-emerald-600">Sync'd Master</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-black text-gray-900">
                                        {{ money($item->subtotal) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t text-xs font-bold text-gray-900">
                            <tr>
                                <td colspan="3" class="py-3 px-3 text-right">Totals:</td>
                                <td class="py-3 px-3 text-center">{{ $purchase->total_quantity }} units</td>
                                <td class="py-3 px-3"></td>
                                <td class="py-3 px-3 text-right text-orange-600 text-sm font-black">{{ money($purchase->total_amount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Printable Footer Signatures -->
            <div class="hidden print:grid grid-cols-2 gap-8 pt-12 text-xs">
                <div class="border-t border-gray-400 pt-2 text-center">
                    <p class="font-bold text-gray-900">Delivered By (Driver / Supplier Rep)</p>
                    <p class="text-gray-400 mt-1">Signature over printed name</p>
                </div>
                <div class="border-t border-gray-400 pt-2 text-center">
                    <p class="font-bold text-gray-900">Received &amp; Checked By</p>
                    <p class="text-gray-400 mt-1">{{ $purchase->user?->name ?? 'Authorized Staff' }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
