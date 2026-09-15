<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Expenses</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Record Expense</h3>
            <form method="POST" action="{{ route('expenses.store') }}" class="space-y-3">
                @csrf
                <div>
                    <x-input-label for="expense_date" value="Date" />
                    <x-text-input id="expense_date" type="date" name="expense_date" class="mt-1 block w-full" value="{{ old('expense_date', now()->toDateString()) }}" required />
                </div>
                <div>
                    <x-input-label for="category" value="Category" />
                    <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" required>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="description" value="Description" />
                    <x-text-input id="description" name="description" class="mt-1 block w-full" value="{{ old('description') }}" />
                </div>
                <div>
                    <x-input-label for="amount" value="Amount" />
                    <x-text-input id="amount" type="number" step="0.01" min="0" name="amount" class="mt-1 block w-full" value="{{ old('amount') }}" required />
                </div>
                <x-primary-button>Save Expense</x-primary-button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
                <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                <select name="category" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Filter</button>
            </form>

            <div class="mb-3 text-sm text-gray-500">Total: <span class="font-semibold text-gray-800">{{ money($total) }}</span></div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm" x-data="{ editing: null }">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2">Date</th>
                            <th class="py-2">Category</th>
                            <th class="py-2">Description</th>
                            <th class="py-2">Recorded By</th>
                            <th class="py-2 text-right">Amount</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expenses as $expense)
                            <tr class="border-b last:border-0" x-show="editing !== {{ $expense->id }}">
                                <td class="py-2">{{ $expense->expense_date->format('M d, Y') }}</td>
                                <td class="py-2">{{ $expense->category }}</td>
                                <td class="py-2 text-gray-500">{{ $expense->description }}</td>
                                <td class="py-2">{{ $expense->user?->name }}</td>
                                <td class="py-2 text-right">{{ money($expense->amount) }}</td>
                                <td class="py-2 text-right whitespace-nowrap space-x-2">
                                    <button type="button" @click="editing = {{ $expense->id }}" class="text-blue-600 hover:underline">Edit</button>
                                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="inline" onsubmit="return confirm('Delete this expense?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <tr x-show="editing === {{ $expense->id }}" x-cloak>
                                <td colspan="6" class="py-2">
                                    <form method="POST" action="{{ route('expenses.update', $expense) }}" class="grid grid-cols-2 sm:grid-cols-5 gap-2 items-center">
                                        @csrf @method('PUT')
                                        <input type="date" name="expense_date" value="{{ $expense->expense_date->toDateString() }}" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                        <select name="category" class="rounded-md border-gray-300 shadow-sm text-sm">
                                            @foreach ($categories as $cat)
                                                <option value="{{ $cat }}" @selected($expense->category === $cat)>{{ $cat }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="description" value="{{ $expense->description }}" placeholder="Description" class="rounded-md border-gray-300 shadow-sm text-sm">
                                        <input type="number" step="0.01" min="0" name="amount" value="{{ $expense->amount }}" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                        <div class="space-x-2">
                                            <button type="submit" class="text-green-600 text-sm hover:underline">Save</button>
                                            <button type="button" @click="editing = null" class="text-gray-500 text-sm hover:underline">Cancel</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-gray-400">No expenses recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $expenses->links() }}</div>
        </div>
    </div>
</x-app-layout>
