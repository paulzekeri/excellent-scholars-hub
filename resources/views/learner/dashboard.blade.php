@extends('layouts.app')

@section('title', 'Learner Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">My Dashboard</h1>
        <p class="text-gray-600 mt-1">Welcome back, {{ auth()->user()->name }}!</p>
    </div>

    @include('partials.affirmation')

    @php
        $focusSession = $liveSession ?? $nextSession;
        $isLive = (bool) $liveSession;
    @endphp
    <div class="mb-8 rounded-2xl shadow-md overflow-hidden {{ $isLive ? 'bg-gradient-to-r from-orange-500 to-orange-400' : 'bg-gradient-to-r from-blue-800 to-blue-600' }} text-white">
        <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-widest {{ $isLive ? 'text-orange-50' : 'text-blue-100' }} flex items-center">
                    @if($isLive)
                        <span class="h-2 w-2 bg-white rounded-full mr-2 animate-pulse"></span> Live right now
                    @else
                        <i class="fas fa-hourglass-half mr-2"></i> Up next
                    @endif
                </p>
                @if($focusSession)
                    <h2 class="text-2xl font-bold mt-1">{{ $focusSession->title }}</h2>
                    <p class="text-sm {{ $isLive ? 'text-orange-50' : 'text-blue-50' }}">
                        {{ $focusSession->class->name }} · {{ $focusSession->subjectName() }} · {{ $focusSession->tutor->user->name ?? 'Tutor' }}
                    </p>
                    <p class="text-sm {{ $isLive ? 'text-orange-50' : 'text-blue-100' }} mt-1">
                        <i class="far fa-clock mr-1"></i>
                        {{ $focusSession->start_time->format('h:i A') }} – {{ $focusSession->end_time->format('h:i A') }}
                        · {{ $focusSession->start_time->isToday() ? 'Today' : $focusSession->start_time->format('D, M d, Y') }}
                    </p>
                    <p class="text-xs {{ $isLive ? 'text-orange-100' : 'text-blue-200' }} mt-2">
                        @if($isLive)
                            Ends {{ $focusSession->end_time->diffForHumans() }}.
                        @else
                            Starts {{ $focusSession->start_time->diffForHumans() }}.
                        @endif
                    </p>
                @else
                    <h2 class="text-2xl font-bold mt-1">No class scheduled</h2>
                    <p class="text-sm text-blue-100">Your next session will appear here as soon as your tutor schedules it.</p>
                @endif
            </div>
            @if($focusSession && $focusSession->meeting_link && $focusSession->status == 'scheduled')
                <a href="{{ route('learner.class.join', $focusSession) }}" target="_blank"
                   class="inline-flex items-center justify-center {{ $isLive ? 'bg-white text-orange-700 hover:bg-orange-50' : 'bg-orange-500 text-blue-950 hover:bg-orange-400' }} px-6 py-3 rounded-lg font-semibold transition">
                    <i class="fas fa-video mr-2"></i> Join class
                </a>
            @elseif($focusSession)
                <span class="inline-flex items-center justify-center bg-white/20 px-6 py-3 rounded-lg text-sm">
                    <i class="fas fa-link mr-2"></i> Meeting link not added yet
                </span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-5 mb-8">
        @include('partials.stat-card', ['label' => 'My Classes', 'value' => $myClasses->count(), 'icon' => 'fa-chalkboard', 'tone' => 'navy', 'href' => route('learner.classes')])
        @include('partials.stat-card', ['label' => 'Pending Assignments', 'value' => $pendingAssignments, 'icon' => 'fa-tasks', 'tone' => 'blue', 'href' => route('learner.assignments')])
        @include('partials.stat-card', ['label' => 'Materials', 'value' => $materials->count(), 'icon' => 'fa-folder-open', 'tone' => 'blue', 'href' => route('learner.materials')])
        @include('partials.stat-card', ['label' => 'This Week\'s Sessions', 'value' => $weekSessionCount, 'icon' => 'fa-calendar', 'tone' => 'sun', 'href' => route('learner.classes')])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- My Classes -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">My Classes</h2>
                <a href="{{ route('learner.classes') }}" class="text-sm text-blue-600 hover:text-blue-800">View All <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            <div class="space-y-4">
                @forelse($myClasses as $class)
                    <a href="{{ route('learner.classes') }}" class="block border rounded-lg p-4 hover:bg-gray-50 transition">
                        <h3 class="font-semibold text-gray-800">{{ $class->name }}</h3>
                        <p class="text-sm text-gray-600">Tutor: {{ $class->tutor->user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $class->subjectSummary() }}</p>
                        <div class="mt-2 flex items-center text-xs text-blue-600">
                            <i class="fas fa-calendar mr-1"></i> 
                            {{ $class->schedules_count ?? $class->schedules()->where('start_time', '>', now())->count() }} upcoming session(s)
                        </div>
                    </a>
                @empty
                    <p class="text-gray-500 text-center py-4">You are not enrolled in any classes yet.</p>
                @endforelse
            </div>
        </div>

        <!-- This week's sessions -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex flex-wrap justify-between items-start gap-2 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">This Week's Sessions</h2>
                    <p class="text-xs text-gray-500">{{ $weekStart->format('D, M d') }} – {{ $weekEnd->format('D, M d, Y') }}</p>
                </div>
                <a href="{{ route('learner.classes', ['view' => 'all']) }}" class="text-sm text-blue-600 hover:text-blue-800">View All <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            <div class="space-y-4">
                @forelse($weekSessions as $schedule)
                    @php $isToday = \Carbon\Carbon::parse($schedule->start_time)->isToday(); @endphp
                    <div class="border-l-4 {{ $isToday ? 'border-sun bg-sun-light' : 'border-blue-500 bg-blue-50' }} p-4 rounded-r-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold {{ $isToday ? 'text-sun-ink' : 'text-gray-800' }}">{{ $schedule->title }}@if($isToday) <span class="ml-1 px-2 py-0.5 text-xs font-bold uppercase rounded-full bg-sun text-blue-950">Today</span>@endif</p>
                                <p class="text-sm text-gray-600">{{ $schedule->class->name }}</p>
                                <p class="text-xs text-gray-500">{{ $schedule->subjectName() }}</p>
                                <p class="text-xs {{ $isToday ? 'text-sun-ink font-semibold' : 'text-gray-500' }}">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('D, M d, Y') }}
                                </p>
                                <p class="text-xs {{ $isToday ? 'text-sun-ink' : 'text-gray-500' }}">
                                    <i class="far fa-clock mr-1"></i>
                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                                </p>
                            </div>
                            @if($schedule->meeting_link && $schedule->status == 'scheduled' && $schedule->end_time->greaterThanOrEqualTo(now()))
                                <a href="{{ route('learner.class.join', $schedule) }}" target="_blank" 
                                    class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600 transition flex items-center">
                                    <i class="fas fa-video mr-1"></i> Join
                                </a>
                            @elseif($schedule->status == 'scheduled' && $schedule->end_time->isPast())
                                <span class="text-gray-500 text-sm"><i class="fas fa-check-circle"></i> Ended</span>
                            @else
                                <span class="text-gray-500 text-sm"><i class="fas fa-check-circle"></i> {{ ucfirst($schedule->status) }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No sessions this week.</p>
                        <p class="text-xs text-gray-500 mt-1">
                            <a href="{{ route('learner.classes', ['view' => 'all']) }}" class="text-blue-600 hover:text-blue-800">View all sessions</a>
                            to see the full timetable.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Goals shared with you (note 7) -->
    <div class="bg-white rounded-xl shadow-md p-6 mt-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-bullseye text-orange-700 mr-2"></i>Goals shared with you
                @if($sharedGoalsCount > 0)
                    <span class="ml-2 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-sun-ink">{{ $sharedGoalsCount }}</span>
                @endif
            </h2>
            <a href="{{ route('learner.learning-goals.index') }}" class="text-sm text-blue-600 hover:text-blue-800">View all <i class="fas fa-arrow-right ml-1"></i></a>
        </div>

        @if($sharedGoals->isEmpty())
            <p class="text-sm text-gray-500">No goals have been shared with you yet — they appear here when your school shares them.</p>
        @else
            <ul class="space-y-3">
                @foreach($sharedGoals as $goal)
                    <li class="border border-gray-200 rounded-lg p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-medium text-gray-800 whitespace-pre-line">{{ $goal->goalExcerpt(160) }}</p>
                            <span class="text-xs text-gray-500">
                                {{ $goal->schoolClass->name ?? 'No class' }} · {{ $goal->term ?: 'No term' }}
                            </span>
                        </div>
                        @if($goal->target)
                            <p class="text-xs text-gray-600 mt-1"><strong>Target:</strong> {{ $goal->target }}</p>
                        @endif
                        @if($goal->description)
                            <p class="text-sm text-gray-600 mt-1">{{ \Illuminate\Support\Str::limit($goal->description, 140) }}</p>
                        @endif
                        @if($goal->file_path)
                            <a href="{{ route('learner.learning-goals.download', $goal) }}" class="mt-2 inline-block text-xs text-orange-700 hover:text-sun-ink">
                                <i class="fas fa-download mr-1"></i> Attachment
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
