@php
    $styles = [
        'present' => 'bg-green-100 text-green-800',
        'late' => 'bg-yellow-100 text-yellow-800',
        'excused' => 'bg-blue-100 text-blue-800',
        'absent' => 'bg-orange-100 text-orange-900',
    ];
@endphp
<span class="px-2 py-1 text-xs rounded-full {{ $styles[$status] ?? 'bg-gray-100 text-gray-800' }}">
    {{ ucfirst($status) }}
</span>
