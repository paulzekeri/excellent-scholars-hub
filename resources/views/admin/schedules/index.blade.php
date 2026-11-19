@extends('layouts.app')
@section('title', 'Manage Schedules')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Manage Schedules</h1>
            <p class="text-gray-600 mt-1">
                @if($scope === 'week')
                    One week at a time, grouped by day &mdash; use the arrows to move between weeks. <span class="text-sun-ink font-medium">Sessions scheduled for today are highlighted in yellow.</span>
                @elseif($scope === 'day')
                    Showing a single day. <a href="{{ route('admin.schedules.index', $filterParams) }}" class="font-semibold text-blue-700 hover:text-blue-900">Back to the week view</a>
                @else
                    Full history &mdash; upcoming sessions first, then most recent past sessions. <span class="text-sun-ink font-medium">Sessions scheduled for today are highlighted in yellow.</span>
                @endif
            </p>
        </div>
        <a href="{{ route('admin.schedules.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-plus mr-1"></i> New Schedule</a>
    </div>

    @if($scope === 'week')
    <div class="bg-white rounded-xl shadow-md p-4 mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.schedules.index', array_merge($filterParams, ['week' => $weekStart->copy()->subWeek()->toDateString()])) }}" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200 transition"><i class="fas fa-chevron-left mr-1"></i> Previous week</a>
            @unless($weekStart->isCurrentWeek())
                <a href="{{ route('admin.schedules.index', $filterParams) }}" class="bg-blue-700 text-white px-3 py-2 rounded-lg text-sm hover:bg-blue-800 transition"><i class="fas fa-calendar-day mr-1"></i> This week</a>
            @endunless
            <a href="{{ route('admin.schedules.index', array_merge($filterParams, ['week' => $weekStart->copy()->addWeek()->toDateString()])) }}" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Next week <i class="fas fa-chevron-right ml-1"></i></a>
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

    <form method="GET" action="{{ route('admin.schedules.index') }}" class="bg-white rounded-xl shadow-md p-4 mb-6">
        @if($scope === 'all')
            {{-- Keep the full-history view when the filters are applied. --}}
            <input type="hidden" name="view" value="all">
        @elseif($scope === 'week' && ! $weekStart->isCurrentWeek())
            {{-- Filtering while browsing another week stays on that week. --}}
            <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
        @endif
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            <div>
                <label for="schedule-class-filter" class="block text-xs font-medium text-gray-600 mb-1">Class</label>
                <select id="schedule-class-filter" name="class_id" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ (string) $classFilter === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="schedule-subject-filter" class="block text-xs font-medium text-gray-600 mb-1">Subject</label>
                <select id="schedule-subject-filter" name="class_subject_id" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All subjects</option>
                    @foreach($classSubjects->groupBy(function ($row) { return $row->class->name ?? 'Class'; }) as $className => $rows)
                        <optgroup label="{{ $className }}">
                            @foreach($rows as $row)
                                <option value="{{ $row->id }}" {{ (string) $subjectFilter === (string) $row->id ? 'selected' : '' }}>
                                    {{ $row->subject->name ?? 'Subject' }} — {{ $row->tutor->user->name ?? 'No teacher' }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="schedule-tutor-filter" class="block text-xs font-medium text-gray-600 mb-1">Tutor</label>
                <select id="schedule-tutor-filter" name="tutor_id" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All tutors</option>
                    @foreach($tutors as $tutor)
                        <option value="{{ $tutor->id }}" {{ (string) $tutorFilter === (string) $tutor->id ? 'selected' : '' }}>{{ $tutor->user->name ?? 'Tutor' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="schedule-date-filter" class="block text-xs font-medium text-gray-600 mb-1">Start Time (day)</label>
                <input id="schedule-date-filter" type="date" name="date" value="{{ $dateFilter }}" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="schedule-status-filter" class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="schedule-status-filter" name="status" class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Any status</option>
                    @foreach(['scheduled' => 'Scheduled', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" {{ $statusFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-800 transition"><i class="fas fa-filter mr-1"></i> Filter</button>
                <a href="{{ route('admin.schedules.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Reset</a>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
            <span>
                Showing <strong class="text-gray-700">{{ $matchedSessions }}</strong> of {{ $totalSessions }} sessions
                @if($scope === 'week')
                    &middot; in <strong class="text-gray-700">{{ $weekStart->format('D, M d') }} &ndash; {{ $weekEnd->format('D, M d, Y') }}</strong>
                @elseif($dateFilter)
                    &middot; on <strong class="text-gray-700">{{ \Carbon\Carbon::parse($dateFilter)->format('D, M d, Y') }}</strong>
                @endif
            </span>
            <span class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.schedules.index', array_merge($filterParams, ['date' => now()->toDateString()])) }}" class="font-semibold text-blue-700 hover:text-blue-900"><i class="fas fa-calendar-day mr-1"></i>Today</a>
                @if($scope !== 'week')
                    <a href="{{ route('admin.schedules.index', $filterParams) }}" class="font-semibold text-blue-700 hover:text-blue-900"><i class="fas fa-calendar-week mr-1"></i>This week</a>
                @endif
                @if($scope !== 'all')
                    <a href="{{ route('admin.schedules.index', array_merge($filterParams, ['view' => 'all'])) }}" class="font-semibold text-blue-700 hover:text-blue-900"><i class="fas fa-list mr-1"></i>All schedules</a>
                @endif
            </span>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Tutor</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Start Time</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th></tr>
                </thead>
                @if($scope === 'all')
                <tbody class="divide-y divide-gray-200">
                    @forelse($schedules as $schedule)
                        @include('admin.schedules._row', ['schedule' => $schedule])
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                            <i class="fas fa-calendar-times mr-2 text-gray-400"></i>
                            @if($isFiltered)
                                No sessions match these filters.
                                <a href="{{ route('admin.schedules.index') }}" class="font-semibold text-blue-700 hover:text-blue-900">Clear the filters</a>
                                to see everything.
                            @else
                                No sessions have been scheduled yet.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @else
                @php
                    // "week" and "day" are shown in full and grouped under a day
                    // heading, so the same date is never repeated on every row.
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
                            @include('admin.schedules._row', ['schedule' => $schedule, 'showDate' => false])
                        @endforeach
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                <i class="fas fa-calendar-times mr-2 text-gray-400"></i>
                                @if($isFiltered)
                                    No sessions match these filters.
                                    <a href="{{ route('admin.schedules.index') }}" class="font-semibold text-blue-700 hover:text-blue-900">Clear the filters</a>
                                    to see everything.
                                @elseif($scope === 'week')
                                    No sessions scheduled this week.
                                    <a href="{{ route('admin.schedules.index', ['view' => 'all']) }}" class="font-semibold text-blue-700 hover:text-blue-900">Browse all schedules</a>
                                    or move to another week.
                                @else
                                    No sessions scheduled on this day.
                                @endif
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
