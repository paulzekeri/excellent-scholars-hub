@extends('layouts.app')

@section('title', 'Child Progress')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <a href="{{ route('parent.dashboard') }}" class="text-indigo-600 hover:text-indigo-800 mb-4 inline-block">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
        <h1 class="text-3xl font-bold text-gray-800">{{ $learner->user->name }} - Progress</h1>
        <p class="text-gray-600 mt-1">Grade Level: {{ $learner->grade_level ?? 'N/A' }}</p>
    </div>

    <div class="space-y-8">
        <!-- Attendance -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Recent Attendance</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($attendance as $record)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ \Carbon\Carbon::parse($record->classSchedule->start_time)->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm">{{ $record->classSchedule->class->name }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        @if($record->status == 'present') bg-green-100 text-green-800
                                        @elseif($record->status == 'late') bg-yellow-100 text-yellow-800
                                        @elseif($record->status == 'excused') bg-blue-100 text-blue-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ ucfirst($record->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-3 text-center text-gray-500">No attendance records</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Assignments -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Assignments & Submissions</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignment</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Feedback</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($assignments as $assignment)
                            @php
                                $submission = $assignment->submissions->first();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $assignment->title }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $assignment->class->name }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="{{ $assignment->due_date->isPast() ? 'text-red-600' : 'text-gray-600' }}">
                                        {{ $assignment->due_date->format('M d, Y h:i A') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($submission)
                                        <span class="text-green-600">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            {{ $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y h:i A') : \Carbon\Carbon::parse($submission->created_at)->format('M d, Y h:i A') }}
                                        </span>
                                    @else
                                        <span class="text-red-500"><i class="fas fa-times mr-1"></i> Not submitted</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-sm font-semibold">
                                    @if($submission && $submission->score !== null)
                                        <span class="{{ $submission->score >= ($assignment->total_points / 2) ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $submission->score }} / {{ $assignment->total_points }}
                                        </span>
                                    @elseif($submission)
                                        <span class="text-yellow-600">Pending grading</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($submission)
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            @if($submission->status == 'graded') bg-green-100 text-green-800
                                            @elseif($submission->status == 'submitted') bg-blue-100 text-blue-800
                                            @elseif($submission->status == 'late') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($submission->status) }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Missing</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm max-w-xs">
                                    @if($submission && $submission->feedback)
                                        <div class="bg-gray-50 rounded p-2 text-gray-700 text-xs">
                                            <span class="font-medium text-indigo-600">Teacher:</span>
                                            {{ $submission->feedback }}
                                            @if($submission->graded_at)
                                                <div class="mt-1 text-gray-400">
                                                    Graded on {{ \Carbon\Carbon::parse($submission->graded_at)->format('M d, Y') }}
                                                </div>
                                            @endif
                                        </div>
                                    @elseif($submission && $submission->status == 'graded')
                                        <span class="text-gray-400 italic">No feedback provided</span>
                                    @elseif($submission)
                                        <span class="text-yellow-600 text-xs">Awaiting grading</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No assignments found for enrolled classes</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Upcoming Classes -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Upcoming Classes</h2>
            <div class="space-y-4">
                @forelse($upcomingClasses as $schedule)
                    <div class="border-l-4 border-indigo-500 bg-indigo-50 p-4 rounded-r-lg">
                        <p class="font-semibold text-gray-800">{{ $schedule->title }}</p>
                        <p class="text-sm text-gray-600">{{ $schedule->class->name }}</p>
                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($schedule->start_time)->format('M d, Y h:i A') }}</p>
                    </div>
                @empty
                    <p class="text-gray-500 text-center">No upcoming classes</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
