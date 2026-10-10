<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Accounts Payable (A/P)</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Track supplier invoices, vendor bills, payment due dates &amp; balances
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('payables.export', request()->query()) }}"
                    class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-xs font-semibold shadow-xs flex items-center gap-1.5 transition"
                >
                    <span>📊</span>
                    <span>Export Excel</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div x-data="{
        showCreateModal: false,
        payingBill: null,
        paymentAmount: '',
        paymentMethod: 'cash',
        paymentRef: '',
        recordAsExpense: false,

        openPayment(bill) {
            this.payingBill = bill;
            this.paymentAmount = bill.balance;
            this.paymentMethod = 'cash';
            this.paymentRef = '';
            this.recordAsExpense = false;
        }
    }">
        <!-- Top Metrics & Quick Action -->
        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Outstanding Payables -->
            <div class="bg-white rounded-xl shadow-xs p-5 border-l-4 border-amber-500">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Total Outstanding Payables</p>
                <p class="text-2xl font-black text-gray-900 mt-1">{{ money($totalOutstanding) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $pendingCount }} pending bill(s)</p>
            </div>

            <!-- Overdue Bills -->
            <div class="bg-white rounded-xl shadow-xs p-5 border-l-4 {{ $overdueCount > 0 ? 'border-red-500 bg-red-50/20' : 'border-gray-300' }}">
                <p class="text-xs font-bold uppercase tracking-wider {{ $overdueCount > 0 ? 'text-red-700' : 'text-gray-500' }}">Overdue Bills</p>
                <p class="text-2xl font-black {{ $overdueCount > 0 ? 'text-red-600' : 'text-gray-900' }} mt-1">{{ money($totalOverdue) }}</p>
                <p class="text-xs {{ $overdueCount > 0 ? 'text-red-600 font-semibold' : 'text-gray-500' }} mt-1">
                    {{ $overdueCount }} bill(s) past due date
                </p>
            </div>

            <!-- New Bill Button Card -->
            <div class="sm:col-span-1 lg:col-span-2 bg-gradient-to-r from-orange-500 to-amber-600 rounded-xl shadow-xs p-5 text-white flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold">Add Supplier Bill</h3>
                    <p class="text-xs text-orange-100 mt-0.5">Record incoming invoices from parts suppliers or utility creditors</p>
                </div>
                <button
                    type="button"
                    @click="showCreateModal = true"
                    class="px-4 py-2 bg-white text-orange-700 hover:bg-orange-50 font-bold text-xs rounded-lg shadow-sm transition cursor-pointer"
                >
                    + New Payable Bill
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white rounded-xl shadow-xs p-4 mb-6 border border-gray-100">
            <form method="GET" action="{{ route('payables.index') }}" class="flex flex-wrap items-center justify-between gap-4">
                <!-- Status Pills -->
                <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
                    <a
                        href="{{ route('payables.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}"
                        class="px-3 py-1.5 rounded-lg transition {{ $statusFilter === 'pending' ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                    >
                        Pending / Due
                    </a>
                    <a
                        href="{{ route('payables.index', array_merge(request()->except('status', 'page'), ['status' => 'overdue'])) }}"
                        class="px-3 py-1.5 rounded-lg transition {{ $statusFilter === 'overdue' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                    >
                        ⚠️ Overdue ({{ $overdueCount }})
                    </a>
                    <a
                        href="{{ route('payables.index', array_merge(request()->except('status', 'page'), ['status' => 'unpaid'])) }}"
                        class="px-3 py-1.5 rounded-lg transition {{ $statusFilter === 'unpaid' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                    >
                        Unpaid
                    </a>
                    <a
                        href="{{ route('payables.index', array_merge(request()->except('status', 'page'), ['status' => 'partially_paid'])) }}"
                        class="px-3 py-1.5 rounded-lg transition {{ $statusFilter === 'partially_paid' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                    >
                        Partially Paid
                    </a>
                    <a
                        href="{{ route('payables.index', array_merge(request()->except('status', 'page'), ['status' => 'paid'])) }}"
                        class="px-3 py-1.5 rounded-lg transition {{ $statusFilter === 'paid' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                    >
                        Fully Settled
                    </a>
                    <a
                        href="{{ route('payables.index', array_merge(request()->except('status', 'page'), ['status' => 'all'])) }}"
                        class="px-3 py-1.5 rounded-lg transition {{ $statusFilter === 'all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                    >
                        All
                    </a>
                </div>

                <!-- Filters & Search -->
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        name="supplier_id"
                        onchange="this.form.submit()"
                        class="rounded-md border-gray-300 text-xs py-1.5 focus:border-orange-500 focus:ring-orange-500 bg-white"
                    >
                        <option value="">All Suppliers</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>

                    <div class="relative">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Search bill #, vendor..."
                            class="rounded-md border-gray-300 text-xs py-1.5 pl-7 w-48 sm:w-56 focus:border-orange-500 focus:ring-orange-500 bg-white"
                        >
                        <span class="absolute left-2.5 top-2 text-gray-400 text-xs">🔍</span>
                    </div>

                    @if (request()->hasAny(['supplier_id', 'q', 'status']))
                        <a href="{{ route('payables.index') }}" class="text-xs text-gray-500 hover:text-gray-800 font-semibold underline">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Payables Table -->
        <div class="bg-white rounded-xl shadow-xs border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-gray-600 border-b">
                        <tr>
                            <th class="py-3 px-4">Bill #</th>
                            <th class="py-3 px-4">Vendor / Supplier</th>
                            <th class="py-3 px-4">Bill Date</th>
                            <th class="py-3 px-4">Due Date</th>
                            <th class="py-3 px-4 text-right">Total Amount</th>
                            <th class="py-3 px-4 text-right">Amount Paid</th>
                            <th class="py-3 px-4 text-right font-bold text-gray-900">Balance Due</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payables as $payable)
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="py-3 px-4 font-mono font-bold text-gray-900">
                                    {{ $payable->bill_number }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-gray-900">{{ $payable->vendor_name }}</div>
                                    @if ($payable->notes)
                                        <div class="text-[11px] text-gray-500 truncate max-w-xs">{{ $payable->notes }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-gray-600">
                                    {{ $payable->bill_date?->format('M j, Y') }}
                                </td>
                                <td class="py-3 px-4">
                                    @if ($payable->due_date)
                                        <div class="text-gray-800 font-medium">{{ $payable->due_date->format('M j, Y') }}</div>
                                        @if ($payable->status !== 'paid')
                                            @if ($payable->is_overdue)
                                                <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">
                                                    {{ $payable->days_overdue }} days overdue
                                                </span>
                                            @elseif ($payable->due_date->isToday())
                                                <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                                    Due Today
                                                </span>
                                            @else
                                                <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] text-gray-500 font-medium">
                                                    Due in {{ now()->startOfDay()->diffInDays($payable->due_date) }} days
                                                </span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-gray-400">No due date</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right text-gray-700">
                                    {{ money($payable->amount) }}
                                </td>
                                <td class="py-3 px-4 text-right text-gray-500">
                                    {{ money($payable->amount_paid) }}
                                </td>
                                <td class="py-3 px-4 text-right font-black text-sm {{ $payable->balance > 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                    {{ money($payable->balance) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        {{ $payable->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                        {{ $payable->status === 'partially_paid' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $payable->status === 'unpaid' ? 'bg-red-100 text-red-800' : '' }}
                                    ">
                                        {{ str_replace('_', ' ', $payable->status) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($payable->balance > 0)
                                            <button
                                                type="button"
                                                @click="openPayment({
                                                    id: {{ $payable->id }},
                                                    bill_number: '{{ $payable->bill_number }}',
                                                    vendor_name: '{{ addslashes($payable->vendor_name) }}',
                                                    amount: {{ $payable->amount }},
                                                    amount_paid: {{ $payable->amount_paid }},
                                                    balance: {{ $payable->balance }}
                                                })"
                                                class="px-2.5 py-1 bg-orange-600 hover:bg-orange-700 text-white rounded font-semibold text-xs transition cursor-pointer"
                                            >
                                                Pay
                                            </button>
                                        @endif
                                        <form
                                            method="POST"
                                            action="{{ route('payables.destroy', $payable) }}"
                                            onsubmit="return confirm('Are you sure you want to delete bill {{ $payable->bill_number }}?');"
                                            class="inline"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="text-gray-400 hover:text-red-600 text-xs p-1"
                                                title="Delete Bill"
                                            >
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-10 text-center text-gray-400">
                                    <span class="text-3xl block mb-2">📋</span>
                                    <p class="font-medium text-gray-600">No accounts payable records found.</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Click &ldquo;+ New Payable Bill&rdquo; to add a supplier bill or expense invoice.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($payables->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $payables->links() }}
                </div>
            @endif
        </div>

        <!-- Create Payable Bill Modal -->
        <div
            x-show="showCreateModal"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/50"
            @keydown.escape.window="showCreateModal = false"
        >
            <div
                class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 text-left"
                @click.outside="showCreateModal = false"
            >
                <div class="flex items-center justify-between border-b pb-3 mb-4">
                    <h3 class="text-base font-bold text-gray-900">New Payable Bill / Invoice</h3>
                    <button type="button" @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>

                <form method="POST" action="{{ route('payables.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="supplier_id" value="Supplier / Vendor" />
                        <select
                            id="supplier_id"
                            name="supplier_id"
                            class="w-full rounded-md border-gray-300 text-xs py-2 bg-white text-gray-900 focus:border-orange-500 focus:ring-orange-500"
                        >
                            <option value="">-- Choose Existing Supplier --</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="vendor_name" value="Or Vendor Name (if not in supplier list)" />
                        <x-text-input
                            id="vendor_name"
                            name="vendor_name"
                            placeholder="e.g. Davao Industrial Tubing or Electricity Utility"
                            class="w-full"
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="bill_number" value="Bill / Invoice Number *" />
                            <x-text-input
                                id="bill_number"
                                name="bill_number"
                                placeholder="e.g. INV-9048 or PO-2026-01"
                                class="w-full font-mono font-bold"
                                required
                            />
                        </div>
                        <div>
                            <x-input-label for="amount" value="Bill Amount (₱) *" />
                            <x-text-input
                                id="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                name="amount"
                                placeholder="0.00"
                                class="w-full font-bold"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="bill_date" value="Bill Date *" />
                            <x-text-input
                                id="bill_date"
                                type="date"
                                name="bill_date"
                                value="{{ now()->toDateString() }}"
                                class="w-full"
                                required
                            />
                        </div>
                        <div>
                            <x-input-label for="due_date" value="Payment Due Date" />
                            <x-text-input
                                id="due_date"
                                type="date"
                                name="due_date"
                                value="{{ now()->addDays(30)->toDateString() }}"
                                class="w-full"
                            />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="notes" value="Notes / Description" />
                        <textarea
                            id="notes"
                            name="notes"
                            rows="2"
                            placeholder="e.g. Shipment of 50pcs hydraulic hoses 1/2in"
                            class="w-full rounded-md border-gray-300 text-xs py-2 bg-white text-gray-900 focus:border-orange-500 focus:ring-orange-500"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 hover:bg-gray-50 cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-md text-xs font-semibold shadow-xs cursor-pointer"
                        >
                            Save Payable Bill
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Record Payment Modal -->
        <div
            x-show="payingBill !== null"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/50"
            @keydown.escape.window="payingBill = null"
        >
            <div
                class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 text-left"
                @click.outside="payingBill = null"
            >
                <div class="flex items-center justify-between border-b pb-3 mb-4">
                    <h3 class="text-base font-bold text-gray-900">Record Payment</h3>
                    <button type="button" @click="payingBill = null" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">&times;</button>
                </div>

                <template x-if="payingBill">
                    <form
                        method="POST"
                        :action="'{{ url('/payables') }}/' + payingBill.id + '/record-payment'"
                        class="space-y-4"
                    >
                        @csrf
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200 text-xs">
                            <div class="flex justify-between font-semibold text-gray-800">
                                <span>Bill #: <span class="font-mono" x-text="payingBill.bill_number"></span></span>
                                <span class="text-gray-500" x-text="payingBill.vendor_name"></span>
                            </div>
                            <div class="flex justify-between mt-2 pt-2 border-t border-gray-200">
                                <span class="text-gray-500">Total: ₱<span x-text="payingBill.amount.toFixed(2)"></span></span>
                                <span class="font-bold text-red-600">Balance Due: ₱<span x-text="payingBill.balance.toFixed(2)"></span></span>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="pay_amount" value="Payment Amount (₱) *" />
                            <input
                                id="pay_amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="payingBill.balance"
                                name="amount"
                                x-model="paymentAmount"
                                class="w-full rounded-md border-gray-300 text-sm font-bold py-2 bg-white text-gray-900 focus:border-orange-500 focus:ring-orange-500"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="pay_method" value="Payment Method" />
                                <select
                                    id="pay_method"
                                    name="payment_method"
                                    x-model="paymentMethod"
                                    class="w-full rounded-md border-gray-300 text-xs py-2 bg-white text-gray-900 focus:border-orange-500 focus:ring-orange-500"
                                >
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="check">Check</option>
                                    <option value="gcash">GCash</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="pay_ref" value="Reference / Check #" />
                                <x-text-input
                                    id="pay_ref"
                                    name="reference_number"
                                    x-model="paymentRef"
                                    placeholder="Check #, Ref #"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="pay_notes" value="Notes" />
                            <x-text-input
                                id="pay_notes"
                                name="notes"
                                placeholder="e.g. Paid via Metrobank online"
                                class="w-full"
                            />
                        </div>

                        <div class="pt-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-gray-700 font-medium">
                                <input
                                    type="checkbox"
                                    name="record_as_expense"
                                    value="1"
                                    x-model="recordAsExpense"
                                    class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                                >
                                <span>Also log this payment into <strong>Expenses</strong></span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t">
                            <button
                                type="button"
                                @click="payingBill = null"
                                class="px-4 py-2 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 hover:bg-gray-50 cursor-pointer"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md text-xs font-semibold shadow-xs cursor-pointer"
                            >
                                Confirm Payment
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
