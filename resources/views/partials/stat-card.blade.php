@php
    /**
     * Dashboard stat card.
     *
     * Usage: @include('partials.stat-card', [
     *     'label' => 'Total Learners', 'value' => $totalLearners,
     *     'icon' => 'fa-user-graduate', 'tone' => 'blue',
     *     'href' => route('...'),        // optional: renders an <a>
     *     'meta' => 'this month',        // optional: small caption
     * ])
     *
     * Tones carry meaning, so the dashboards stay calm: blue = people,
     * navy = academics, orange = finance/actions, sun = look here, green = success.
     */
    $statTones = [
        'blue' => 'bg-[#EAF2FB] text-[#1d4ed8]',
        'navy' => 'bg-[#EAF2FB] text-[#0a2550]',
        'orange' => 'bg-[#FFF1E6] text-[#C2530F]',
        'sun' => 'bg-sun-light text-sun-ink',
        'green' => 'bg-green-50 text-green-700',
    ];

    $statTone = $statTones[$tone ?? 'navy'] ?? $statTones['navy'];
    $statLink = ! empty($href);

    $statCard = 'flex items-center justify-between gap-4 rounded-xl border border-[#E6ECF4] bg-white p-5 shadow-sm transition';
    $statCard .= $statLink ? ' hover:border-[#C3D4EA] hover:shadow-md' : '';
@endphp

@if($statLink)<a href="{{ $href }}" class="{{ $statCard }}">@else<div class="{{ $statCard }}">@endif
    <div class="min-w-0">
        <p class="truncate text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
        <p class="mt-2 font-display text-3xl font-extrabold text-[#0a2550]">{{ $value }}</p>
        @isset($meta)<p class="mt-1 text-xs text-gray-500">{{ $meta }}</p>@endisset
    </div>
    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $statTone }}">
        <i class="fas {{ $icon }}"></i>
    </span>
@if($statLink)</a>@else</div>@endif
