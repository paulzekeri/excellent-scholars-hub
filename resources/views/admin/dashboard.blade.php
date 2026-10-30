@extends('layouts.app')

@section('title', auth()->user()->isCeo() ? 'CEO Dashboard' : 'Admin Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">{{ auth()->user()->isCeo() ? 'CEO Dashboard' : 'Admin Dashboard' }}</h1>
        <p class="text-gray-600 mt-1">Overview of the Excellent Scholars Hub platform</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Users</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalUsers }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Tutors</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalTutors }}</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-chalkboard-teacher text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Learners</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalLearners }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-user-graduate text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Classes</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalClasses }}</p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-chalkboard text-yellow-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Schedules</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalSchedules }}</p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-calendar text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        @if(auth()->user()->canManagePayments())
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-pink-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Payments</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalPayments }}</p>
                </div>
                <div class="bg-pink-100 p-3 rounded-full">
                    <i class="fas fa-money-bill text-pink-600 text-xl"></i>
                </div>
            </div>
        </div>
        @endif

        <a href="{{ route('messages.index') }}" class="bg-white rounded-xl shadow-md p-6 border-l-4 border-teal-500 hover:bg-teal-50 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Unread Messages</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $unreadMessages }}</p>
                </div>
                <div class="bg-teal-100 p-3 rounded-full">
                    <i class="fas fa-comments text-teal-600 text-xl"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.enquiries.index', ['status' => 'new']) }}" class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-400 hover:bg-blue-50 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">New Enquiries</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $newEnquiries }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-user-plus text-blue-600 text-xl"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.enquiries.index', ['status' => 'contacted']) }}" class="bg-white rounded-xl shadow-md p-6 border-l-4 border-amber-400 hover:bg-amber-50 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Enquiries In Progress</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $contactedEnquiries }}</p>
                </div>
                <div class="bg-amber-100 p-3 rounded-full">
                    <i class="fas fa-phone text-amber-600 text-xl"></i>
                </div>
            </div>
        </a>
    </div>

    @include('partials.affirmation')

    <!-- Today's assessment calls -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-video text-blue-600 mr-2"></i>Today's assessment calls</h2>
                <p class="text-sm text-gray-500">{{ $upcomingAssessments }} more scheduled after today.</p>
            </div>
            <a href="{{ route('admin.assessments.index') }}" class="text-sm text-blue-700 hover:text-blue-900">
                All assessments <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        @if($todayAssessments->isEmpty())
            <p class="text-sm text-gray-500">No assessment calls today. New bookings appear on <a href="{{ route('admin.assessments.index') }}" class="text-blue-700">Assessments</a>.</p>
        @else
            <ul class="space-y-2">
                @foreach($todayAssessments as $assessment)
                    <li class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-gray-50 rounded-lg px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800">
                                {{ $assessment->scheduled_at->format('g:i a') }} — {{ optional($assessment->enquiry)->child_name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ optional(optional($assessment->tutor)->user)->name }} ·
                                {{ $assessment->platformLabel() }} ·
                                <span class="font-mono">{{ optional($assessment->enquiry)->reference }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($assessment->enquiry)
                                <a href="{{ route('admin.enquiries.show', $assessment->enquiry) }}" class="text-xs text-gray-600 hover:text-blue-700">Open enquiry</a>
                            @endif
                            @if($assessment->joinUrl())
                                <a href="{{ $assessment->joinUrl() }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs hover:bg-blue-700">
                                    <i class="fas fa-video mr-1"></i> Join
                                </a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <!-- Monthly classes per tutor -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Classes per tutor — {{ $monthStart->format('F Y') }}</h2>
                <p class="text-sm text-gray-500">
                    {{ $monthCompleted }} completed of {{ $monthSessions }} scheduled across {{ $tutorMonthly->count() }} tutor(s).
                    <a href="{{ route('admin.schedules.index') }}" class="text-blue-700 hover:text-blue-900">Tick a class as completed &rarr;</a>
                </p>
            </div>
            <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                <input type="month" name="month" value="{{ $monthStart->format('Y-m') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                <button type="submit" class="bg-blue-700 text-white px-3 py-2 rounded-lg text-sm hover:bg-blue-800 transition"><i class="fas fa-calendar-day mr-1"></i> View</button>
            </form>
        </div>
        <div class="space-y-3">
            @forelse($tutorMonthly as $tutor)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-800">{{ $tutor->user->name ?? 'Tutor #' . $tutor->id }}</span>
                        <span class="{{ $tutor->month_completed > 0 ? 'text-green-700 font-semibold' : 'text-gray-400' }}">{{ $tutor->month_completed }} completed <span class="font-normal text-gray-400">of {{ $tutor->month_sessions }} scheduled</span></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5">
                        <div class="{{ $tutor->month_completed > 0 ? 'bg-green-600' : 'bg-gray-200' }} h-2.5 rounded-full" style="width: {{ round($tutor->month_completed / $maxMonthCompleted * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No tutors yet.</p>
            @endforelse
        </div>
        <div class="mt-6 border-t border-gray-100 pt-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Last 6 months (completed classes)</p>
            <div class="flex items-end gap-3 h-24">
                @php $trendMax = max(1, (int) $monthlyTrend->max('count')); @endphp
                @foreach($monthlyTrend as $point)
                    <div class="flex-1 flex flex-col items-center justify-end h-full">
                        <span class="text-xs font-semibold text-gray-700">{{ $point['count'] }}</span>
                        <div class="w-full bg-orange-400 rounded-t" style="height: {{ $point['count'] > 0 ? max(6, round($point['count'] / $trendMax * 60)) : 2 }}px"></div>
                        <span class="text-xs text-gray-500 mt-1">{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Users -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Recent Users</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($recentUsers as $user)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-800">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                                <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full 
                                    @if($user->role->slug == 'ceo') bg-purple-100 text-purple-800
                                    @elseif($user->role->slug == 'admin') bg-red-100 text-red-800
                                    @elseif($user->role->slug == 'tutor') bg-green-100 text-green-800
                                    @elseif($user->role->slug == 'learner') bg-blue-100 text-blue-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ ucfirst($user->role->name) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-3 text-center text-gray-500">No users yet</td></tr>
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
                    <a href="{{ route('admin.schedules.show', $schedule) }}" class="block border-l-4 border-blue-500 bg-blue-50 p-4 rounded-r-lg hover:bg-blue-100 transition">
                        <div class="flex justify-between items-start gap-3">
                            <div>
                                <p class="font-semibold text-gray-800">{{ $schedule->title }}</p>
                                <p class="text-sm text-gray-600">{{ $schedule->class->name }} - {{ $schedule->tutor->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($schedule->start_time)->format('M d, Y h:i A') }}</p>
                            </div>
                            <i class="fas fa-chevron-right text-blue-400 mt-1"></i>
                        </div>
                    </a>
                @empty
                    <p class="text-gray-500 text-center">No upcoming classes</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
