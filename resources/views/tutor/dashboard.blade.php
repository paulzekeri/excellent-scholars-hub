@extends('layouts.app')

@section('title', 'Tutor Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Tutor Dashboard</h1>
        <p class="text-gray-600 mt-1">Welcome back, {{ auth()->user()->name }}!</p>
    </div>

    @include('partials.affirmation')

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">My Classes</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $myClasses }}</p>
                </div>
                <i class="fas fa-chalkboard text-blue-600 text-3xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Assignments</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalAssignments }}</p>
                </div>
                <i class="fas fa-tasks text-green-600 text-3xl"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Materials</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalMaterials }}</p>
                </div>
                <i class="fas fa-folder-open text-blue-600 text-3xl"></i>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('tutor.schedules.create') }}" class="bg-white rounded-xl shadow-md p-4 text-center hover:shadow-lg transition">
            <i class="fas fa-plus-circle text-blue-600 text-2xl mb-2"></i>
            <p class="text-sm font-medium text-gray-700">New Schedule</p>
        </a>
        <a href="{{ route('tutor.assignments.create') }}" class="bg-white rounded-xl shadow-md p-4 text-center hover:shadow-lg transition">
            <i class="fas fa-file-alt text-green-600 text-2xl mb-2"></i>
            <p class="text-sm font-medium text-gray-700">New Assignment</p>
        </a>
        <a href="{{ route('tutor.materials.create') }}" class="bg-white rounded-xl shadow-md p-4 text-center hover:shadow-lg transition">
            <i class="fas fa-upload text-blue-600 text-2xl mb-2"></i>
            <p class="text-sm font-medium text-gray-700">Upload Material</p>
        </a>
        <a href="{{ route('tutor.schedules.index') }}" class="bg-white rounded-xl shadow-md p-4 text-center hover:shadow-lg transition">
            <i class="fas fa-calendar-check text-purple-600 text-2xl mb-2"></i>
            <p class="text-sm font-medium text-gray-700">View Schedule</p>
        </a>
    </div>

    <!-- Upcoming Schedules -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Upcoming Classes</h2>
        <div class="space-y-4">
            @forelse($upcomingSchedules as $schedule)
                <div class="flex items-center justify-between border-b pb-3">
                    <div>
                        <p class="font-semibold">{{ $schedule->title }}</p>
                        <p class="text-sm text-gray-600">{{ $schedule->class->name }}</p>
                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($schedule->start_time)->format('M d, Y h:i A') }}</p>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('tutor.attendance.index', $schedule) }}" class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-sm hover:bg-blue-200">Attendance</a>
                        @if($schedule->meeting_link)
                            <a href="{{ $schedule->meeting_link }}" target="_blank" class="bg-green-100 text-green-700 px-3 py-1 rounded text-sm hover:bg-green-200">Join</a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-center">No upcoming classes</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
