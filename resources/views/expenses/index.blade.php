<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Expenses &amp; Vouchers</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-5" x-data="{ type: '{{ old('voucher_type', 'petty_cash') }}' }">
            <h3 class="font-semibold mb-3">New Voucher</h3>
            <form method="POST" action="{{ route('expenses.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-2">
                    @foreach (\App\Models\Expense::VOUCHER_TYPES as $value => $label)
                        <label class="flex items-center justify-center gap-2 rounded-md border px-3 py-2 text-sm cursor-pointer"
                               :class="type === '{{ $value }}' ? 'border-orange-500 bg-orange-50 text-orange-700 font-medium' : 'border-gray-300 text-gray-600'">
                            <input type="radio" name="voucher_type" value="{{ $value }}" x-model="type" class="sr-only">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400" x-show="type === 'petty_cash'">For small cash payments from the petty cash fund.</p>
                <p class="text-xs text-gray-400" x-show="type === 'check'" x-cloak>For payments made by check.</p>

                <div>
                    <x-input-label for="expense_date" value="Date *" />
                    <x-text-input id="expense_date" type="date" name="expense_date" class="mt-1 block w-full" value="{{ old('expense_date', now()->toDateString()) }}" required />
                </div>
                <div>
                    <x-input-label for="payee" value="Payee (paid to) *" />
                    <x-text-input id="payee" name="payee" class="mt-1 block w-full" value="{{ old('payee') }}" placeholder="e.g. CEPALCO, Juan dela Cruz" required />
                </div>
                <div>
                    <x-input-label for="payee_address" value="Payee Address" />
                    <x-text-input id="payee_address" name="payee_address" class="mt-1 block w-full" value="{{ old('payee_address') }}" />
                </div>
                <div>
                    <x-input-label for="category" value="Category *" />
                    <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="description" value="Particulars / Description" />
                    <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('description') }}</textarea>
                </div>
                <div>
                    <x-input-label for="amount" value="Amount *" />
                    <x-text-input id="amount" type="number" step="0.01" min="0" name="amount" class="mt-1 block w-full" value="{{ old('amount') }}" required />
                </div>

                <template x-if="type === 'check'">
                    <div class="space-y-3 rounded-md bg-gray-50 p-3">
                        <div>
                            <x-input-label for="bank_name" value="Bank *" />
                            <x-text-input id="bank_name" name="bank_name" class="mt-1 block w-full" value="{{ old('bank_name') }}" placeholder="e.g. BDO, Metrobank" required />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <x-input-label for="check_number" value="Check No. *" />
                                <x-text-input id="check_number" name="check_number" class="mt-1 block w-full" value="{{ old('check_number') }}" required />
                            </div>
                            <div>
                                <x-input-label for="check_date" value="Check Date *" />
                                <x-text-input id="check_date" type="date" name="check_date" class="mt-1 block w-full" value="{{ old('check_date', now()->toDateString()) }}" required />
                            </div>
                        </div>
                    </div>
                </template>

                <div>
                    <x-input-label for="approved_by" value="Approved By" />
                    <x-text-input id="approved_by" name="approved_by" class="mt-1 block w-full" value="{{ old('approved_by') }}" placeholder="Name of approving officer" />
                </div>
                <x-primary-button>Issue Voucher</x-primary-button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5">
            <form method="GET" class="grid grid-cols-2 xl:grid-cols-6 gap-3 mb-4">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Voucher, payee, check no..." class="rounded-md border-gray-300 shadow-sm text-sm col-span-2">
                <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                <select name="voucher_type" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">All Vouchers</option>
                    @foreach (\App\Models\Expense::VOUCHER_TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(request('voucher_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="category" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md col-span-2 xl:col-span-6 xl:justify-self-end">Filter</button>
            </form>

            <div class="mb-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500">
                <span>Total: <span class="font-semibold text-gray-800">{{ money($total) }}</span></span>
                <span>Petty cash: <span class="font-semibold text-gray-800">{{ money($totalsByType['petty_cash'] ?? 0) }}</span></span>
                <span>Check: <span class="font-semibold text-gray-800">{{ money($totalsByType['check'] ?? 0) }}</span></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm" x-data="{ editing: null }">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2">Voucher</th>
                            <th class="py-2">Date</th>
                            <th class="py-2">Payee</th>
                            <th class="py-2">Category</th>
                            <th class="py-2 text-right">Amount</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expenses as $expense)
                            <tr class="border-b last:border-0" x-show="editing !== {{ $expense->id }}">
                                <td class="py-2">
                                    <a href="{{ route('expenses.show', $expense) }}" class="text-orange-600 hover:underline font-mono text-xs whitespace-nowrap">{{ $expense->voucher_number }}</a>
                                    <span class="block text-xs text-gray-400">{{ $expense->isCheck() ? 'Check'.($expense->check_number ? ' #'.$expense->check_number : '') : 'Petty cash' }}</span>
                                </td>
                                <td class="py-2 whitespace-nowrap">{{ $expense->expense_date->format('M d, Y') }}</td>
                                <td class="py-2">
                                    {{ $expense->payee ?: '—' }}
                                    @if ($expense->description)
                                        <span class="block text-xs text-gray-400 line-clamp-1">{{ $expense->description }}</span>
                                    @endif
                                </td>
                                <td class="py-2">{{ $expense->category }}</td>
                                <td class="py-2 text-right whitespace-nowrap">{{ money($expense->amount) }}</td>
                                <td class="py-2 text-right whitespace-nowrap space-x-2">
                                    <a href="{{ route('expenses.show', $expense) }}" class="text-gray-700 hover:underline">Print</a>
                                    <button type="button" @click="editing = {{ $expense->id }}" class="text-blue-600 hover:underline">Edit</button>
                                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="inline" onsubmit="return confirm('Delete this voucher?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <tr x-show="editing === {{ $expense->id }}" x-cloak>
                                <td colspan="6" class="py-2">
                                    <form method="POST" action="{{ route('expenses.update', $expense) }}" class="grid grid-cols-2 sm:grid-cols-4 gap-2 items-center">
                                        @csrf @method('PUT')
                                        <input type="date" name="expense_date" value="{{ $expense->expense_date->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                        <input type="text" name="payee" value="{{ $expense->payee }}" placeholder="Payee" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                        <select name="category" class="rounded-md border-gray-300 shadow-sm text-sm">
                                            @foreach ($categories as $cat)
                                                <option value="{{ $cat }}" @selected($expense->category === $cat)>{{ $cat }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" step="0.01" min="0" name="amount" value="{{ $expense->amount }}" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                        <input type="text" name="payee_address" value="{{ $expense->payee_address }}" placeholder="Payee address" class="rounded-md border-gray-300 shadow-sm text-sm col-span-2">
                                        <input type="text" name="description" value="{{ $expense->description }}" placeholder="Particulars" class="rounded-md border-gray-300 shadow-sm text-sm col-span-2">
                                        <input type="text" name="approved_by" value="{{ $expense->approved_by }}" placeholder="Approved by" class="rounded-md border-gray-300 shadow-sm text-sm col-span-2">
                                        @if ($expense->isCheck())
                                            <input type="text" name="bank_name" value="{{ $expense->bank_name }}" placeholder="Bank" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                            <input type="text" name="check_number" value="{{ $expense->check_number }}" placeholder="Check no." class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                            <input type="date" name="check_date" value="{{ $expense->check_date?->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                        @endif
                                        <div class="space-x-2">
                                            <button type="submit" class="text-green-600 text-sm hover:underline">Save</button>
                                            <button type="button" @click="editing = null" class="text-gray-500 text-sm hover:underline">Cancel</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-gray-400">No vouchers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $expenses->links() }}</div>
        </div>
    </div>
</x-app-layout>
