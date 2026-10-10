<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">{{ $customer->name }}</h2>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('customers.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md">All Customers</a>
                <button onclick="window.print()" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Print Statement</button>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-5 text-sm">
            <h3 class="font-semibold mb-3">Customer Details</h3>
            <dl class="space-y-2">
                <div><dt class="text-xs text-gray-500">Name</dt><dd>{{ $customer->name }}</dd></div>
                <div><dt class="text-xs text-gray-500">Address</dt><dd>{{ $customer->address ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gray-500">TIN Number</dt><dd>{{ $customer->tin_number ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gray-500">Telephone Number</dt><dd>{{ $customer->phone ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gray-500">Email</dt><dd>{{ $customer->email ?: '—' }}</dd></div>
                <div><dt class="text-xs text-gray-500">Contact Person</dt><dd>{{ $customer->contact_person ?: '—' }}</dd></div>
            </dl>
        </div>

        <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-4 gap-4 content-start">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-gray-500">Total Purchases</p>
                <p class="text-lg font-bold">{{ money($summary['purchases']) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-gray-500">Returns</p>
                <p class="text-lg font-bold text-amber-700">{{ money($summary['returns']) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-gray-500">Total Paid</p>
                <p class="text-lg font-bold text-green-700">{{ money($summary['paid']) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-gray-500">Outstanding Balance</p>
                <p class="text-lg font-bold {{ $summary['balance'] > 0 ? 'text-red-600' : '' }}">{{ money($summary['balance']) }}</p>
                <p class="text-xs text-gray-400">{{ $summary['open_invoices'] }} open invoice(s)</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-5 mb-6 overflow-x-auto">
        <h3 class="font-semibold mb-3">Invoices</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2">Date</th>
                    <th class="py-2">Invoice</th>
                    <th class="py-2">Type</th>
                    <th class="py-2 text-right">Total</th>
                    <th class="py-2 text-right">Paid</th>
                    <th class="py-2 text-right">Returns</th>
                    <th class="py-2 text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr class="border-b last:border-0 {{ $sale->isVoided() ? 'text-gray-400 line-through' : '' }}">
                        <td class="py-2">{{ $sale->created_at->format('M d, Y') }}</td>
                        <td class="py-2"><a href="{{ route('sales.show', $sale) }}" class="text-orange-600 hover:underline">{{ $sale->invoice_number }}</a></td>
                        <td class="py-2">{{ $sale->isVoided() ? 'Voided' : $sale->documentTitle() }}</td>
                        <td class="py-2 text-right">{{ money($sale->total) }}</td>
                        <td class="py-2 text-right">{{ money(min($sale->amount_paid, $sale->total) - $sale->refunded_amount) }}</td>
                        <td class="py-2 text-right">{{ $sale->returned_amount > 0 ? '-'.money($sale->returned_amount) : '—' }}</td>
                        <td class="py-2 text-right font-medium {{ $sale->balance() > 0 ? 'text-red-600' : '' }}">{{ money($sale->balance()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-4 text-center text-gray-400">No invoices yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
            <h3 class="font-semibold mb-3">Payments (Collection Receipts)</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Date</th>
                        <th class="py-2">CR No.</th>
                        <th class="py-2">Invoice</th>
                        <th class="py-2">Method</th>
                        <th class="py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr class="border-b last:border-0">
                            <td class="py-2">{{ $payment->created_at->format('M d, Y') }}</td>
                            <td class="py-2"><a href="{{ route('payments.show', $payment) }}" class="text-orange-600 hover:underline">{{ $payment->receipt_number }}</a></td>
                            <td class="py-2">{{ $payment->sale->invoice_number }}</td>
                            <td class="py-2">{{ $payment->methodLabel() }}</td>
                            <td class="py-2 text-right">{{ money($payment->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-gray-400">No payments recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
            <h3 class="font-semibold mb-3">Returns (Credit Memos)</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Date</th>
                        <th class="py-2">CM No.</th>
                        <th class="py-2">Invoice</th>
                        <th class="py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($creditMemos as $memo)
                        <tr class="border-b last:border-0">
                            <td class="py-2">{{ $memo->created_at->format('M d, Y') }}</td>
                            <td class="py-2"><a href="{{ route('credit-memos.show', $memo) }}" class="text-orange-600 hover:underline">{{ $memo->memo_number }}</a></td>
                            <td class="py-2">{{ $memo->sale->invoice_number }}</td>
                            <td class="py-2 text-right">{{ money($memo->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-gray-400">No returns.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
