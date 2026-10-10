<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Settings</h2>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl">
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
            @csrf @method('PUT')

            <div>
                <x-input-label for="company_name" value="Company Name *" />
                <x-text-input id="company_name" name="company_name" class="mt-1 block w-full" value="{{ old('company_name', $settings['company_name'] ?? 'Osmena Hydraulic') }}" required />
            </div>
            <div>
                <x-input-label for="company_address" value="Address" />
                <x-text-input id="company_address" name="company_address" class="mt-1 block w-full" value="{{ old('company_address', $settings['company_address'] ?? '') }}" />
            </div>
            <div>
                <x-input-label for="company_tagline" value="Tagline (printed under the letterhead)" />
                <x-text-input id="company_tagline" name="company_tagline" class="mt-1 block w-full" value="{{ old('company_tagline', $settings['company_tagline'] ?? '') }}" placeholder="e.g. * FITTINGS * INDUSTRIAL HOSES * SEIKEN RUBBER CUPS * BRAKE BONDING *" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="company_phone" value="Phone" />
                    <x-text-input id="company_phone" name="company_phone" class="mt-1 block w-full" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}" />
                </div>
                <div>
                    <x-input-label for="company_tin" value="TIN" />
                    <x-text-input id="company_tin" name="company_tin" class="mt-1 block w-full" value="{{ old('company_tin', $settings['company_tin'] ?? '') }}" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <x-input-label for="currency_symbol" value="Currency Symbol *" />
                    <x-text-input id="currency_symbol" name="currency_symbol" class="mt-1 block w-full" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? config('pos.currency_symbol')) }}" required />
                </div>
                <div>
                    <x-input-label for="tax_rate" value="Tax Rate (0-1) *" />
                    <x-text-input id="tax_rate" type="number" step="0.01" min="0" max="1" name="tax_rate" class="mt-1 block w-full" value="{{ old('tax_rate', $settings['tax_rate'] ?? config('pos.tax_rate')) }}" required />
                </div>
                <div>
                    <x-input-label for="low_stock_default" value="Default Reorder Level *" />
                    <x-text-input id="low_stock_default" type="number" min="0" name="low_stock_default" class="mt-1 block w-full" value="{{ old('low_stock_default', $settings['low_stock_default'] ?? config('pos.low_stock_default')) }}" required />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="hidden" name="tax_inclusive" value="0">
                <input type="checkbox" id="tax_inclusive" name="tax_inclusive" value="1" @checked(old('tax_inclusive', $settings['tax_inclusive'] ?? false)) class="rounded border-gray-300">
                <x-input-label for="tax_inclusive" value="Prices are tax-inclusive" />
            </div>

            <div>
                <x-input-label for="receipt_footer" value="Receipt Footer Message" />
                <textarea id="receipt_footer" name="receipt_footer" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">{{ old('receipt_footer', $settings['receipt_footer'] ?? 'Thank you for your business!') }}</textarea>
            </div>

            <div>
                <x-input-label for="payroll_days_per_month" value="Payroll: working days per month" />
                <x-text-input id="payroll_days_per_month" type="number" step="0.01" min="1" max="31" name="payroll_days_per_month" class="mt-1 block w-40" value="{{ old('payroll_days_per_month', $settings['payroll_days_per_month'] ?? 26) }}" />
                <p class="mt-1 text-xs text-gray-400">Daily rate &times; this = monthly salary used for SSS, PhilHealth and Pag-IBIG. 26 for a 6-day week, 22 for a 5-day week.</p>
            </div>

            <x-primary-button>Save Settings</x-primary-button>
        </form>
    </div>
</x-app-layout>
