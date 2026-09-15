<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Invoice {{ $sale->invoice_number }}</h2>
            <div class="flex gap-2 print:hidden">
                <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Print Receipt</button>
                @if (!$sale->isVoided() && auth()->user()->isManager())
                    <button type="button" onclick="document.getElementById('void-modal').classList.remove('hidden')" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md">Void Sale</button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm p-8 print:shadow-none">
        <div class="text-center mb-6">
            <h1 class="text-lg font-bold">{{ setting('company_name', config('app.name')) }}</h1>
            <p class="text-sm text-gray-500">{{ setting('company_address') }}</p>
            <p class="text-sm text-gray-500">{{ setting('company_phone') }}</p>
        </div>

        @if ($sale->isVoided())
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800 text-center">
                <strong>VOIDED</strong> by {{ $sale->voidedBy?->name }} on {{ $sale->voided_at?->format('M d, Y g:ia') }}<br>
                Reason: {{ $sale->void_reason }}
            </div>
        @endif

        <div class="flex justify-between text-sm mb-4">
            <div>
                <p><strong>Invoice:</strong> {{ $sale->invoice_number }}</p>
                <p><strong>Date:</strong> {{ $sale->created_at->format('M d, Y g:ia') }}</p>
                <p><strong>Cashier:</strong> {{ $sale->user?->name }}</p>
            </div>
            <div class="text-right">
                <p><strong>Customer:</strong> {{ $sale->customer?->name ?? 'Walk-in' }}</p>
                @if ($sale->customer?->vehicle_info)
                    <p><strong>Vehicle:</strong> {{ $sale->customer->vehicle_info }}</p>
                @endif
            </div>
        </div>

        <table class="w-full text-sm mb-4">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">Item</th>
                    <th class="py-2 text-right">Qty</th>
                    <th class="py-2 text-right">Price</th>
                    <th class="py-2 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr class="border-b last:border-0">
                        <td class="py-2">{{ $item->product_name }}<span class="text-xs text-gray-400 block">{{ $item->product_sku }}</span></td>
                        <td class="py-2 text-right">{{ $item->quantity }}</td>
                        <td class="py-2 text-right">{{ money($item->unit_price) }}</td>
                        <td class="py-2 text-right">{{ money($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="space-y-1 text-sm border-t pt-3">
            <div class="flex justify-between"><span>Subtotal</span><span>{{ money($sale->subtotal) }}</span></div>
            <div class="flex justify-between"><span>Discount</span><span>-{{ money($sale->discount) }}</span></div>
            <div class="flex justify-between"><span>Tax</span><span>{{ money($sale->tax) }}</span></div>
            <div class="flex justify-between text-lg font-bold border-t pt-2"><span>Total</span><span>{{ money($sale->total) }}</span></div>
            <div class="flex justify-between"><span>Paid ({{ str_replace('_', ' ', $sale->payment_method) }})</span><span>{{ money($sale->amount_paid) }}</span></div>
            <div class="flex justify-between"><span>Change</span><span>{{ money($sale->change_due) }}</span></div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">{{ setting('receipt_footer', 'Thank you for your business!') }}</p>
    </div>

    <div id="void-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 print:hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h3 class="font-semibold mb-3">Void Sale {{ $sale->invoice_number }}</h3>
            <p class="text-sm text-gray-500 mb-3">This will return all items to stock. This cannot be undone.</p>
            <form method="POST" action="{{ route('sales.void', $sale) }}">
                @csrf
                <textarea name="reason" rows="2" placeholder="Reason for voiding" class="w-full rounded-md border-gray-300 shadow-sm text-sm mb-3" required></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('void-modal').classList.add('hidden')" class="px-4 py-2 text-gray-500 text-sm">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md">Confirm Void</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
