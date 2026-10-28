@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Notifications</h1>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('notifications.index') }}"
               class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium border {{ $type === null ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                <i class="fas fa-layer-group mr-1"></i>All
            </a>
            @foreach(\App\Models\Notification::COMPOSABLE_TYPES as $filterType)
                @php $filterMeta = \App\Models\Notification::metaFor($filterType); @endphp
                <a href="{{ route('notifications.index', ['type' => $filterType]) }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium border {{ $type === $filterType ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    <i class="fas {{ $filterMeta['icon'] }} mr-1"></i>{{ $filterMeta['label'] }}
                </a>
            @endforeach
        </div>
    </div>
    <div class="space-y-4">
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
                    <p class="text-xs text-gray-400 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                @if(!$notification->is_read)
                <form action="{{ route('notifications.read', $notification) }}" method="POST">@csrf
                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 whitespace-nowrap"><i class="fas fa-check"></i> Mark read</button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-12 text-gray-500"><i class="fas fa-bell text-4xl mb-4 text-gray-300"></i><p>{{ $type === null ? 'No notifications yet.' : 'No notifications of this type.' }}</p></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
    @if($notifications->count() > 0)
    <form action="{{ route('notifications.readAll') }}" method="POST" class="mt-4">@csrf
        <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm"><i class="fas fa-check-double mr-1"></i> Mark all as read</button>
    </form>
    @endif
</div>
@endsection
