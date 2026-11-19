@extends('layouts.app')
@section('title', 'My Classes')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-start gap-3 mb-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">My Classes</h1>
            <p class="text-gray-600">
                @if($scope === 'all')
                    Your enrolled classes and their full schedule history.
                @else
                    Your enrolled classes and this week's sessions.
                @endif
            </p>
        </div>
        <a href="{{ $scope === 'all' ? route('learner.classes') : route('learner.classes', ['view' => 'all']) }}"
           class="inline-flex items-center bg-white border border-blue-600 text-blue-700 px-4 py-2 rounded-lg text-sm hover:bg-blue-50 transition">
            @if($scope === 'all')
                <i class="fas fa-calendar-week mr-2"></i> This week
            @else
                <i class="fas fa-list mr-2"></i> All sessions
            @endif
        </a>
    </div>

    @if($scope === 'week')
        <div class="bg-white rounded-xl shadow-md p-4 mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('learner.classes', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200 transition"><i class="fas fa-chevron-left mr-1"></i> Previous week</a>
                @unless($weekStart->isCurrentWeek())
                    <a href="{{ route('learner.classes') }}" class="bg-blue-700 text-white px-3 py-2 rounded-lg text-sm hover:bg-blue-800 transition"><i class="fas fa-calendar-day mr-1"></i> This week</a>
                @endunless
                <a href="{{ route('learner.classes', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Next week <i class="fas fa-chevron-right ml-1"></i></a>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        @foreach($classes as $class)
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800">{{ $class->name }}</h2>
                <p class="text-gray-600">{{ $class->subjectSummary() }} - {{ $class->grade_level }}</p>
                @if($class->subjectAssignments->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($class->subjectAssignments as $assignment)
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs text-blue-900">
                        <strong class="font-semibold">{{ $assignment->subject->name ?? 'Subject' }}</strong>
                        <span class="ml-1 text-gray-600">— {{ $assignment->tutor->user->name ?? 'Teacher not assigned yet' }}</span>
                    </span>
                    @endforeach
                </div>
                @endif
                @if($class->description)<p class="text-sm text-gray-500 mt-2">{{ $class->description }}</p>@endif
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                {{ $scope === 'all' ? 'All Sessions' : "This Week's Sessions" }}
                <span class="ml-1 text-sm font-normal text-gray-500">({{ $class->schedules->count() }})</span>
            </h3>
            @forelse($class->schedules as $schedule)
            <div class="bg-white rounded-xl shadow-md p-6 mb-4 hover:shadow-lg transition">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-gray-800">{{ $schedule->title }}</h4>
                        <p class="text-sm text-gray-600">{{ $schedule->subjectName() }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ \Carbon\Carbon::parse($schedule->start_time)->format('l, M d, Y') }}</p>
                        <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}</p>
                    </div>
                    <div class="text-right">
                        @if($schedule->meeting_link && $schedule->status == 'scheduled' && $schedule->end_time->greaterThanOrEqualTo(now()))
                            <a href="{{ $schedule->meeting_link }}" target="_blank" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition inline-flex items-center"><i class="fas fa-video mr-2"></i> Join Class</a>
                        @elseif($schedule->status == 'scheduled' && $schedule->end_time->isPast())
                            <span class="text-gray-500"><i class="fas fa-check-circle"></i> Ended</span>
                        @else
                            <span class="text-gray-500"><i class="fas fa-check-circle"></i> {{ ucfirst($schedule->status) }}</span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <p class="text-gray-500">
                {{ $scope === 'all' ? 'No sessions scheduled for this class yet.' : 'No sessions for this class this week.' }}
                @if($scope === 'week')
                    <a href="{{ route('learner.classes', ['view' => 'all']) }}" class="text-blue-600 hover:text-blue-800">View all sessions</a>.
                @endif
            </p>
            @endforelse
        </div>
        @endforeach
    </div>
</div>
@endsection
