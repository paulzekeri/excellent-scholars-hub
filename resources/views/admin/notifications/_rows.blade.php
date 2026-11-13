{{--
    The notification manager rows. Shared by the page and its auto-refresh
    endpoint (admin/notifications/updates).
--}}
@forelse($notifications as $notification)
    @php $typeMeta = $notification->typeMeta(); @endphp
    <tr class="hover:bg-gray-50">
        <td class="px-6 py-4 text-sm font-medium">{{ $notification->title }}</td>
        <td class="px-6 py-4 text-sm">
            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full {{ $typeMeta['badge'] }}">
                <i class="fas {{ $typeMeta['icon'] }} mr-1"></i>{{ $typeMeta['label'] }}
            </span>
        </td>
        <td class="px-6 py-4 text-sm">{{ $notification->user_id ? ($notification->user->name ?? 'Deleted User') : 'All Users' }}</td>
        <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full @if($notification->is_read) bg-green-100 text-green-800 @else bg-sun-light text-sun-ink @endif">{{ $notification->is_read ? 'Read' : 'Unread' }}</span></td>
        <td class="px-6 py-4 text-sm">{{ $notification->created_at->format('M d, Y h:i A') }}</td>
        <td class="px-6 py-4 text-sm">
            <div class="flex items-center gap-4">
                <button type="button"
                    class="notification-read-btn text-blue-600 hover:text-blue-800"
                    title="Read notification"
                    data-title="{{ $notification->title }}"
                    data-type="{{ $typeMeta['label'] }}"
                    data-recipient="{{ $notification->user_id ? ($notification->user->name ?? 'Deleted User') : 'All Users' }}"
                    data-status="{{ $notification->is_read ? 'Read' : 'Unread' }}"
                    data-date="{{ $notification->created_at->format('M d, Y h:i A') }}"
                    data-message="{{ $notification->message }}">
                    <i class="fas fa-eye mr-1"></i> Read
                </button>
                <form action="{{ route('admin.notifications.destroy', $notification) }}" method="POST" class="inline" onsubmit="return confirm('Delete this notification?')">@csrf @method('DELETE')<button type="submit" class="text-orange-700 hover:text-orange-900"><i class="fas fa-trash"></i></button></form>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
            <i class="fas fa-bell-slash text-3xl text-gray-300 mb-2"></i>
            <p>No notifications sent yet.</p>
        </td>
    </tr>
@endforelse
