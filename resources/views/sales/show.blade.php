<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">{{ $sale->documentTitle() }} {{ $sale->invoice_number }}</h2>
            <div class="flex gap-2 print:hidden">
                <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Print {{ $sale->documentTitle() }}</button>
                @if (!$sale->isVoided() && auth()->user()->can('sales.return'))
                    <a href="{{ route('sales.return.create', $sale) }}" class="px-4 py-2 bg-amber-500 text-white text-sm rounded-md">Return Items</a>
                @endif
                @if (!$sale->isVoided() && $sale->creditMemos->isEmpty() && auth()->user()->can('sales.void'))
                    <button type="button" onclick="document.getElementById('void-modal').classList.remove('hidden')" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md">Void Sale</button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm p-8 print:shadow-none">
        @include('documents._header', ['title' => $sale->documentTitle()])

        @if ($sale->isVoided())
            <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800 text-center">
                <strong>VOIDED</strong> by {{ $sale->voidedBy?->name }} on {{ $sale->voided_at?->format('M d, Y g:ia') }}<br>
                Reason: {{ $sale->void_reason }}
            </div>
        @endif

        @include('documents._parties', [
            'customer' => $sale->customer,
            'meta' => [
                ($sale->isCharge() ? 'Charge Invoice No.' : 'Sales Invoice No.') => $sale->invoice_number,
                'Date' => $sale->created_at->format('M d, Y g:ia'),
            ],
        ])

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
            @if ($sale->isCharge())
                <div class="flex justify-between"><span>Total paid</span><span>{{ money(min($sale->amount_paid, $sale->total)) }}</span></div>
            @else
                <div class="flex justify-between"><span>Paid ({{ str_replace('_', ' ', $sale->payment_method) }})</span><span>{{ money($sale->amount_paid) }}</span></div>
                <div class="flex justify-between"><span>Change</span><span>{{ money($sale->change_due) }}</span></div>
            @endif
            @if ($sale->returned_amount > 0)
                <div class="flex justify-between text-amber-700"><span>Returns (credit memos)</span><span>-{{ money($sale->returned_amount) }}</span></div>
                @if ($sale->refunded_amount > 0)
                    <div class="flex justify-between text-amber-700"><span>Refunded to customer</span><span>{{ money($sale->refunded_amount) }}</span></div>
                @endif
            @endif
            @if ($sale->isCharge() || $sale->balance() > 0)
                <div class="flex justify-between font-semibold {{ $sale->balance() > 0 ? 'text-red-600' : 'text-green-700' }}"><span>Balance due</span><span>{{ money($sale->balance()) }}</span></div>
            @endif
        </div>

        @if ($sale->isCharge())
            <div class="mt-10 grid-cols-2 gap-8 text-xs text-gray-500 hidden print:grid">
                <div class="border-t border-gray-400 pt-1 text-center">Received the above items in good order &mdash; Customer signature</div>
                <div class="border-t border-gray-400 pt-1 text-center">Authorized signature</div>
            </div>
        @endif

        @if ($sale->balance() > 0)
            @php $balance = $sale->balance(); @endphp
            <div class="mt-4 border-t pt-4 print:hidden">
                <p class="text-sm font-medium mb-2">Collect payment &mdash; a Collection Receipt will be issued</p>
                <form method="POST" action="{{ route('sales.record-payment', $sale) }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="number" step="0.01" min="0.01" max="{{ $balance }}" name="amount" value="{{ $balance }}" class="w-32 rounded-md border-gray-300 shadow-sm text-sm">
                    <select name="payment_method" class="rounded-md border-gray-300 shadow-sm text-sm">
                        @foreach (\App\Models\Payment::METHODS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="reference" placeholder="Ref / check no. (optional)" class="w-48 rounded-md border-gray-300 shadow-sm text-sm">
                    <button type="submit" class="px-3 py-1.5 bg-gray-800 text-white text-sm rounded-md">Record Payment</button>
                </form>
            </div>
        @endif

        @php $paidAtSale = $sale->isCharge() ? $sale->paidAtSale() : 0; @endphp
        @if ($sale->payments->isNotEmpty() || $paidAtSale > 0)
            <div class="mt-6 border-t pt-4 print:hidden">
                <h3 class="text-sm font-semibold mb-2">Payment History</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-1">Date</th>
                            <th class="py-1">Collection Receipt</th>
                            <th class="py-1">Method</th>
                            <th class="py-1 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($paidAtSale > 0)
                            <tr class="border-b">
                                <td class="py-1">{{ $sale->created_at->format('M d, Y') }}</td>
                                <td class="py-1 text-gray-400">Paid at sale</td>
                                <td class="py-1">&mdash;</td>
                                <td class="py-1 text-right">{{ money($paidAtSale) }}</td>
                            </tr>
                        @endif
                        @foreach ($sale->payments as $payment)
                            <tr class="border-b last:border-0">
                                <td class="py-1">{{ $payment->created_at->format('M d, Y g:ia') }}</td>
                                <td class="py-1"><a href="{{ route('payments.show', $payment) }}" class="text-orange-600 hover:underline">{{ $payment->receipt_number }}</a></td>
                                <td class="py-1">{{ $payment->methodLabel() }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</td>
                                <td class="py-1 text-right">{{ money($payment->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($sale->creditMemos->isNotEmpty())
            <div class="mt-6 border-t pt-4 print:hidden">
                <h3 class="text-sm font-semibold mb-2">Returns / Credit Memos</h3>
                <table class="w-full text-sm">
                    <tbody>
                        @foreach ($sale->creditMemos as $memo)
                            <tr class="border-b last:border-0">
                                <td class="py-1">{{ $memo->created_at->format('M d, Y') }}</td>
                                <td class="py-1"><a href="{{ route('credit-memos.show', $memo) }}" class="text-orange-600 hover:underline">{{ $memo->memo_number }}</a></td>
                                <td class="py-1 text-gray-500">{{ $memo->reason }}</td>
                                <td class="py-1 text-right">-{{ money($memo->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

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
