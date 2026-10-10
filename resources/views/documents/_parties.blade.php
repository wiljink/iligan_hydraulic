{{-- Customer on the left, document number/date (and any extra lines) on the right. --}}
<div class="flex justify-between gap-6 text-sm mb-4">
    <div>
        <p><strong>Customer:</strong> {{ $customer?->name ?? 'Walk-in' }}</p>
        @if ($customer?->address)
            <p><strong>Address:</strong> {{ $customer->address }}</p>
        @endif
        <p><strong>TIN:</strong> {{ $customer?->tin_number ?: '—' }}</p>
    </div>
    <div class="text-right shrink-0">
        @foreach ($meta as $label => $value)
            <p><strong>{{ $label }}:</strong> {{ $value }}</p>
        @endforeach
    </div>
</div>
