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

    @include('partials.phone-alerts-button')

    @if($notifications->onFirstPage())
    <div id="notifications-list"
         data-updates-url="{{ route('notifications.updates', array_filter(['type' => $type])) }}"
         data-since="{{ $latestId }}"
         data-unread="{{ $unreadTotal }}"
         class="space-y-4">
    @else
    <div class="space-y-4">
    @endif
        @include('notifications._list', ['notifications' => $notifications, 'type' => $type])
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
    @if($notifications->count() > 0)
    <form action="{{ route('notifications.readAll') }}" method="POST" class="mt-4">@csrf
        <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm"><i class="fas fa-check-double mr-1"></i> Mark all as read</button>
    </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Poll for new notifications (and read-state changes) and redraw the list only
// when something actually changed.
(function () {
    var list = document.getElementById('notifications-list');
    if (!list) return;

    var url = list.dataset.updatesUrl;
    var since = parseInt(list.dataset.since || '0', 10);
    var unread = parseInt(list.dataset.unread || '0', 10);
    var busy = false;

    function poll() {
        if (busy || document.visibilityState !== 'visible') return;
        busy = true;

        var separator = url.indexOf('?') === -1 ? '?' : '&';

        fetch(url + separator + 'since=' + since + '&unread=' + unread, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (data) {
            if (!data) return;
            if (typeof data.last_id === 'number') since = data.last_id;
            if (typeof data.unread_total === 'number') unread = data.unread_total;
            if (data.changed && data.html) list.innerHTML = data.html;
        })
        .catch(function () { /* offline — the next tick tries again */ })
        .finally(function () { busy = false; });
    }

    setInterval(poll, 8000);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') poll();
    });
})();
</script>
@endpush
