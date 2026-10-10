{{-- Company letterhead + document title for printed invoices, receipts, memos and vouchers. --}}
<div class="text-center mb-6">
    <h1 class="text-xl font-extrabold uppercase tracking-wide">{{ setting('company_name', config('app.name')) }}</h1>
    <p class="text-sm text-gray-600">{{ setting('company_address') }}</p>
    @if (setting('company_phone') || setting('company_tin'))
        <p class="text-sm text-gray-600">
            {{ setting('company_phone') ? 'Tel./Cell No. '.setting('company_phone') : '' }}
            {{ setting('company_phone') && setting('company_tin') ? ' · ' : '' }}
            {{ setting('company_tin') ? 'TIN '.setting('company_tin') : '' }}
        </p>
    @endif
    @if (setting('company_tagline'))
        <p class="text-xs font-semibold text-gray-700 mt-1 uppercase">{{ setting('company_tagline') }}</p>
    @endif
    @isset($title)
        <p class="mt-4 text-base font-bold tracking-widest uppercase">{{ $title }}</p>
    @endisset
</div>
