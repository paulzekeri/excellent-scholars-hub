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
                <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 ml-2">{{ $parentUnreadMessages }}</span>
            @endif
        </a>
    </div>

    @include('partials.affirmation')

    @if($totalMissedClasses > 0)
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-start">
            <i class="fas fa-exclamation-triangle mt-1 mr-3"></i>
            <div>
                <p class="font-semibold">{{ $totalMissedClasses }} missed class{{ $totalMissedClasses === 1 ? '' : 'es' }} recorded</p>
                <p class="text-sm">Open a child below and choose <strong>Attendance</strong> to see which sessions were missed.</p>
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
                           class="bg-red-500 hover:bg-red-600 text-white text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap transition">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $stats['missedClasses'] }} missed
                        </a>
                    @endif
                </div>

                <div class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600"><i class="fas fa-envelope mr-2"></i>{{ $child->user->email }}</p>
                        <p class="text-sm text-gray-600"><i class="fas fa-phone mr-2"></i>{{ $child->phone ?? 'N/A' }}</p>
                    </div>

                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Tap a card to see the details</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
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
                           class="block rounded-lg border border-gray-200 p-3 hover:border-yellow-400 hover:bg-yellow-50 transition"
                           title="View pending assignments">
                            <div class="flex items-center justify-between">
                                <i class="fas fa-hourglass-half text-yellow-500"></i>
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
                    </div>

                    <a href="{{ route('parent.payments.index') }}"
                       class="mb-5 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 p-3 text-sm hover:border-orange-400 hover:bg-orange-50 transition"
                       title="Record a payment or check what the school has verified">
                        <span class="text-gray-700">
                            <i class="fas fa-file-invoice-dollar text-orange-500 mr-2"></i>
                            @if($stats['fees']['fees'] === 0)
                                No fee recorded yet
                            @else
                                Fees: <strong>₦{{ number_format($stats['fees']['paid'], 2) }}</strong> verified
                                @if($stats['fees']['balance'] > 0)
                                    · <span class="font-semibold text-red-600">₦{{ number_format($stats['fees']['balance'], 2) }} outstanding</span>
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
                                <span class="font-semibold {{ $stats['attendanceRate'] >= 75 ? 'text-green-600' : 'text-red-600' }}">{{ $stats['attendanceRate'] }}%</span>
                                <span class="text-gray-400">({{ $stats['attendanceAttended'] }}/{{ $stats['attendanceRecorded'] }} sessions)</span>
                            @else
                                <span class="text-gray-400">No records yet</span>
                            @endif
                        </a>
                        @if($stats['missedClasses'] > 0)
                            <span class="text-red-600 font-medium">
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
                <i class="fas fa-child text-gray-400 text-6xl mb-4"></i>
                <p class="text-gray-500 text-lg">No children linked to your account yet.</p>
                <p class="text-gray-400">Please contact the administrator.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
