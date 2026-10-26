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
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-indigo-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total Users</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalUsers }}</p>
                </div>
                <div class="bg-indigo-100 p-3 rounded-full">
                    <i class="fas fa-users text-indigo-600 text-xl"></i>
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
                    <a href="{{ route('admin.schedules.show', $schedule) }}" class="block border-l-4 border-indigo-500 bg-indigo-50 p-4 rounded-r-lg hover:bg-indigo-100 transition">
                        <div class="flex justify-between items-start gap-3">
                            <div>
                                <p class="font-semibold text-gray-800">{{ $schedule->title }}</p>
                                <p class="text-sm text-gray-600">{{ $schedule->class->name }} - {{ $schedule->tutor->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($schedule->start_time)->format('M d, Y h:i A') }}</p>
                            </div>
                            <i class="fas fa-chevron-right text-indigo-400 mt-1"></i>
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
