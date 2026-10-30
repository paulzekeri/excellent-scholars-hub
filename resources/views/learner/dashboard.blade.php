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
                        {{ $focusSession->class->name }} · {{ $focusSession->tutor->user->name ?? 'Tutor' }}
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
                   class="inline-flex items-center justify-center {{ $isLive ? 'bg-white text-orange-600 hover:bg-orange-50' : 'bg-orange-500 text-white hover:bg-orange-600' }} px-6 py-3 rounded-lg font-semibold transition">
                    <i class="fas fa-video mr-2"></i> Join class
                </a>
            @elseif($focusSession)
                <span class="inline-flex items-center justify-center bg-white/20 px-6 py-3 rounded-lg text-sm">
                    <i class="fas fa-link mr-2"></i> Meeting link not added yet
                </span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 mb-8">
        <a href="{{ route('learner.classes') }}" class="block bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">My Classes</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $myClasses->count() }}</p>
                </div>
                <i class="fas fa-chalkboard text-blue-600 text-3xl"></i>
            </div>
        </a>
        <a href="{{ route('learner.assignments') }}" class="block bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Pending Assignments</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $pendingAssignments }}</p>
                </div>
                <i class="fas fa-tasks text-green-600 text-3xl"></i>
            </div>
        </a>
        <a href="{{ route('learner.materials') }}" class="block bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Materials</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $materials->count() }}</p>
                </div>
                <i class="fas fa-folder-open text-blue-600 text-3xl"></i>
            </div>
        </a>
        <a href="{{ route('learner.classes') }}" class="block bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Upcoming Sessions</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $upcomingCount }}</p>
                </div>
                <i class="fas fa-calendar text-yellow-600 text-3xl"></i>
            </div>
        </a>
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
                        <p class="text-xs text-gray-500">{{ $class->subject ?? 'General' }}</p>
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

        <!-- Upcoming Classes (Schedules) -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Upcoming Sessions</h2>
                <a href="{{ route('learner.classes') }}" class="text-sm text-blue-600 hover:text-blue-800">View All <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
            <div class="space-y-4">
                @forelse($upcomingClasses as $schedule)
                    @php $isToday = \Carbon\Carbon::parse($schedule->start_time)->isToday(); @endphp
                    <div class="border-l-4 {{ $isToday ? 'border-red-500 bg-red-50' : 'border-blue-500 bg-blue-50' }} p-4 rounded-r-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold {{ $isToday ? 'text-red-700' : 'text-gray-800' }}">{{ $schedule->title }}@if($isToday) <span class="ml-1 px-2 py-0.5 text-xs font-bold uppercase rounded-full bg-red-600 text-white">Today</span>@endif</p>
                                <p class="text-sm text-gray-600">{{ $schedule->class->name }}</p>
                                <p class="text-xs {{ $isToday ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('D, M d, Y') }}
                                </p>
                                <p class="text-xs {{ $isToday ? 'text-red-600' : 'text-gray-500' }}">
                                    <i class="far fa-clock mr-1"></i>
                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') }}
                                </p>
                            </div>
                            @if($schedule->meeting_link && $schedule->status == 'scheduled')
                                <a href="{{ route('learner.class.join', $schedule) }}" target="_blank" 
                                    class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600 transition flex items-center">
                                    <i class="fas fa-video mr-1"></i> Join
                                </a>
                            @else
                                <span class="text-gray-400 text-sm"><i class="fas fa-check-circle"></i> {{ ucfirst($schedule->status) }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No upcoming sessions scheduled.</p>
                        <p class="text-xs text-gray-400 mt-1">Sessions appear here when your tutor creates schedules for your classes.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
