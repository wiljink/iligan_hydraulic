<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Stock Transfers</h2>
            <a href="{{ route('transfers.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-md shadow-sm">
                <span>🔁</span> New Transfer
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
        <form method="GET" class="flex flex-wrap gap-2 mb-4">
            <select name="branch_id" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">All branches</option>
                @foreach ($branches as $b)
                    <option value="{{ $b->id }}" @selected(request('branch_id') == $b->id)>{{ $b->label }}</option>
                @endforeach
            </select>
            <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Filter</button>
        </form>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2">Transfer #</th>
                    <th class="py-2">Date</th>
                    <th class="py-2">From</th>
                    <th class="py-2">To</th>
                    <th class="py-2 text-right">Units</th>
                    <th class="py-2">By</th>
                    <th class="py-2 text-right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transfers as $transfer)
                    <tr class="border-b last:border-0">
                        <td class="py-2 font-mono">{{ $transfer->transfer_number }}</td>
                        <td class="py-2">{{ $transfer->transfer_date->format('M j, Y') }}</td>
                        <td class="py-2">{{ $transfer->fromBranch?->name }}</td>
                        <td class="py-2">{{ $transfer->toBranch?->name }}</td>
                        <td class="py-2 text-right">{{ (int) $transfer->total_quantity }}</td>
                        <td class="py-2">{{ $transfer->user?->name ?? 'System' }}</td>
                        <td class="py-2 text-right"><a href="{{ route('transfers.show', $transfer) }}" class="text-orange-600 hover:underline">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-400">No stock transfers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $transfers->links() }}</div>
    </div>
</x-app-layout>
