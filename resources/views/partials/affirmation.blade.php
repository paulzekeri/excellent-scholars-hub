@php
    // The affirmation is stable for the whole day and rotates automatically.
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

    $affirmation = $affirmations[now()->dayOfYear % count($affirmations)];
@endphp

<div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-orange-500">
    <div class="flex items-start gap-4">
        <div class="bg-orange-100 text-orange-600 rounded-full p-3 shrink-0">
            <i class="fas fa-sun text-lg"></i>
        </div>
        <div>
            <p class="text-xs uppercase tracking-wide text-orange-600 font-semibold">Affirmation of the day</p>
            <p class="text-gray-800 font-medium mt-1">&ldquo;{{ $affirmation }}&rdquo;</p>
            <p class="text-xs text-gray-500 mt-2">{{ now()->format('l, F j, Y') }}</p>
        </div>
    </div>
</div>
