{{--
    Goal text for list screens.

    A goal can run to 5,000 characters, which does not fit in a table cell, so the
    cell shows a short excerpt plus a "Read the full goal" toggle. The complete
    text is always in the page, so nobody (admin, the tutor who wrote it, or a
    person it was shared with by name) is left with a cut-off goal.

    Expects: $goal, optional $limit (characters shown before the toggle).
--}}
@php
    $goalText = trim($goal->goalText());
    $goalLimit = $limit ?? 220;
    $goalIsLong = \Illuminate\Support\Str::length($goalText) > $goalLimit;
@endphp

<p class="font-medium text-gray-800 whitespace-pre-line">{{ $goal->goalExcerpt($goalLimit) }}</p>

@if($goalIsLong)
    <details class="mt-1 group">
        <summary class="cursor-pointer text-xs font-semibold text-blue-700 hover:text-blue-900">
            <i class="fas fa-chevron-down mr-1 transition-transform group-open:rotate-180"></i>Read the full goal
            <span class="font-normal text-gray-500">({{ number_format(\Illuminate\Support\Str::length($goalText)) }} characters)</span>
        </summary>
        <div class="mt-2 max-h-64 overflow-y-auto whitespace-pre-line rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm leading-relaxed text-gray-700">{{ $goalText }}</div>
    </details>
@endif
