<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Credit Memo {{ $creditMemo->memo_number }}</h2>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('sales.show', $creditMemo->sale) }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md">Back to Invoice</a>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Print Credit Memo</button>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm p-8 print:shadow-none">
        @include('documents._header', ['title' => 'Credit Memo'])

        @include('documents._parties', [
            'customer' => $creditMemo->customer,
            'meta' => [
                'CM No.' => $creditMemo->memo_number,
                'Date' => $creditMemo->created_at->format('M d, Y g:ia'),
                'Ref. Invoice' => $creditMemo->sale->invoice_number,
            ],
        ])

        <p class="text-sm mb-4"><strong>Reason for return:</strong> {{ $creditMemo->reason }}</p>

        <table class="w-full text-sm mb-4">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">Item Returned</th>
                    <th class="py-2 text-right">Qty</th>
                    <th class="py-2 text-right">Unit Price</th>
                    <th class="py-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($creditMemo->items as $item)
                    <tr class="border-b last:border-0">
                        <td class="py-2">{{ $item->product_name }}<span class="text-xs text-gray-400 block">{{ $item->product_sku }}</span></td>
                        <td class="py-2 text-right">{{ $item->quantity }}</td>
                        <td class="py-2 text-right">{{ money($item->unit_price) }}</td>
                        <td class="py-2 text-right">{{ money($item->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="space-y-1 text-sm border-t pt-3">
            <div class="flex justify-between text-lg font-bold"><span>Total credit</span><span>{{ money($creditMemo->total) }}</span></div>
            @if ($creditMemo->applied_to_balance > 0)
                <div class="flex justify-between"><span>Applied to invoice balance</span><span>{{ money($creditMemo->applied_to_balance) }}</span></div>
            @endif
            @if ($creditMemo->refunded > 0)
                <div class="flex justify-between"><span>Refunded to customer</span><span>{{ money($creditMemo->refunded) }}</span></div>
            @endif
        </div>
        <p class="text-xs text-gray-400 mt-2">Unit prices include each line's share of the invoice discount and tax.</p>

        <div class="mt-12 grid grid-cols-3 gap-6 text-xs text-gray-500 text-center">
            <div>
                <p class="font-medium text-gray-800 h-4">{{ $creditMemo->user?->name }}</p>
                <div class="border-t border-gray-400 pt-1 mt-1">Prepared by</div>
            </div>
            <div>
                <p class="font-medium text-gray-800 h-4">{{ $creditMemo->approved_by }}</p>
                <div class="border-t border-gray-400 pt-1 mt-1">Approved by</div>
            </div>
            <div>
                <p class="font-medium text-gray-800 h-4">{{ $creditMemo->customer?->name }}</p>
                <div class="border-t border-gray-400 pt-1 mt-1">Customer signature over printed name</div>
            </div>
        </div>
    </div>
</x-app-layout>
