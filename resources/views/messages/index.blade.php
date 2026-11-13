@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-wrap justify-between items-start gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-comments text-blue-700 mr-2"></i>Messages
            </h1>
            <p class="text-gray-600 mt-1">
                @if(auth()->user()->isAdministrator())
                    Private conversations with parents, tutors and students — or send one message to a whole category.
                @elseif(auth()->user()->isTutor())
                    Message the school or the learners in your classes.
                @elseif(auth()->user()->isLearner())
                    Message the school or your tutors.
                @else
                    Send a private message to the school's CEO or an administrator.
                @endif
            </p>
        </div>
        @if(auth()->user()->isAdministrator())
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.messages.index') }}" class="bg-white border border-blue-600 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-50 transition">
                    <i class="fas fa-bullhorn mr-1"></i> Broadcasts
                </a>
                <a href="{{ route('admin.messages.create') }}" class="bg-blue-700 text-white px-5 py-2 rounded-lg hover:bg-blue-800 transition">
                    <i class="fas fa-paper-plane mr-1"></i> Message a category
                </a>
            </div>
        @endif
    </div>

    @if($contacts->isNotEmpty())
    <div class="mb-5 flex items-start gap-3 rounded-xl border-l-4 border-blue-600 bg-blue-50 p-4">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white">
            <i class="fas fa-paper-plane text-sm"></i>
        </span>
        <div>
            <p class="text-sm font-semibold text-blue-900">Want to send a message? Start by clicking a name below.</p>
            <p class="text-sm text-blue-800 mt-0.5">
                The conversation opens on the next screen &mdash; type your message there and press <strong>Send</strong>.
                Replies appear here with a <strong>&ldquo;new&rdquo;</strong> badge next to the person&rsquo;s name.
            </p>
        </div>
    </div>
    @endif

    @if(auth()->user()->isAdministrator())
        <div class="flex flex-wrap gap-2 mb-4">
            <a href="{{ route('messages.index') }}" class="px-4 py-2 rounded-lg text-sm transition {{ $category === null ? 'bg-blue-700 text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-blue-50' }}">
                Everyone
            </a>
            @foreach($categories as $key => $label)
                <a href="{{ route('messages.index', ['category' => $key]) }}" class="px-4 py-2 rounded-lg text-sm transition {{ $category === $key ? 'bg-blue-700 text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-blue-50' }}">
                    {{ $label }}
                </a>
            @endforeach
            @if($broadcastCount > 0)
                <span class="px-4 py-2 rounded-lg text-sm bg-orange-100 text-orange-800">
                    {{ $broadcastCount }} broadcast(s) sent
                </span>
            @endif
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                @if(auth()->user()->isAdministrator())
                    {{ $categories[$category] ?? 'Contacts' }}
                @elseif(auth()->user()->isParent())
                    School Administration
                @elseif(auth()->user()->isTutor())
                    School &amp; my learners
                @else
                    School &amp; my tutors
                @endif
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                @if(auth()->user()->isAdministrator())
                    Click a person below to open their private conversation.
                @else
                    Click a contact below to open the conversation and send a message.
                @endif
            </p>
        </div>

        <ul id="messages-contacts"
            data-updates-url="{{ route('messages.updates.inbox', array_filter(['category' => $category])) }}"
            data-since="{{ $latestConversationId }}"
            data-unread="{{ $unreadTotal }}"
            class="divide-y divide-gray-200">
            @include('messages._contacts', [
                'contacts' => $contacts,
                'latestMessages' => $latestMessages,
                'unreadCounts' => $unreadCounts,
            ])
        </ul>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Keep the inbox fresh: poll a tiny JSON endpoint and swap the contact list only
// when a new message (or a change in the unread total) actually arrived.
(function () {
    var list = document.getElementById('messages-contacts');
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

    setInterval(poll, 5000);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') poll();
    });
})();
</script>
@endpush
