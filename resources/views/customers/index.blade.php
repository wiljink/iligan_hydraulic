<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Customers</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Add Customer</h3>
            <form method="POST" action="{{ route('customers.store') }}" class="space-y-3">
                @csrf
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="phone" value="Phone" />
                    <x-text-input id="phone" name="phone" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" type="email" name="email" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="vehicle_info" value="Vehicle Info" />
                    <x-text-input id="vehicle_info" name="vehicle_info" class="mt-1 block w-full" placeholder="e.g. Isuzu Elf 2018" />
                </div>
                <div>
                    <x-input-label for="address" value="Address" />
                    <textarea id="address" name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"></textarea>
                </div>
                <x-primary-button>Add Customer</x-primary-button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
            <form method="GET" class="mb-4">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or phone..." class="rounded-md border-gray-300 shadow-sm text-sm w-full">
            </form>
            <table class="w-full text-sm" x-data="{ editing: null }">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Name</th>
                        <th class="py-2">Phone</th>
                        <th class="py-2">Vehicle</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr class="border-b last:border-0" x-show="editing !== {{ $customer->id }}">
                            <td class="py-2">{{ $customer->name }}</td>
                            <td class="py-2">{{ $customer->phone }}</td>
                            <td class="py-2">{{ $customer->vehicle_info }}</td>
                            <td class="py-2 text-right space-x-2 whitespace-nowrap">
                                <button type="button" @click="editing = {{ $customer->id }}" class="text-blue-600 hover:underline">Edit</button>
                                <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline" onsubmit="return confirm('Delete this customer?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr x-show="editing === {{ $customer->id }}" x-cloak>
                            <td colspan="4" class="py-2">
                                <form method="POST" action="{{ route('customers.update', $customer) }}" class="grid grid-cols-2 gap-2">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $customer->name }}" placeholder="Name" class="rounded-md border-gray-300 shadow-sm text-sm" required>
                                    <input type="text" name="phone" value="{{ $customer->phone }}" placeholder="Phone" class="rounded-md border-gray-300 shadow-sm text-sm">
                                    <input type="email" name="email" value="{{ $customer->email }}" placeholder="Email" class="rounded-md border-gray-300 shadow-sm text-sm">
                                    <input type="text" name="vehicle_info" value="{{ $customer->vehicle_info }}" placeholder="Vehicle" class="rounded-md border-gray-300 shadow-sm text-sm">
                                    <input type="text" name="address" value="{{ $customer->address }}" placeholder="Address" class="rounded-md border-gray-300 shadow-sm text-sm col-span-2">
                                    <div class="col-span-2 space-x-2">
                                        <button type="submit" class="text-green-600 text-sm hover:underline">Save</button>
                                        <button type="button" @click="editing = null" class="text-gray-500 text-sm hover:underline">Cancel</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-gray-400">No customers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $customers->links() }}</div>
        </div>
    </div>
</x-app-layout>
