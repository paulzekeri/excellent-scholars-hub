@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Notifications</h1>
    <div class="space-y-4">
        @forelse($notifications as $notification)
        <div class="bg-white rounded-xl shadow-md p-6 @if(!$notification->is_read) border-l-4 border-indigo-500 @endif">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-semibold text-gray-800">{{ $notification->title }}</h3>
                    <p class="text-gray-600 mt-1">{{ $notification->message }}</p>
                    <p class="text-xs text-gray-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                @if(!$notification->is_read)
                <form action="{{ route('notifications.read', $notification) }}" method="POST">@csrf
                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800"><i class="fas fa-check"></i> Mark read</button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-12 text-gray-500"><i class="fas fa-bell text-4xl mb-4 text-gray-300"></i><p>No notifications yet.</p></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
    @if($notifications->count() > 0)
    <form action="{{ route('notifications.readAll') }}" method="POST" class="mt-4">@csrf
        <button type="submit" class="text-indigo-600 hover:text-indigo-800 text-sm"><i class="fas fa-check-double mr-1"></i> Mark all as read</button>
    </form>
    @endif
</div>
@endsection
