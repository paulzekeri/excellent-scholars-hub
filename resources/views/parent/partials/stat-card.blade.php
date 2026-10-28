@php
    $active = $active ?? false;
    $cardClasses = $active
        ? 'border-blue-300 bg-blue-50 ring-2 ring-blue-500'
        : 'border-gray-200 bg-white hover:border-blue-300 hover:bg-gray-50';
@endphp
<a href="{{ $href }}" class="block rounded-lg border p-4 transition {{ $cardClasses }}" title="{{ $label }}">
    <div class="flex items-center justify-between">
        <i class="fas {{ $icon }} {{ $iconClass ?? 'text-blue-500' }} text-lg"></i>
        <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
    </div>
    <p class="text-2xl font-bold text-gray-800 mt-2">{{ $count }}</p>
    <p class="text-xs text-gray-500 leading-tight">{{ $label }}</p>
</a>
