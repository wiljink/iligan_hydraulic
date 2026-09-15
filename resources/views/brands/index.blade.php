<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Brands</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Add Brand</h3>
            <form method="POST" action="{{ route('brands.store') }}" class="space-y-3">
                @csrf
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" required placeholder="e.g. Toyota, Denso, Parker" />
                </div>
                <x-primary-button>Add Brand</x-primary-button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5">
            <table class="w-full text-sm" x-data="{ editing: null }">
                <thead>
                    <tr class="text-left text-gray-500 border-b">
                        <th class="py-2">Name</th>
                        <th class="py-2">Products</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr class="border-b last:border-0" x-show="editing !== {{ $brand->id }}">
                            <td class="py-2">{{ $brand->name }}</td>
                            <td class="py-2">{{ $brand->products_count }}</td>
                            <td class="py-2 text-right space-x-2">
                                <button type="button" @click="editing = {{ $brand->id }}" class="text-blue-600 hover:underline">Edit</button>
                                <form method="POST" action="{{ route('brands.destroy', $brand) }}" class="inline" onsubmit="return confirm('Delete this brand?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr x-show="editing === {{ $brand->id }}" x-cloak>
                            <td colspan="3" class="py-2">
                                <form method="POST" action="{{ route('brands.update', $brand) }}" class="flex items-center gap-2">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $brand->name }}" class="rounded-md border-gray-300 shadow-sm text-sm flex-1" required>
                                    <button type="submit" class="text-green-600 text-sm hover:underline">Save</button>
                                    <button type="button" @click="editing = null" class="text-gray-500 text-sm hover:underline">Cancel</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-gray-400">No brands yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $brands->links() }}</div>
        </div>
    </div>
</x-app-layout>
