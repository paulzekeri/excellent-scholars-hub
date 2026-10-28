@extends('layouts.app')
@section('title', 'Session - ' . $schedule->title)
@section('content')
@php
    $statusStyles = [
        'scheduled' => 'bg-blue-100 text-blue-800',
        'ongoing' => 'bg-yellow-100 text-yellow-800',
        'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
    ];
    $statusStyle = $statusStyles[$schedule->status] ?? 'bg-gray-100 text-gray-800';
    $platformLabels = ['google_meet' => 'Google Meet', 'zoom' => 'Zoom'];
    $platformLabel = $schedule->meeting_platform
        ? ($platformLabels[$schedule->meeting_platform] ?? ucwords(str_replace('_', ' ', $schedule->meeting_platform)))
        : null;
    $isUpcomingOrLive = in_array($schedule->status, ['scheduled', 'ongoing']);
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <a href="{{ route('admin.schedules.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-1"></i> Back to Schedules</a>

    <!-- Session Summary -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
            <div>
                <div class="flex flex-wrap items-center gap-3 mb-2">
                    <span class="px-3 py-1 text-xs font-medium rounded-full {{ $statusStyle }}">{{ ucfirst($schedule->status) }}</span>
                    @if($schedule->start_time->isToday())
                        <span class="px-3 py-1 text-xs font-bold uppercase rounded-full bg-red-600 text-white">Today</span>
                    @endif
                    @if($platformLabel)
                        <span class="text-xs text-gray-500"><i class="fas fa-video mr-1"></i> {{ $platformLabel }}</span>
                    @endif
                    @if($schedule->status == 'ongoing')
                        <span class="text-xs font-medium text-yellow-700"><i class="fas fa-circle text-yellow-500 mr-1"></i> In progress</span>
                    @endif
                </div>
                <h1 class="text-3xl font-bold text-gray-800">{{ $schedule->title }}</h1>
                <p class="text-gray-600 mt-2">
                    @if($class)
                        <a href="{{ route('admin.classes.show', $class) }}" class="text-blue-600 hover:text-blue-800">{{ $class->name }}</a>
                        <span class="text-gray-400 mx-1">|</span> Code: <strong>{{ $class->code }}</strong>
                    @else
                        <span class="text-gray-500">Class no longer available</span>
                    @endif
                    <span class="text-gray-400 mx-1">|</span> Tutor: <strong>{{ $schedule->tutor->user->name ?? 'N/A' }}</strong>
                </p>
                <p class="mt-1 {{ $schedule->start_time->isToday() ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                    <i class="far fa-calendar-alt mr-1"></i> {{ $schedule->start_time->format('D, M d, Y') }}
                    <span class="text-gray-400 mx-1">|</span>
                    <i class="far fa-clock mr-1"></i> {{ $schedule->start_time->format('h:i A') }} - {{ $schedule->end_time->format('h:i A') }}
                    <span class="text-xs text-gray-400 ml-2">({{ $schedule->start_time->diffForHumans() }})</span>
                </p>
                @if($schedule->description)
                    <p class="text-gray-500 text-sm mt-3 max-w-2xl">{{ $schedule->description }}</p>
                @endif
            </div>

            <!-- Primary Action -->
            <div class="lg:text-right shrink-0">
                @if($isUpcomingOrLive && $schedule->meeting_link)
                    <a href="{{ $schedule->meeting_link }}" target="_blank" rel="noopener"
                        class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-medium">
                        <i class="fas fa-video mr-2"></i> Join Class
                    </a>
                    <p class="text-xs text-gray-500 mt-2">Opens the meeting in a new tab</p>
                @elseif($isUpcomingOrLive)
                    <div class="inline-block bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-sm text-left">
                        <i class="fas fa-exclamation-triangle mr-1"></i> No meeting link set for this session.
                    </div>
                @elseif($schedule->status == 'completed' && $schedule->recording_url)
                    <a href="{{ $schedule->recording_url }}" target="_blank" rel="noopener"
                        class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-medium">
                        <i class="fas fa-play-circle mr-2"></i> View Recording
                    </a>
                    <p class="text-xs text-gray-500 mt-2">Opens in a new tab</p>
                @elseif($schedule->status == 'completed')
                    <p class="text-sm text-gray-500"><i class="fas fa-check-circle mr-1"></i> Session ended</p>
                @else
                    <p class="text-sm text-gray-500"><i class="fas fa-ban mr-1"></i> Session cancelled</p>
                @endif

                <div class="mt-3">
                    <a href="{{ route('admin.schedules.edit', $schedule) }}" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-edit mr-1"></i> Edit schedule
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Attendance -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b flex flex-wrap justify-between items-center gap-3">
                    <h2 class="text-lg font-semibold text-gray-800">Attendance
                        <span class="text-sm font-normal text-gray-500">({{ $attendanceRows->count() }} learner{{ $attendanceRows->count() == 1 ? '' : 's' }})</span>
                    </h2>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="px-2 py-1 rounded-full bg-green-100 text-green-800">Present {{ $attendanceSummary['present'] }}</span>
                        <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">Late {{ $attendanceSummary['late'] }}</span>
                        <span class="px-2 py-1 rounded-full bg-red-100 text-red-800">Absent {{ $attendanceSummary['absent'] }}</span>
                        <span class="px-2 py-1 rounded-full bg-blue-100 text-blue-800">Excused {{ $attendanceSummary['excused'] }}</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Learner</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marked</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($attendanceRows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-800">
                                        {{ $row['learner']->user->name ?? 'N/A' }}
                                        @unless($row['enrolled'])
                                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600" title="No longer enrolled in this class">Unenrolled</span>
                                        @endunless
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($row['record'])
                                            <span class="px-2 py-1 text-xs rounded-full font-medium
                                                @if($row['record']->status == 'present') bg-green-100 text-green-800
                                                @elseif($row['record']->status == 'absent') bg-red-100 text-red-800
                                                @elseif($row['record']->status == 'late') bg-yellow-100 text-yellow-800
                                                @elseif($row['record']->status == 'excused') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($row['record']->status) }}
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-500">Not marked</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['record']->notes ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $row['record'] && $row['record']->marked_at ? $row['record']->marked_at->format('M d, h:i A') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3 block"></i>
                                        No learners are enrolled in this class yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t bg-gray-50">
                    <a href="{{ route('admin.oversight.attendance') }}" class="text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-clipboard-check mr-1"></i> View all attendance records
                    </a>
                </div>
            </div>
        </div>

        <!-- Session Details -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Session Details</h2>
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd class="mt-1"><span class="px-2 py-1 text-xs rounded-full font-medium {{ $statusStyle }}">{{ ucfirst($schedule->status) }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Meeting platform</dt>
                        <dd class="mt-1 text-gray-800">{{ $platformLabel ?? 'Not specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Meeting link</dt>
                        <dd class="mt-1">
                            @if($schedule->meeting_link)
                                <input id="meeting-link" type="text" readonly value="{{ $schedule->meeting_link }}" class="block w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-600 bg-gray-50">
                                <button type="button" id="copy-meeting-link" class="mt-2 inline-flex items-center text-xs text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-copy mr-1"></i> Copy link
                                </button>
                            @else
                                <span class="text-gray-500">Not set</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Recording</dt>
                        <dd class="mt-1">
                            @if($schedule->recording_url)
                                <a href="{{ $schedule->recording_url }}" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-800"><i class="fas fa-play-circle mr-1"></i> Watch recording</a>
                            @else
                                <span class="text-gray-500">Not available</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Created</dt>
                        <dd class="mt-1 text-gray-800">{{ $schedule->created_at ? $schedule->created_at->format('M d, Y h:i A') : '-' }}</dd>
                    </div>
                </dl>
            </div>

            @if($class)
            <div class="bg-white rounded-xl shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Class Records</h2>
                <ul class="space-y-3 text-sm">
                    <li>
                        <a href="{{ route('admin.oversight.assignments.class', $class->id) }}" class="flex items-center justify-between text-gray-700 hover:text-blue-600">
                            <span><i class="fas fa-clipboard-list mr-2 text-green-600 w-4"></i> Assignments</span>
                            <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">{{ $assignmentCount }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.oversight.materials.class', $class->id) }}" class="flex items-center justify-between text-gray-700 hover:text-blue-600">
                            <span><i class="fas fa-folder-open mr-2 text-blue-600 w-4"></i> Materials</span>
                            <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">{{ $materialCount }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.oversight.attendance.class', $class->id) }}" class="flex items-center justify-between text-gray-700 hover:text-blue-600">
                            <span><i class="fas fa-user-check mr-2 text-purple-600 w-4"></i> Class attendance</span>
                            <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.classes.show', $class) }}" class="flex items-center justify-between text-gray-700 hover:text-blue-600">
                            <span><i class="fas fa-chalkboard mr-2 text-yellow-600 w-4"></i> Class &amp; enrolled learners</span>
                            <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                        </a>
                    </li>
                </ul>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var button = document.getElementById('copy-meeting-link');
    var input = document.getElementById('meeting-link');
    if (!button || !input) return;

    button.addEventListener('click', function () {
        var done = function () {
            var original = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check mr-1"></i> Copied';
            setTimeout(function () { button.innerHTML = original; }, 1500);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value).then(done);
        } else {
            input.select();
            document.execCommand('copy');
            done();
        }
    });
});
</script>
@endpush

