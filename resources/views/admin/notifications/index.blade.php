@extends('layouts.app')
@section('title', 'Manage Notifications')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div><h1 class="text-3xl font-bold text-gray-800">Notifications</h1><p class="text-gray-600 mt-1">Send and manage platform notifications</p></div>
        <a href="{{ route('admin.notifications.create') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"><i class="fas fa-bell mr-1"></i> Send Notification</a>
    </div>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-[#EAF2FB]">
                    <tr><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Title</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Type</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Recipient</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Date</th><th class="px-6 py-3 text-left text-xs font-semibold text-[#0a2550] uppercase">Actions</th></tr>
                </thead>
                <tbody id="admin-notification-rows"
                       @if($notifications->onFirstPage())
                       data-updates-url="{{ route('admin.notifications.updates') }}"
                       data-since="{{ $latestId }}"
                       @endif
                       class="divide-y divide-gray-200">
                    @include('admin.notifications._rows', ['notifications' => $notifications])
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t">{{ $notifications->links() }}</div>
    </div>
</div>

<!-- Read notification modal -->
<div id="notification-modal" class="fixed inset-0 z-50 hidden overflow-y-auto px-4 py-10" role="dialog" aria-modal="true" aria-labelledby="notification-modal-title">
    <div class="absolute inset-0 bg-black bg-opacity-50" data-modal-close></div>
    <div class="relative max-w-lg mx-auto bg-white rounded-xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 bg-blue-600 flex items-start justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-blue-200">Notification</p>
                <h2 id="notification-modal-title" class="text-lg font-semibold text-white"></h2>
            </div>
            <button type="button" data-modal-close class="text-blue-200 hover:text-white" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs uppercase text-gray-500">Type</p>
                    <p id="notification-modal-type" class="text-gray-700"></p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Status</p>
                    <p id="notification-modal-status" class="text-gray-700"></p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Recipient</p>
                    <p id="notification-modal-recipient" class="text-gray-700"></p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Sent</p>
                    <p id="notification-modal-date" class="text-gray-700"></p>
                </div>
            </div>
            <div>
                <p class="text-xs uppercase text-gray-500">Message</p>
                <p id="notification-modal-message" class="text-sm text-gray-700 whitespace-pre-line mt-1"></p>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t flex justify-end">
            <button type="button" data-modal-close class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition text-sm">Close</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('notification-modal');
        if (!modal) return;

        var fields = {
            title: document.getElementById('notification-modal-title'),
            type: document.getElementById('notification-modal-type'),
            status: document.getElementById('notification-modal-status'),
            recipient: document.getElementById('notification-modal-recipient'),
            date: document.getElementById('notification-modal-date'),
            message: document.getElementById('notification-modal-message')
        };

        function openModal(button) {
            fields.title.textContent = button.dataset.title || '';
            fields.type.textContent = button.dataset.type || '';
            fields.status.textContent = button.dataset.status || '';
            fields.recipient.textContent = button.dataset.recipient || '';
            fields.date.textContent = button.dataset.date || '';
            fields.message.textContent = button.dataset.message || '';
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Delegated, so rows replaced by the auto-refresh keep working.
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.notification-read-btn');
            if (button) openModal(button);
        });

        modal.querySelectorAll('[data-modal-close]').forEach(function (element) {
            element.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    });
</script>
@endpush

@push('scripts')
<script>
// Poll page one of the notification manager so new sends appear without a
// reload. Pagination is untouched: other pages do not poll at all.
(function () {
    var rows = document.getElementById('admin-notification-rows');
    if (!rows || !rows.dataset.updatesUrl) return;

    var url = rows.dataset.updatesUrl;
    var since = parseInt(rows.dataset.since || '0', 10);
    var busy = false;

    function poll() {
        if (busy || document.visibilityState !== 'visible') return;
        busy = true;

        fetch(url + '?since=' + since, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (data) {
            if (!data) return;
            if (typeof data.last_id === 'number') since = data.last_id;
            if (data.changed && data.html) rows.innerHTML = data.html;
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
