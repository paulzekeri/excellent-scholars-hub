@php
    $active = $active ?? false;
    $cardClasses = $active
        ? 'border-indigo-300 bg-indigo-50 ring-2 ring-indigo-500'
        : 'border-gray-200 bg-white hover:border-indigo-300 hover:bg-gray-50';
@endphp
<a href="{{ $href }}" class="block rounded-lg border p-4 transition {{ $cardClasses }}" title="{{ $label }}">
    <div class="flex items-center justify-between">
        <i class="fas {{ $icon }} {{ $iconClass ?? 'text-indigo-500' }} text-lg"></i>
        <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
    </div>
    <p class="text-2xl font-bold text-gray-800 mt-2">{{ $count }}</p>
    <p class="text-xs text-gray-500 leading-tight">{{ $label }}</p>
</a>
