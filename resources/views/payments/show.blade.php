<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Collection Receipt {{ $payment->receipt_number }}</h2>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('sales.show', $payment->sale) }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md">Back to Invoice</a>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Print Receipt</button>
            </div>
        </div>
    </x-slot>

    @php $inWords = amount_in_words($payment->amount); @endphp

    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-sm p-8 print:shadow-none">
        @include('documents._header', ['title' => 'Collection Receipt'])

        @include('documents._parties', [
            'customer' => $payment->customer,
            'meta' => [
                'CR No.' => $payment->receipt_number,
                'Date' => $payment->created_at->format('M d, Y g:ia'),
            ],
        ])

        <div class="border rounded-md p-4 text-sm space-y-3 my-6">
            <p>
                Received from <strong>{{ $payment->customer?->name ?? 'Walk-in customer' }}</strong>
                the sum of <strong>{{ money($payment->amount) }}</strong>
                @if ($inWords)
                    <span class="block text-gray-600 italic">({{ $inWords }})</span>
                @endif
            </p>
            <p>
                as payment for
                {{ $payment->sale->isCharge() ? 'Charge Invoice' : 'Sales Invoice' }}
                <strong>{{ $payment->sale->invoice_number }}</strong>
                dated {{ $payment->sale->created_at->format('M d, Y') }}.
            </p>
        </div>

        <div class="space-y-1 text-sm border-t pt-3">
            <div class="flex justify-between"><span>Form of payment</span><span>{{ $payment->methodLabel() }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</span></div>
            <div class="flex justify-between"><span>Invoice total</span><span>{{ money($payment->sale->netTotal()) }}</span></div>
            <div class="flex justify-between text-lg font-bold border-t pt-2"><span>Amount received</span><span>{{ money($payment->amount) }}</span></div>
            <div class="flex justify-between"><span>Current balance on invoice</span><span>{{ money($payment->sale->balance()) }}</span></div>
        </div>

        <div class="mt-12 grid grid-cols-2 gap-8 text-xs text-gray-500">
            <div></div>
            <div class="border-t border-gray-400 pt-1 text-center">
                Received by: {{ $payment->user?->name }}
            </div>
        </div>
    </div>
</x-app-layout>
