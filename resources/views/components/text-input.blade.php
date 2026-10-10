@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 bg-white text-gray-900 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm text-sm']) }}>
