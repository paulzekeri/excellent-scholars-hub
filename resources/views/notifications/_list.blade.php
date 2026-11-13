{{--
    The notification cards. Shared by the notifications page and its auto-refresh
    endpoint (notifications/updates), so live updates keep identical markup.
--}}
@forelse($notifications as $notification)
    @php $meta = $notification->typeMeta(); @endphp
    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 {{ $notification->is_read ? 'border-transparent' : $meta['accent'] }}">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $meta['badge'] }}">
                        <i class="fas {{ $meta['icon'] }} mr-1"></i>{{ $meta['label'] }}
                    </span>
                    @if(!$notification->is_read)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-600 text-white">New</span>
                    @endif
                </div>
                <h3 class="font-semibold text-gray-800 mt-2">{{ $notification->title }}</h3>
                <p class="text-gray-600 mt-1">{{ $notification->message }}</p>
                <p class="text-xs text-gray-500 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
            </div>
            @if(!$notification->is_read)
                <form action="{{ route('notifications.read', $notification) }}" method="POST">@csrf
                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 whitespace-nowrap"><i class="fas fa-check"></i> Mark read</button>
                </form>
            @endif
        </div>
    </div>
@empty
    <div class="text-center py-12 text-gray-500">
        <i class="fas fa-bell text-4xl mb-4 text-gray-300"></i>
        <p>{{ $type === null ? 'No notifications yet.' : 'No notifications of this type.' }}</p>
    </div>
@endforelse
