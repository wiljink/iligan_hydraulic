<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Add Product</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-4xl">
        <form method="POST" action="{{ route('products.store') }}">
            @csrf
            @include('products._form')
            <div class="mt-6 flex gap-3">
                <x-primary-button>Save Product</x-primary-button>
                <a href="{{ route('products.index') }}" class="px-4 py-2 text-gray-500 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
