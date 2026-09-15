<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Import Products</h2>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-2">1. Download Template</h3>
            <p class="text-sm text-gray-500 mb-4">Get a spreadsheet template with the required columns and instructions.</p>
            <div class="flex gap-3">
                <a href="{{ route('imports.template') }}" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md">Blank Template</a>
                <a href="{{ route('imports.template', ['with_products' => 1]) }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md">Export Current Catalog</a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="font-semibold mb-2">2. Upload &amp; Import</h3>
            <p class="text-sm text-gray-500 mb-4">Upload a filled-in .xlsx, .xls, or .csv file. Existing products are matched by barcode, then SKU, then name.</p>
            <form method="POST" action="{{ route('imports.products') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full text-sm">
                <x-primary-button>Import Products</x-primary-button>
            </form>
        </div>
    </div>

    @if (session('import_errors'))
        <div class="bg-white rounded-lg shadow-sm p-6 mt-6">
            <h3 class="font-semibold mb-2 text-red-600">Import Errors</h3>
            <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                @foreach (session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</x-app-layout>
