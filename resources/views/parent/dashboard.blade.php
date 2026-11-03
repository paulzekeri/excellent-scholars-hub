@extends('layouts.app')

@section('title', 'Parent Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Parent Dashboard</h1>
            <p class="text-gray-600 mt-1">Monitor your children's progress at a glance</p>
        </div>
        <a href="{{ route('messages.index') }}" class="inline-flex items-center justify-center bg-white border border-blue-600 text-blue-700 px-5 py-2 rounded-lg hover:bg-blue-50 transition">
            <i class="fas fa-comments mr-2"></i> Message the School
            @php $parentUnreadMessages = auth()->user()->unreadMessagesCount(); @endphp
            @if($parentUnreadMessages > 0)
                <span class="bg-sun text-[#0a2550] text-xs rounded-full px-2 py-0.5 ml-2">{{ $parentUnreadMessages }}</span>
            @endif
        </a>
    </div>

    @include('partials.affirmation')

    @if($children->isNotEmpty())
        <div class="bg-white rounded-xl shadow-md p-5 mb-6">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-id-card text-blue-600 mr-2"></i>Children &amp; logins
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Each child signs in with their own username. A reset link is emailed to you at
                <strong>{{ auth()->user()->email }}</strong> — share it with your child privately; it works once and expires in 60 minutes.
            </p>
            <ul class="mt-4 space-y-2">
                @foreach($children as $child)
                    <li class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-gray-50 rounded-lg px-4 py-3">
                        <span class="text-sm text-gray-700">
                            {{ $child->user->name }}
                            <span class="ml-1 font-mono text-gray-800">{{ $child->user->username ?: 'no username yet' }}</span>
                            @if($child->user->email)
                                <span class="text-xs text-gray-500">({{ $child->user->email }})</span>
                            @endif
                        </span>
                        <form action="{{ route('parent.children.password-reset', $child) }}" method="POST"
                            onsubmit="return confirm('Email a one-time reset link for this child to your address?')">
                            @csrf
                            <button type="submit" class="text-sm text-orange-700 hover:text-orange-900">
                                <i class="fas fa-key mr-1"></i> Send reset link
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($totalMissedClasses > 0)
        <div class="bg-orange-50 border-l-4 border-orange-500 text-orange-800 p-4 rounded-lg mb-6 flex items-start">
            <i class="fas fa-exclamation-triangle mt-1 mr-3"></i>
            <div>
                <p class="font-semibold">{{ $totalMissedClasses }} missed class{{ $totalMissedClasses === 1 ? '' : 'es' }} recorded</p>
                <p class="text-sm">Open a child below and choose <strong>Attendance</strong> to see which sessions were missed.</p>
            </div>
        </div>
    @endif

    @if($sharedGoals->isNotEmpty())
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-bullseye text-orange-700 mr-2"></i>Learning goals shared with you
                    </h2>
                    <p class="text-sm text-gray-500">
                        Goals your children's tutors set and the school approved for you.
                        @if($sharedGoalsCount > $sharedGoals->count())
                            Showing the latest {{ $sharedGoals->count() }} of {{ $sharedGoalsCount }}.
                        @endif
                    </p>
                </div>
                <span class="self-start rounded-full bg-orange-100 px-3 py-1 text-sm font-semibold text-orange-700">
                    {{ $sharedGoalsCount }} goal{{ $sharedGoalsCount === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="space-y-4">
                @foreach($sharedGoals as $goal)
                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                @include('partials.goal-text', ['goal' => $goal, 'limit' => 220])

                                <p class="mt-2 text-sm text-gray-500">
                                    @if($goal->learner)
                                        <a href="{{ route('parent.child.progress', ['learner' => $goal->learner, 'tab' => 'goals']) }}"
                                           class="font-medium text-blue-700 hover:text-orange-700">{{ $goal->learner->user->name ?? 'Learner' }}</a>
                                    @endif
                                    @if($goal->schoolClass) · {{ $goal->schoolClass->name }} @endif
                                    @if($goal->term) · {{ $goal->term }} @endif
                                </p>

                                <div class="mt-2 flex flex-wrap items-center gap-4 text-xs text-gray-500">
                                    <span><i class="fas fa-chalkboard-teacher mr-1"></i>{{ $goal->tutor->user->name ?? 'Tutor' }}</span>
                                    @if($goal->shared_at)
                                        <span><i class="far fa-calendar mr-1"></i>Shared {{ $goal->shared_at->format('M d, Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            @if($goal->file_path)
                                <a href="{{ asset('storage/' . $goal->file_path) }}" target="_blank"
                                   class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-white transition hover:bg-blue-800">
                                    <i class="fas fa-download mr-1"></i> Attachment
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @forelse($children as $child)
            @php $stats = $childStats[$child->id]; @endphp
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition">
                <div class="bg-blue-600 px-6 py-4 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-white text-lg font-semibold">{{ $child->user->name }}</h3>
                        <p class="text-blue-200 text-sm">
                            Grade: {{ $child->grade_level ?? 'N/A' }}
                            · {{ $stats['enrolledClasses'] }} enrolled class{{ $stats['enrolledClasses'] === 1 ? '' : 'es' }}
                        </p>
                    </div>
                    @if($stats['missedClasses'] > 0)
                        <a href="{{ route('parent.child.progress', ['learner' => $child, 'tab' => 'attendance']) }}"
                           class="bg-orange-500 hover:bg-orange-400 text-blue-950 text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap transition">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $stats['missedClasses'] }} missed
                        </a>
                    @endif
                </div>

                <div class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600"><i class="fas fa-envelope mr-2"></i>{{ $child->user->email }}</p>
                        <p class="text-sm text-gray-600"><i class="fas fa-phone mr-2"></i>{{ $child->phone ?? 'N/A' }}</p>
                    </div>

                    <p class="text-xs font-semibold text-[#0a2550] uppercase tracking-wide mb-2">Tap a card to see the details</p>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
                        <a href="{{ route('parent.child.progress', ['learner' => $child, 'tab' => 'schedule']) }}"
                           class="block rounded-lg border border-gray-200 p-3 hover:border-blue-400 hover:bg-blue-50 transition"
                           title="View {{ $child->user->name }}'s class schedule">
                            <div class="flex items-center justify-between">
                                <i class="fas fa-calendar-alt text-blue-500"></i>
                                <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
                            </div>
                            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['upcomingClasses'] }}</p>
                            <p class="text-xs text-gray-500 leading-tight">Class Schedule</p>
                        </a>
                        <a href="{{ route('parent.child.progress', ['learner' => $child, 'tab' => 'pending']) }}"
                           class="block rounded-lg border border-gray-200 p-3 hover:border-sun hover:bg-sun-light transition"
                           title="View pending assignments">
                            <div class="flex items-center justify-between">
                                <i class="fas fa-hourglass-half text-sun-ink"></i>
                                <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
                            </div>
                            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['pendingAssignments'] }}</p>
                            <p class="text-xs text-gray-500 leading-tight">Assignments Pending</p>
                        </a>
                        <a href="{{ route('parent.child.progress', ['learner' => $child, 'tab' => 'completed']) }}"
                           class="block rounded-lg border border-gray-200 p-3 hover:border-green-400 hover:bg-green-50 transition"
                           title="View completed assignments">
                            <div class="flex items-center justify-between">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
                            </div>
                            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['completedAssignments'] }}</p>
                            <p class="text-xs text-gray-500 leading-tight">Assignments Completed</p>
                        </a>
                        <a href="{{ route('parent.child.progress', ['learner' => $child, 'tab' => 'materials']) }}"
                           class="block rounded-lg border border-gray-200 p-3 hover:border-blue-400 hover:bg-blue-50 transition"
                           title="View shared learning materials">
                            <div class="flex items-center justify-between">
                                <i class="fas fa-folder-open text-blue-500"></i>
                                <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
                            </div>
                            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['materials'] }}</p>
                            <p class="text-xs text-gray-500 leading-tight">Materials Shared</p>
                        </a>
                        <a href="{{ route('parent.child.progress', ['learner' => $child, 'tab' => 'goals']) }}"
                           class="block rounded-lg border border-gray-200 p-3 hover:border-orange-400 hover:bg-orange-50 transition"
                           title="View learning goals shared with you for {{ $child->user->name }}">
                            <div class="flex items-center justify-between">
                                <i class="fas fa-bullseye text-orange-600"></i>
                                <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
                            </div>
                            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['goals'] }}</p>
                            <p class="text-xs text-gray-500 leading-tight">Learning Goals</p>
                        </a>
                    </div>

                    <a href="{{ route('parent.payments.index') }}"
                       class="mb-5 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 p-3 text-sm hover:border-orange-400 hover:bg-orange-50 transition"
                       title="Record a payment or check what the school has verified">
                        <span class="text-gray-700">
                            <i class="fas fa-file-invoice-dollar text-orange-700 mr-2"></i>
                            @if($stats['fees']['fees'] === 0)
                                No fee recorded yet
                            @else
                                Fees: <strong>₦{{ number_format($stats['fees']['paid'], 2) }}</strong> verified
                                @if($stats['fees']['balance'] > 0)
                                    · <span class="font-semibold text-orange-700">₦{{ number_format($stats['fees']['balance'], 2) }} outstanding</span>
                                @else
                                    · <span class="font-semibold text-green-600">fully paid</span>
                                @endif
                            @endif
                            @if($stats['fees']['pending_count'] > 0)
                                <span class="ml-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-800">{{ $stats['fees']['pending_count'] }} pending</span>
                            @endif
                        </span>
                        <span class="text-xs font-semibold text-blue-700">Record / view <i class="fas fa-chevron-right text-gray-300 ml-1"></i></span>
                    </a>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-t pt-4 text-sm">
                        <a href="{{ route('parent.child.progress', ['learner' => $child, 'tab' => 'attendance']) }}" class="text-gray-600 hover:text-blue-600">
                            <i class="fas fa-user-check mr-1 text-green-600"></i> Attendance:
                            @if($stats['attendanceRate'] !== null)
                                <span class="font-semibold {{ $stats['attendanceRate'] >= 75 ? 'text-green-600' : 'text-orange-700' }}">{{ $stats['attendanceRate'] }}%</span>
                                <span class="text-gray-500">({{ $stats['attendanceAttended'] }}/{{ $stats['attendanceRecorded'] }} sessions)</span>
                            @else
                                <span class="text-gray-500">No records yet</span>
                            @endif
                        </a>
                        @if($stats['missedClasses'] > 0)
                            <span class="text-orange-700 font-medium">
                                <i class="fas fa-calendar-times mr-1"></i>{{ $stats['missedClasses'] }} class{{ $stats['missedClasses'] === 1 ? '' : 'es' }} missed
                            </span>
                        @else
                            <span class="text-green-600 font-medium">
                                <i class="fas fa-check-circle mr-1"></i>No missed classes
                            </span>
                        @endif
                    </div>

                    <a href="{{ route('parent.child.progress', $child) }}" class="mt-4 block w-full text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-chart-line mr-1"></i> View Full Details
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <i class="fas fa-child text-gray-500 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg">No children linked to your account yet.</p>
                <p class="text-gray-500">Please contact the administrator.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
