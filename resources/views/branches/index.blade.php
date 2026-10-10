<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Branches &amp; Warehouses</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Add Branch / Warehouse</h3>
            <form method="POST" action="{{ route('branches.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <x-input-label for="code" value="Code" />
                        <x-text-input id="code" name="code" class="mt-1 block w-full uppercase" maxlength="20" placeholder="BR2" required />
                    </div>
                    <div class="col-span-2">
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" placeholder="e.g. Tibanga Branch" required />
                    </div>
                </div>
                <div>
                    <x-input-label for="type" value="Type" />
                    <select id="type" name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="store">Store (sells via POS)</option>
                        <option value="warehouse">Warehouse (storage only)</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" name="phone" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="address" value="Address" />
                    <x-text-input id="address" name="address" class="mt-1 block w-full" />
                </div>
                <x-primary-button>Add Branch</x-primary-button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
            <table class="w-full text-sm" x-data="{ editing: null }">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Code</th>
                        <th class="py-2">Name</th>
                        <th class="py-2">Type</th>
                        <th class="py-2 text-right">Units on Hand</th>
                        <th class="py-2 text-right">Users</th>
                        <th class="py-2">Status</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($branches as $branch)
                        <tr class="border-b last:border-0" x-show="editing !== {{ $branch->id }}">
                            <td class="py-2 font-mono">{{ $branch->code }}</td>
                            <td class="py-2">
                                {{ $branch->name }}
                                @if ($branch->address)
                                    <span class="block text-xs text-gray-400">{{ $branch->address }}</span>
                                @endif
                            </td>
                            <td class="py-2 capitalize">{{ $branch->type }}</td>
                            <td class="py-2 text-right">{{ number_format((int) $branch->stock_units) }}</td>
                            <td class="py-2 text-right">{{ $branch->users_count }}</td>
                            <td class="py-2">
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $branch->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-2 text-right space-x-2 whitespace-nowrap">
                                <button type="button" @click="editing = {{ $branch->id }}" class="text-blue-600 hover:underline">Edit</button>
                                <form method="POST" action="{{ route('branches.destroy', $branch) }}" class="inline" onsubmit="return confirm('Delete this branch?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr x-show="editing === {{ $branch->id }}" x-cloak>
                            <td colspan="7" class="py-2">
                                <form method="POST" action="{{ route('branches.update', $branch) }}" class="grid grid-cols-2 gap-2">
                                    @csrf @method('PUT')
                                    <input type="text" name="code" value="{{ $branch->code }}" placeholder="Code" maxlength="20" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                    <input type="text" name="name" value="{{ $branch->name }}" placeholder="Name" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                    <select name="type" class="rounded-md border-gray-300 shadow-sm text-sm">
                                        <option value="store" @selected($branch->type === 'store')>Store</option>
                                        <option value="warehouse" @selected($branch->type === 'warehouse')>Warehouse</option>
                                    </select>
                                    <input type="text" name="phone" value="{{ $branch->phone }}" placeholder="Phone" class="rounded-md border-gray-300 shadow-sm text-sm">
                                    <input type="text" name="address" value="{{ $branch->address }}" placeholder="Address" class="rounded-md border-gray-300 shadow-sm text-sm col-span-2">
                                    <label class="flex items-center gap-2 text-sm col-span-2">
                                        <input type="checkbox" name="is_active" value="1" @checked($branch->is_active) class="rounded border-gray-300"> Active
                                    </label>
                                    <div class="col-span-2 space-x-2">
                                        <button type="submit" class="text-green-600 text-sm hover:underline">Save</button>
                                        <button type="button" @click="editing = null" class="text-gray-500 text-sm hover:underline">Cancel</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-4 text-center text-gray-400">No branches yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $branches->links() }}</div>
        </div>
    </div>
</x-app-layout>
