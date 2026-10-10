<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Stock Transfer {{ $transfer->transfer_number }}</h2>
            <div class="flex gap-2 print:hidden">
                <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Print</button>
                <a href="{{ route('transfers.index') }}" class="px-4 py-2 text-gray-500 text-sm">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-4xl">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-6">
            <div>
                <span class="text-gray-400 uppercase text-[10px] font-semibold block">From</span>
                <strong>{{ $transfer->fromBranch?->label }}</strong>
            </div>
            <div>
                <span class="text-gray-400 uppercase text-[10px] font-semibold block">To</span>
                <strong>{{ $transfer->toBranch?->label }}</strong>
            </div>
            <div>
                <span class="text-gray-400 uppercase text-[10px] font-semibold block">Date</span>
                <strong>{{ $transfer->transfer_date->format('M j, Y') }}</strong>
            </div>
            <div>
                <span class="text-gray-400 uppercase text-[10px] font-semibold block">Transferred By</span>
                <strong>{{ $transfer->user?->name ?? 'System' }}</strong>
            </div>
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2">SKU</th>
                    <th class="py-2">Product</th>
                    <th class="py-2 text-right">Quantity</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transfer->items as $item)
                    <tr class="border-b last:border-0">
                        <td class="py-2 font-mono">{{ $item->product_sku }}</td>
                        <td class="py-2">{{ $item->product_name }}</td>
                        <td class="py-2 text-right">{{ $item->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-semibold border-t">
                    <td colspan="2" class="py-2 text-right">Total units</td>
                    <td class="py-2 text-right">{{ $transfer->items->sum('quantity') }}</td>
                </tr>
            </tfoot>
        </table>

        @if ($transfer->notes)
            <p class="mt-4 text-sm text-gray-600"><strong>Notes:</strong> {{ $transfer->notes }}</p>
        @endif
    </div>
</x-app-layout>
