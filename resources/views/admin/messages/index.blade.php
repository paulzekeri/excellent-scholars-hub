@extends('layouts.app')
@section('title', 'Message Broadcasts')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Categorised Messages</h1>
            <p class="text-gray-600 mt-1">Send to parents, tutors, students, a single class or everyone</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('messages.index') }}" class="bg-white border border-blue-600 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-50 transition"><i class="fas fa-inbox mr-1"></i> My inbox</a>
            <a href="{{ route('admin.messages.create') }}" class="bg-blue-700 text-white px-6 py-2 rounded-lg hover:bg-blue-800 transition"><i class="fas fa-paper-plane mr-1"></i> New message</a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-600">
            <p class="text-gray-500 text-sm">Broadcasts sent</p>
            <p id="broadcast-count" class="text-2xl font-bold text-gray-800">{{ $batches->count() }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-orange-500">
            <p class="text-gray-500 text-sm">Delivered copies</p>
            <p id="broadcast-delivered" class="text-2xl font-bold text-gray-800">{{ $batches->sum('recipients') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-500">
            <p class="text-gray-500 text-sm">Unread copies</p>
            <p id="broadcast-unread" class="text-2xl font-bold text-gray-800">{{ $unreadTotal }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Sent</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Audience</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Recipients</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Read</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Sender</th>
                    </tr>
                </thead>
                <tbody id="broadcast-rows"
                       data-updates-url="{{ route('admin.messages.updates') }}"
                       data-since="{{ $latestId }}"
                       data-unread="{{ $unreadTotal }}"
                       class="divide-y divide-gray-200">
                    @include('admin.messages._batches', ['batches' => $batches])
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Poll the broadcast report so a message sent from another tab or another
// administrator appears here (with the counters) without a reload.
(function () {
    var rows = document.getElementById('broadcast-rows');
    if (!rows) return;

    var url = rows.dataset.updatesUrl;
    var since = parseInt(rows.dataset.since || '0', 10);
    var unread = parseInt(rows.dataset.unread || '0', 10);
    var busy = false;

    function poll() {
        if (busy || document.visibilityState !== 'visible') return;
        busy = true;

        fetch(url + '?since=' + since + '&unread=' + unread, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (data) {
            if (!data) return;
            if (typeof data.last_id === 'number' && data.last_id > since) since = data.last_id;
            if (typeof data.unread_total === 'number') unread = data.unread_total;
            if (!data.changed || !data.html) return;

            rows.innerHTML = data.html;

            if (data.stats) {
                var count = document.getElementById('broadcast-count');
                var delivered = document.getElementById('broadcast-delivered');
                var unreadCopies = document.getElementById('broadcast-unread');
                if (count) count.textContent = data.stats.broadcasts;
                if (delivered) delivered.textContent = data.stats.delivered;
                if (unreadCopies) unreadCopies.textContent = data.stats.unread;
            }
        })
        .catch(function () { /* offline — the next tick tries again */ })
        .finally(function () { busy = false; });
    }

    setInterval(poll, 6000);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') poll();
    });
})();
</script>
@endpush
