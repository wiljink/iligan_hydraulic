<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Suppliers</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Add Supplier</h3>
            <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-3">
                @csrf
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="address" value="Address" />
                    <textarea id="address" name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"></textarea>
                </div>
                <div>
                    <x-input-label for="tin_number" value="TIN Number" />
                    <x-text-input id="tin_number" name="tin_number" class="mt-1 block w-full" placeholder="e.g. 123-456-789-000" />
                </div>
                <div>
                    <x-input-label for="phone" value="Telephone Number" />
                    <x-text-input id="phone" name="phone" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="contact_person" value="Contact Person" />
                    <x-text-input id="contact_person" name="contact_person" class="mt-1 block w-full" />
                </div>
                <x-primary-button>Add Supplier</x-primary-button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
            <table class="w-full text-sm" x-data="{ editing: null }">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Name</th>
                        <th class="py-2">TIN</th>
                        <th class="py-2">Contact</th>
                        <th class="py-2">Telephone</th>
                        <th class="py-2">Products</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr class="border-b last:border-0" x-show="editing !== {{ $supplier->id }}">
                            <td class="py-2">{{ $supplier->name }}</td>
                            <td class="py-2">{{ $supplier->tin_number }}</td>
                            <td class="py-2">{{ $supplier->contact_person }}</td>
                            <td class="py-2">{{ $supplier->phone }}</td>
                            <td class="py-2">{{ $supplier->products_count }}</td>
                            <td class="py-2 text-right space-x-2 whitespace-nowrap">
                                <button type="button" @click="editing = {{ $supplier->id }}" class="text-blue-600 hover:underline">Edit</button>
                                <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="inline" onsubmit="return confirm('Delete this supplier?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr x-show="editing === {{ $supplier->id }}" x-cloak>
                            <td colspan="6" class="py-2">
                                <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="grid grid-cols-2 gap-2">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $supplier->name }}" placeholder="Name" class="rounded-md border-gray-300 shadow-sm text-sm col-span-2" required>
                                    <input type="text" name="address" value="{{ $supplier->address }}" placeholder="Address" class="rounded-md border-gray-300 shadow-sm text-sm col-span-2">
                                    <input type="text" name="tin_number" value="{{ $supplier->tin_number }}" placeholder="TIN Number" class="rounded-md border-gray-300 shadow-sm text-sm">
                                    <input type="text" name="phone" value="{{ $supplier->phone }}" placeholder="Telephone Number" class="rounded-md border-gray-300 shadow-sm text-sm">
                                    <input type="email" name="email" value="{{ $supplier->email }}" placeholder="Email" class="rounded-md border-gray-300 shadow-sm text-sm">
                                    <input type="text" name="contact_person" value="{{ $supplier->contact_person }}" placeholder="Contact Person" class="rounded-md border-gray-300 shadow-sm text-sm">
                                    <div class="col-span-2 space-x-2">
                                        <button type="submit" class="text-green-600 text-sm hover:underline">Save</button>
                                        <button type="button" @click="editing = null" class="text-gray-500 text-sm hover:underline">Cancel</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-gray-400">No suppliers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $suppliers->links() }}</div>
        </div>
    </div>
</x-app-layout>
