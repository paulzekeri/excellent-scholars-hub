@php
    $currentAffirmation = \App\Models\Affirmation::current();

    // The built-in rotation is the fallback when the school has not posted one.
    $affirmations = [
        'I am capable, focused and ready to learn.',
        'Small steps every day add up to big results.',
        'My effort today shapes my success tomorrow.',
        'I ask questions, I practise, and I grow.',
        'Mistakes are proof that I am trying.',
        'I am in charge of my progress.',
        'I choose discipline over distraction.',
        'Every class I attend makes me stronger.',
        'I deserve the goals I am working for.',
        'I can do hard things, one page at a time.',
        'My curiosity is my superpower.',
        'I am proud of the progress I have already made.',
        'I prepare today so I can shine tomorrow.',
        'Knowledge grows when I share it.',
        'I finish what I start.',
        'I am becoming the best version of myself.',
        'My future self is cheering me on.',
        'I learn from every result, not just the good ones.',
        'Kindness and focus go a long way.',
        'I show up, I try, and I improve.',
    ];

    $affirmation = $currentAffirmation
        ? $currentAffirmation->body
        : $affirmations[now()->dayOfYear % count($affirmations)];

    $affirmationLabel = $currentAffirmation ? 'Affirmation of the week' : 'Affirmation of the day';

    $affirmationMeta = $currentAffirmation
        ? trim(($currentAffirmation->author ? 'From ' . $currentAffirmation->author->name : 'From the school')
            . ($currentAffirmation->published_at ? ' · ' . $currentAffirmation->published_at->format('M j, Y') : ''))
        : now()->format('l, F j, Y');
@endphp

<div class="relative overflow-hidden rounded-xl shadow-md p-6 text-white bg-gradient-to-r from-[#0a2550] via-[#0d3070] to-[#1d4ed8]">
    <div class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10"></div>
    <div class="pointer-events-none absolute -bottom-8 -left-4 h-20 w-20 rounded-full bg-white/10"></div>

    <div class="relative flex items-start gap-4">
        <div class="shrink-0 rounded-full bg-white/20 p-3">
            <i class="fas fa-sun text-lg"></i>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-white/80">{{ $affirmationLabel }}</p>
            <p class="mt-1 text-lg font-semibold text-white">&ldquo;{{ $affirmation }}&rdquo;</p>
            <p class="mt-2 text-xs text-white/80">{{ $affirmationMeta }}</p>
        </div>
    </div>
</div>
