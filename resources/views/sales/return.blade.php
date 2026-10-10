<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Return Items &mdash; {{ $sale->invoice_number }}</h2>
            <a href="{{ route('sales.show', $sale) }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md">Back to Invoice</a>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-sm p-6">
        <p class="text-sm text-gray-500 mb-4">
            Customer: <strong class="text-gray-800">{{ $sale->customer?->name ?? 'Walk-in' }}</strong> &bull;
            Current balance: <strong class="text-gray-800">{{ money($sale->balance()) }}</strong><br>
            Returned items go back into stock. A Credit Memo is issued; the credit first reduces the invoice balance and anything beyond it is refunded to the customer.
        </p>

        <form method="POST" action="{{ route('sales.return.store', $sale) }}" class="space-y-4">
            @csrf
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Item</th>
                        <th class="py-2 text-right">Sold</th>
                        <th class="py-2 text-right">Already returned</th>
                        <th class="py-2 text-right w-32">Return qty</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        @php $returnable = $item->quantity - ($returned[$item->id] ?? 0); @endphp
                        <tr class="border-b last:border-0">
                            <td class="py-2">{{ $item->product_name }}<span class="text-xs text-gray-400 block">{{ $item->product_sku }} &bull; {{ money($item->unit_price) }} each</span></td>
                            <td class="py-2 text-right">{{ $item->quantity }}</td>
                            <td class="py-2 text-right">{{ $returned[$item->id] ?? 0 }}</td>
                            <td class="py-2 text-right">
                                @if ($returnable > 0)
                                    <input type="number" name="quantities[{{ $item->id }}]" min="0" max="{{ $returnable }}" value="{{ old('quantities.'.$item->id, 0) }}" class="w-24 rounded-md border-gray-300 shadow-sm text-sm text-right">
                                @else
                                    <span class="text-gray-400">All returned</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div>
                <x-input-label for="reason" value="Reason for return *" />
                <x-text-input id="reason" name="reason" class="mt-1 block w-full" value="{{ old('reason') }}" placeholder="e.g. Wrong part, defective" required />
            </div>
            <div>
                <x-input-label for="approved_by" value="Approved by *" />
                <x-text-input id="approved_by" name="approved_by" class="mt-1 block w-full" value="{{ old('approved_by') }}" placeholder="Name of the person who approved this return" required />
            </div>

            <x-primary-button>Return Items &amp; Issue Credit Memo</x-primary-button>
        </form>
    </div>
</x-app-layout>
