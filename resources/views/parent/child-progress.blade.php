@extends('layouts.app')

@section('title', $learner->user->name . ' - Details')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('parent.dashboard') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">{{ $learner->user->name }}</h1>
                <p class="text-gray-600 mt-1">
                    Grade Level: {{ $learner->grade_level ?? 'N/A' }}
                    · {{ $stats['enrolledClasses'] }} class{{ $stats['enrolledClasses'] === 1 ? '' : 'es' }}
                    · {{ $learner->user->email }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($stats['attendanceRate'] !== null)
                    <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $stats['attendanceRate'] >= 75 ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-800' }}">
                        <i class="fas fa-user-check mr-1"></i>{{ $stats['attendanceRate'] }}% attendance
                    </span>
                @endif
                @if($stats['missedClasses'] > 0)
                    <a href="{{ route('parent.child.progress', ['learner' => $learner, 'tab' => 'attendance']) }}"
                       class="px-3 py-1 rounded-full text-sm font-semibold bg-orange-100 text-orange-800 hover:bg-orange-200 transition">
                        <i class="fas fa-exclamation-triangle mr-1"></i>{{ $stats['missedClasses'] }} class{{ $stats['missedClasses'] === 1 ? '' : 'es' }} missed
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if($missedClasses->isNotEmpty())
        <div class="bg-orange-50 border-l-4 border-orange-500 rounded-lg p-4 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <p class="font-semibold text-orange-800">
                        <i class="fas fa-calendar-times mr-2"></i>{{ $missedClasses->count() }} missed class{{ $missedClasses->count() === 1 ? '' : 'es' }}
                    </p>
                    <p class="text-sm text-orange-700 mt-1">
                        @foreach($missedClasses->take(3) as $record)
                            {{ $record->classSchedule->class->name ?? 'Class' }} on {{ \Carbon\Carbon::parse($record->classSchedule->start_time)->format('M d, Y') }}@if(! $loop->last), @endif
                        @endforeach
                        @if($missedClasses->count() > 3)
                            and {{ $missedClasses->count() - 3 }} more
                        @endif
                    </p>
                </div>
                <a href="{{ route('parent.child.progress', ['learner' => $learner, 'tab' => 'attendance']) }}"
                   class="text-sm text-orange-800 font-semibold hover:underline whitespace-nowrap">
                    View attendance <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6">
        @include('parent.partials.stat-card', [
            'href' => route('parent.child.progress', ['learner' => $learner, 'tab' => 'schedule']),
            'count' => $stats['weekSessions'],
            'label' => "This Week's Sessions",
            'icon' => 'fa-calendar-alt',
            'iconClass' => 'text-blue-500',
            'active' => $tab === 'schedule',
        ])
        @include('parent.partials.stat-card', [
            'href' => route('parent.child.progress', ['learner' => $learner, 'tab' => 'pending']),
            'count' => $stats['pendingAssignments'],
            'label' => 'Assignments Pending',
            'icon' => 'fa-hourglass-half',
            'iconClass' => 'text-sun-ink',
            'active' => $tab === 'pending',
        ])
        @include('parent.partials.stat-card', [
            'href' => route('parent.child.progress', ['learner' => $learner, 'tab' => 'completed']),
            'count' => $stats['completedAssignments'],
            'label' => 'Assignments Completed',
            'icon' => 'fa-check-circle',
            'iconClass' => 'text-green-500',
            'active' => $tab === 'completed',
        ])
        @include('parent.partials.stat-card', [
            'href' => route('parent.child.progress', ['learner' => $learner, 'tab' => 'materials']),
            'count' => $stats['materials'],
            'label' => 'Materials Shared',
            'icon' => 'fa-folder-open',
            'iconClass' => 'text-blue-500',
            'active' => $tab === 'materials',
        ])
        @include('parent.partials.stat-card', [
            'href' => route('parent.child.progress', ['learner' => $learner, 'tab' => 'attendance']),
            'count' => $stats['missedClasses'],
            'label' => 'Classes Missed',
            'icon' => 'fa-calendar-times',
            'iconClass' => $stats['missedClasses'] > 0 ? 'text-orange-700' : 'text-gray-500',
            'active' => $tab === 'attendance',
        ])
        @include('parent.partials.stat-card', [
            'href' => route('parent.child.progress', ['learner' => $learner, 'tab' => 'goals']),
            'count' => $stats['goals'],
            'label' => 'Learning Goals',
            'icon' => 'fa-bullseye',
            'iconClass' => 'text-orange-700',
            'active' => $tab === 'goals',
        ])
    </div>

    <div class="border-b border-gray-200 mb-6 overflow-x-auto">
        <nav class="flex space-x-6 min-w-max">
            @foreach($tabs as $key => $label)
                <a href="{{ route('parent.child.progress', ['learner' => $learner, 'tab' => $key]) }}"
                   class="pb-3 px-1 border-b-2 text-sm font-medium transition {{ $tab === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>

    @if($tab === 'overview')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Upcoming classes -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Upcoming Classes
                    </h2>
                    <a href="{{ route('parent.child.progress', ['learner' => $learner, 'tab' => 'schedule']) }}" class="text-sm text-blue-600 hover:text-blue-800">
                        View all <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="space-y-3">
                    @forelse($upcomingClasses as $schedule)
                        <div class="border-l-4 border-blue-500 bg-blue-50 p-4 rounded-r-lg">
                            <p class="font-semibold text-gray-800">{{ $schedule->title }}</p>
                            <p class="text-sm text-gray-600">{{ $schedule->class->name ?? 'Class' }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="far fa-calendar-alt mr-1"></i>{{ $schedule->start_time->format('D, M d, Y') }}
                                · <i class="far fa-clock mr-1"></i>{{ $schedule->start_time->format('h:i A') }} - {{ $schedule->end_time->format('h:i A') }}
                            </p>
                            <p class="text-xs text-gray-500">
                                <i class="fas fa-user mr-1"></i>{{ $schedule->class->tutor->user->name ?? 'Tutor to be assigned' }}
                            </p>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm text-center py-6">No upcoming classes scheduled.</p>
                    @endforelse
                </div>
            </div>

            <!-- Pending assignments -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-hourglass-half text-sun-ink mr-2"></i>Pending Assignments
                    </h2>
                    <a href="{{ route('parent.child.progress', ['learner' => $learner, 'tab' => 'pending']) }}" class="text-sm text-blue-600 hover:text-blue-800">
                        View all <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="space-y-3">
                    @forelse($pendingAssignments as $assignment)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex items-start justify-between gap-2">
                                <p class="font-medium text-gray-800 text-sm">{{ $assignment->title }}</p>
                                @if($assignment->due_date->isPast())
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-orange-100 text-orange-800 whitespace-nowrap">Overdue</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $assignment->class->name ?? 'Class' }} · Due {{ $assignment->due_date->format('M d, Y h:i A') }}
                            </p>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm text-center py-6">All assignments are completed. Well done!</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent attendance -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-user-check text-green-500 mr-2"></i>Recent Attendance
                </h2>
                <a href="{{ route('parent.child.progress', ['learner' => $learner, 'tab' => 'attendance']) }}" class="text-sm text-blue-600 hover:text-blue-800">
                    View all <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($recentAttendance as $record)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ \Carbon\Carbon::parse($record->classSchedule->start_time)->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm">{{ $record->classSchedule->class->name ?? 'Class' }}</td>
                                <td class="px-4 py-3">@include('parent.partials.attendance-status', ['status' => $record->status])</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $record->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-3 text-center text-gray-500">No attendance records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($tab === 'schedule')
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Class Schedule
                    </h2>
                    <p class="text-sm text-gray-500">
                        @if($scheduleScope === 'all')
                            All sessions for {{ $learner->user->name }}
                        @else
                            Sessions for {{ $scheduleWeekStart->format('D, M d') }} – {{ $scheduleWeekEnd->format('D, M d, Y') }}
                        @endif
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-700 font-semibold self-start">
                    @if($scheduleScope === 'all')
                        {{ $upcomingClasses->total() }} session{{ $upcomingClasses->total() === 1 ? '' : 's' }}
                    @else
                        {{ $upcomingClasses->count() }} this week
                    @endif
                </span>
            </div>

            @if($scheduleScope === 'week')
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('parent.child.progress', ['learner' => $learner, 'tab' => 'schedule', 'week' => $scheduleWeekStart->copy()->subWeek()->toDateString()]) }}" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200 transition"><i class="fas fa-chevron-left mr-1"></i> Previous week</a>
                        @unless($scheduleWeekStart->isCurrentWeek())
                            <a href="{{ route('parent.child.progress', ['learner' => $learner, 'tab' => 'schedule']) }}" class="bg-blue-700 text-white px-3 py-2 rounded-lg text-sm hover:bg-blue-800 transition"><i class="fas fa-calendar-day mr-1"></i> This week</a>
                        @endunless
                        <a href="{{ route('parent.child.progress', ['learner' => $learner, 'tab' => 'schedule', 'week' => $scheduleWeekStart->copy()->addWeek()->toDateString()]) }}" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200 transition">Next week <i class="fas fa-chevron-right ml-1"></i></a>
                    </div>
                    <a href="{{ route('parent.child.progress', ['learner' => $learner, 'tab' => 'schedule', 'view' => 'all']) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">
                        <i class="fas fa-list mr-1"></i>All sessions
                    </a>
                </div>
            @else
                <div class="mb-4">
                    <a href="{{ route('parent.child.progress', ['learner' => $learner, 'tab' => 'schedule']) }}" class="inline-flex items-center text-sm font-semibold text-blue-700 hover:text-blue-900">
                        <i class="fas fa-calendar-week mr-1"></i>Back to this week
                    </a>
                </div>
            @endif
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Date &amp; Time</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Session</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Subject</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Tutor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($upcomingClasses as $schedule)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">
                                    <p class="font-medium text-gray-800">{{ $schedule->start_time->format('D, M d, Y') }}</p>
                                    <p class="text-xs text-gray-500">{{ $schedule->start_time->format('h:i A') }} - {{ $schedule->end_time->format('h:i A') }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-800">{{ $schedule->title }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $schedule->subjectName() }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $schedule->class->name ?? 'Class' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $schedule->subjectTeacherName() ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($schedule->status === 'ongoing') bg-green-100 text-green-800
                                        @elseif($schedule->status === 'cancelled') bg-orange-100 text-orange-900
                                        @elseif($schedule->status === 'completed') bg-gray-100 text-gray-700
                                        @else bg-blue-100 text-blue-800 @endif">
                                        {{ ucfirst($schedule->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    {{ $scheduleScope === 'all' ? 'No sessions recorded yet.' : 'No sessions scheduled for this week.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($scheduleScope === 'all')
                <div class="mt-4">{{ $upcomingClasses->links() }}</div>
            @endif
        </div>
    @endif

    @if($tab === 'pending')
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-hourglass-half text-sun-ink mr-2"></i>Pending Assignments
                    </h2>
                    <p class="text-sm text-gray-500">Assignments {{ $learner->user->name }} has not submitted yet</p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm bg-sun-light text-sun-ink font-semibold self-start">
                    {{ $pendingAssignments->total() }} pending
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Assignment</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Due Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Points</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($pendingAssignments as $assignment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">
                                    <p class="font-medium text-gray-800">{{ $assignment->title }}</p>
                                    @if($assignment->tutor)
                                        <p class="text-xs text-gray-500">{{ $assignment->tutor->user->name ?? '' }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $assignment->class->name ?? 'Class' }}</td>
                                <td class="px-4 py-3 text-sm {{ $assignment->due_date->isPast() ? 'text-orange-700 font-medium' : 'text-gray-600' }}">
                                    {{ $assignment->due_date->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $assignment->total_points }}</td>
                                <td class="px-4 py-3">
                                    @if($assignment->due_date->isPast())
                                        <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-900">Overdue</span>
                                    @elseif($assignment->status === 'closed')
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">Closed</span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-sun-light text-sun-ink">Not submitted</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No pending assignments. Everything has been submitted.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $pendingAssignments->links() }}</div>
        </div>
    @endif

    @if($tab === 'completed')
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>Completed Assignments
                    </h2>
                    <p class="text-sm text-gray-500">Work {{ $learner->user->name }} has submitted, including scores and feedback</p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm bg-green-100 text-green-700 font-semibold self-start">
                    {{ $completedSubmissions->total() }} submitted
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Assignment</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Submitted</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Score</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Feedback</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($completedSubmissions as $submission)
                            @php $assignment = $submission->assignment; @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">
                                    <p class="font-medium text-gray-800">{{ $assignment->title ?? 'Assignment removed' }}</p>
                                    <p class="text-xs text-gray-500">{{ $assignment->tutor->user->name ?? 'N/A' }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $assignment->class->name ?? 'Class' }}</td>
                                <td class="px-4 py-3 text-sm text-green-700">
                                    {{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y h:i A') : $submission->created_at->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold">
                                    @if($submission->score !== null && $assignment)
                                        <span class="{{ $submission->score >= ($assignment->total_points / 2) ? 'text-green-600' : 'text-orange-700' }}">
                                            {{ $submission->score }} / {{ $assignment->total_points }}
                                        </span>
                                    @elseif($submission->status === 'graded')
                                        <span class="text-gray-500">Graded</span>
                                    @else
                                        <span class="text-sun-ink">Pending grading</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($submission->status === 'graded') bg-green-100 text-green-800
                                        @elseif($submission->status === 'submitted') bg-blue-100 text-blue-800
                                        @elseif($submission->status === 'resubmitted') bg-sun-light text-sun-ink
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst($submission->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm max-w-xs">
                                    @if($submission->feedback)
                                        <div class="bg-gray-50 rounded p-2 text-gray-700 text-xs">
                                            <span class="font-medium text-blue-600">Tutor:</span> {{ $submission->feedback }}
                                            @if($submission->graded_at)
                                                <div class="mt-1 text-gray-500">Graded on {{ $submission->graded_at->format('M d, Y') }}</div>
                                            @endif
                                        </div>
                                    @elseif($submission->status === 'graded')
                                        <span class="text-gray-500 italic">No feedback provided</span>
                                    @else
                                        <span class="text-sun-ink text-xs">Awaiting grading</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No completed assignments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $completedSubmissions->links() }}</div>
        </div>
    @endif

    @if($tab === 'materials')
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-folder-open text-blue-500 mr-2"></i>Materials Shared
                    </h2>
                    <p class="text-sm text-gray-500">Learning materials uploaded for {{ $learner->user->name }}'s classes</p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-700 font-semibold self-start">
                    {{ $materials->total() }} available
                </span>
            </div>

            <div class="space-y-4">
                @forelse($materials as $material)
                    @php $ext = $material->file_type ? strtoupper(last(explode('/', $material->file_type))) : 'FILE'; @endphp
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-semibold text-gray-800">{{ $material->title }}</h3>
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">{{ $ext }}</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $material->class->name ?? 'Class' }} · {{ $material->tutor->user->name ?? 'Tutor' }}
                                </p>
                                @if($material->description)
                                    <p class="text-sm text-gray-600 mt-2">{{ $material->description }}</p>
                                @endif
                                <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                    @if($material->file_size)
                                        <span>
                                            <i class="far fa-file mr-1"></i>
                                            {{ $material->file_size >= 1048576 ? round($material->file_size / 1048576, 2) . ' MB' : round($material->file_size / 1024, 1) . ' KB' }}
                                        </span>
                                    @endif
                                    <span><i class="far fa-calendar mr-1"></i>{{ $material->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $material->file_path) }}" target="_blank"
                               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition inline-flex items-center justify-center">
                                <i class="fas fa-download mr-1"></i> Download
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No materials have been shared yet.</p>
                        <p class="text-gray-500 text-sm mt-1">Materials appear here when a tutor uploads them for your child's classes.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $materials->links() }}</div>
        </div>
    @endif

    @if($tab === 'attendance')
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-calendar-times text-orange-700 mr-2"></i>Classes Missed
                    </h2>
                    <p class="text-sm text-gray-500">Sessions {{ $learner->user->name }} was marked absent for</p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm font-semibold self-start {{ $missedClasses->count() > 0 ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-700' }}">
                    {{ $missedClasses->count() }} missed
                </span>
            </div>

            @if($missedClasses->isNotEmpty())
                <div class="space-y-2">
                    @foreach($missedClasses as $record)
                        <div class="border-l-4 border-orange-500 bg-orange-50 p-3 rounded-r-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <p class="font-medium text-gray-800">{{ $record->classSchedule->class->name ?? 'Class' }}</p>
                                <p class="text-xs text-gray-600">
                                    <i class="far fa-calendar mr-1"></i>{{ \Carbon\Carbon::parse($record->classSchedule->start_time)->format('D, M d, Y h:i A') }}
                                    · {{ $record->classSchedule->title ?? 'Session' }}
                                </p>
                            </div>
                            <span class="text-xs text-orange-800">{{ $record->notes ?: 'Marked absent' }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-3 rounded-r-lg">
                    <i class="fas fa-check-circle mr-2"></i>No missed classes recorded. Keep it up!
                </div>
            @endif
        </div>

        @if($unrecordedSessions->isNotEmpty())
            <div class="bg-sun-light border-l-4 border-sun text-sun-ink p-4 rounded-r-lg mb-6">
                <p class="font-medium">
                    <i class="fas fa-info-circle mr-2"></i>{{ $unrecordedSessions->count() }} past session{{ $unrecordedSessions->count() === 1 ? '' : 's' }} without recorded attendance
                </p>
                <p class="text-sm mt-1">The tutor has not marked attendance for these sessions yet, so they are not counted as missed.</p>
                <details class="mt-2">
                    <summary class="cursor-pointer text-sm font-semibold hover:underline">Show sessions</summary>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach($unrecordedSessions as $session)
                            <li>
                                <i class="far fa-calendar mr-1"></i>
                                {{ $session->start_time->format('D, M d, Y h:i A') }}
                                — {{ $session->title }} ({{ $session->class->name ?? 'Class' }})
                            </li>
                        @endforeach
                    </ul>
                </details>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-user-check text-green-500 mr-2"></i>Attendance History
                    </h2>
                    <p class="text-sm text-gray-500">
                        @if($stats['attendanceRate'] !== null)
                            {{ $stats['attendanceAttended'] }} of {{ $stats['attendanceRecorded'] }} recorded sessions attended
                            ({{ $stats['attendanceRate'] }}%)
                        @else
                            No attendance has been recorded yet.
                        @endif
                    </p>
                </div>
                @if($stats['attendanceRate'] !== null)
                    <span class="px-3 py-1 rounded-full text-sm font-semibold self-start {{ $stats['attendanceRate'] >= 75 ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-800' }}">
                        {{ $stats['attendanceRate'] }}% attendance
                    </span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Session</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($attendance as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm">{{ \Carbon\Carbon::parse($record->classSchedule->start_time)->format('D, M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $record->classSchedule->title ?? 'Session' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $record->classSchedule->class->name ?? 'Class' }}</td>
                                <td class="px-4 py-3">@include('parent.partials.attendance-status', ['status' => $record->status])</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $record->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No attendance records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $attendance->links() }}</div>
        </div>
    @endif

    @if($tab === 'goals')
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-bullseye text-orange-700 mr-2"></i>Learning Goals shared with you
                    </h2>
                    <p class="text-sm text-gray-500">Goals {{ $learner->user->name }}'s tutors set and the school approved for parents</p>
                </div>
                <span class="px-3 py-1 rounded-full text-sm bg-orange-100 text-orange-700 font-semibold self-start">
                    {{ $goals->total() }} goal(s)
                </span>
            </div>

            <div class="space-y-4">
                @forelse($goals as $goal)
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800 whitespace-pre-line">{{ $goal->goalText() }}</h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $goal->learner->user->name ?? 'Whole class' }}
                                    @if($goal->schoolClass) · {{ $goal->schoolClass->name }} @endif
                                    @if($goal->term) · {{ $goal->term }} @endif
                                </p>
                                <div class="flex flex-wrap items-center gap-4 mt-2 text-xs text-gray-500">
                                    <span><i class="fas fa-chalkboard-teacher mr-1"></i>{{ $goal->tutor->user->name ?? 'Tutor' }}</span>
                                    @if($goal->shared_at)
                                        <span><i class="far fa-calendar mr-1"></i>Shared {{ $goal->shared_at->format('M d, Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            @if($goal->file_path)
                                <a href="{{ asset('storage/' . $goal->file_path) }}" target="_blank"
                                   class="bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition inline-flex items-center justify-center">
                                    <i class="fas fa-download mr-1"></i> Attachment
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10">
                        <i class="fas fa-bullseye text-5xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No learning goals have been shared yet.</p>
                        <p class="text-gray-500 text-sm mt-1">Tutors upload goals; the school shares the relevant ones with you.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $goals->links() }}</div>
        </div>
    @endif
</div>
@endsection

