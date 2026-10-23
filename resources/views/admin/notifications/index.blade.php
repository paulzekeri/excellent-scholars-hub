@extends('layouts.app')
@section('title', 'Manage Notifications')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div><h1 class="text-3xl font-bold text-gray-800">Notifications</h1><p class="text-gray-600 mt-1">Send and manage platform notifications</p></div>
        <a href="{{ route('admin.notifications.create') }}" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition"><i class="fas fa-bell mr-1"></i> Send Notification</a>
    </div>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recipient</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($notifications as $notification)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium">{{ $notification->title }}</td>
                        <td class="px-6 py-4 text-sm">{{ ucfirst($notification->type) }}</td>
                        <td class="px-6 py-4 text-sm">{{ $notification->user_id ? ($notification->user->name ?? 'Deleted User') : 'All Users' }}</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full @if($notification->is_read) bg-green-100 text-green-800 @else bg-yellow-100 text-yellow-800 @endif">{{ $notification->is_read ? 'Read' : 'Unread' }}</span></td>
                        <td class="px-6 py-4 text-sm">{{ $notification->created_at->format('M d, Y h:i A') }}</td>
                        <td class="px-6 py-4 text-sm">
                            <form action="{{ route('admin.notifications.destroy', $notification) }}" method="POST" class="inline" onsubmit="return confirm('Delete this notification?')">@csrf @method('DELETE')<button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button></form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $notifications->links() }}</div>
    </div>
</div>
@endsection
