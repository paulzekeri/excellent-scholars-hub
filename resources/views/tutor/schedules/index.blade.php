@extends('layouts.app')
@section('title', 'My Schedules')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">My Schedules</h1>
            <p class="text-gray-600 mt-1">
                @if($scope === 'week')
                    This week's sessions, grouped by day &mdash; use the arrows to look at another week. <span class="text-sun-ink font-medium">Sessions scheduled for today are highlighted in yellow.</span>
                @else
                    Your full schedule history, 25 sessions per page.
                @endif
            </p>
        </div>
        <a href="{{ route('tutor.schedules.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-plus mr-1"></i> New Schedule</a>
    </div>

    @if($scope === 'week')
    <div class="bg-white rounded-xl shadow-md p-4 mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('tutor.schedules.index', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200 transition"><i class="fas fa-chevron-left mr-1"></i> Previous week</a>
            @unless($weekStart->isCurrentWeek())
                <a href="{{ route('tutor.schedules.index') }}" class="bg-blue-700 text-white px-3 py-2 rounded-lg text-sm hover:bg-blue-800 transition"><i class="fas fa-calendar-day mr-1"></i> This week</a>
            @endunless
            <a href="{{ route('tutor.schedules.index', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Next week <i class="fas fa-chevron-right ml-1"></i></a>
        </div>
        <div class="text-sm font-semibold text-[#0a2550]">
            <i class="fas fa-calendar-week mr-1"></i>
            {{ $weekStart->format('D, M d') }} &ndash; {{ $weekEnd->format('D, M d, Y') }}
            @if($weekStart->isCurrentWeek())
                <span class="ml-2 px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-sun text-blue-950">This week</span>
            @endif
        </div>
    </div>
    @endif

    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
        <span>
            Showing <strong class="text-gray-700">{{ $matchedSessions }}</strong> session{{ $matchedSessions === 1 ? '' : 's' }}
            @if($scope === 'week')
                &middot; in <strong class="text-gray-700">{{ $weekStart->format('D, M d') }} &ndash; {{ $weekEnd->format('D, M d, Y') }}</strong>
            @endif
        </span>
        <span class="flex flex-wrap items-center gap-3">
            @if($scope === 'week')
                <a href="{{ route('tutor.schedules.index', ['week' => now()->toDateString()]) }}" class="font-semibold text-blue-700 hover:text-blue-900"><i class="fas fa-calendar-day mr-1"></i>Today</a>
                <a href="{{ route('tutor.schedules.index', ['view' => 'all']) }}" class="font-semibold text-blue-700 hover:text-blue-900"><i class="fas fa-list mr-1"></i>All schedules</a>
            @else
                <a href="{{ route('tutor.schedules.index') }}" class="font-semibold text-blue-700 hover:text-blue-900"><i class="fas fa-calendar-week mr-1"></i>This week</a>
            @endif
        </span>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Start</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">End</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th></tr>
                </thead>
                @if($scope === 'all')
                <tbody class="divide-y divide-gray-200">
                    @forelse($schedules as $schedule)
                        @include('tutor.schedules._row', ['schedule' => $schedule])
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                            <i class="fas fa-calendar-times mr-2 text-gray-400"></i>
                            No sessions scheduled yet.
                            <a href="{{ route('tutor.schedules.create') }}" class="font-semibold text-blue-700 hover:text-blue-900">Create your first schedule</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @else
                @php
                    // A week is shown in full under day headings, so the date is
                    // never repeated on every row.
                    $dayGroups = $schedules->groupBy(function ($session) {
                        return $session->start_time->toDateString();
                    });
                @endphp
                @forelse($dayGroups as $day => $daySessions)
                    @php $dayDate = \Carbon\Carbon::parse($day); @endphp
                    <tbody class="divide-y divide-gray-200">
                        <tr class="{{ $dayDate->isToday() ? 'bg-sun-light' : 'bg-gray-50' }}">
                            <th colspan="6" scope="colgroup" class="px-6 py-2 text-left text-xs font-semibold uppercase tracking-wide {{ $dayDate->isToday() ? 'text-sun-ink' : 'text-[#0a2550]' }}">
                                <i class="fas fa-calendar-day mr-1"></i>
                                {{ $dayDate->format('l, M d, Y') }}
                                @if($dayDate->isToday())
                                    <span class="ml-2 px-2 py-0.5 text-[10px] font-bold rounded-full bg-sun text-blue-950">Today</span>
                                @endif
                                <span class="ml-2 font-normal normal-case tracking-normal text-gray-500">{{ $daySessions->count() }} {{ $daySessions->count() === 1 ? 'session' : 'sessions' }}</span>
                            </th>
                        </tr>
                        @foreach($daySessions as $schedule)
                            @include('tutor.schedules._row', ['schedule' => $schedule, 'showDate' => false])
                        @endforeach
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                <i class="fas fa-calendar-times mr-2 text-gray-400"></i>
                                No sessions scheduled this week.
                                <a href="{{ route('tutor.schedules.index', ['view' => 'all']) }}" class="font-semibold text-blue-700 hover:text-blue-900">Browse all schedules</a>
                                or move to another week.
                            </td>
                        </tr>
                    </tbody>
                @endforelse
                @endif
            </table>
        </div>
        @if($scope === 'all')
            <div class="p-4 border-t">{{ $schedules->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmComplete(form, endTime) {
    var now = new Date();
    var end = new Date(endTime);
    if (now < end) {
        alert('This class session has not ended yet. You can only mark it as completed after the scheduled end time (' + end.toLocaleString() + ').');
        return false;
    }
    return confirm('Mark this class as completed?');
}
</script>
@endpush