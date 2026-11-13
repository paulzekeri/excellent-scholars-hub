@extends('layouts.app')

@section('title', 'Chat with ' . $user->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-4">
        <a href="{{ route('messages.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Messages
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-blue-600 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-white">{{ $user->name }}</h1>
                <p class="text-blue-200 text-xs">
                    {{ ucfirst($user->role->name) }}@if($user->email) &middot; {{ $user->email }}@endif
                </p>
            </div>
            <i class="fas fa-user-circle text-3xl text-blue-200"></i>
        </div>

        <div id="messages-thread"
             data-updates-url="{{ route('messages.updates.thread', $user) }}"
             data-last="{{ $messages->last()->id ?? 0 }}"
             class="p-6 space-y-4 max-h-[28rem] overflow-y-auto bg-gray-50">
            @include('messages._thread', ['messages' => $messages])
        </div>

        <form action="{{ route('messages.store', $user) }}" method="POST" class="border-t border-gray-200 p-4 bg-white">
            @csrf
            @error('body')
                <p class="text-sm text-orange-700 mb-2">{{ $message }}</p>
            @enderror
            @error('type')
                <p class="text-sm text-orange-700 mb-2">{{ $message }}</p>
            @enderror

            @unless(auth()->user()->isAdministrator())
                <div class="mb-3">
                    <label for="type" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Message type</label>
                    <select id="type" name="type"
                        class="mt-1 w-full sm:w-80 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="general" {{ old('type', 'general') === 'general' ? 'selected' : '' }}>General message</option>
                        <option value="testimonial" {{ old('type') === 'testimonial' ? 'selected' : '' }}>Testimonial — may be published on the website</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Choose <strong>Testimonial</strong> to share a sentence or two about the school. It goes to the school for review;
                        they may tidy the wording, and it only appears on the public site once approved.
                    </p>
                </div>
            @endunless

            <div class="flex items-end gap-3">
                <textarea name="body" rows="2" required maxlength="2000" placeholder="Type your message..."
                    class="flex-1 border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500">{{ old('body') }}</textarea>
                <button type="submit" class="bg-blue-600 text-white px-5 py-3 rounded-lg hover:bg-blue-700 transition text-sm">
                    <i class="fas fa-paper-plane mr-1"></i> Send
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-2">Messages are private between you and {{ $user->name }}. Maximum 2,000 characters.</p>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Keep an open conversation fresh: poll for messages after the newest id we have
// and swap the bubbles in place. The composer is outside the swapped container,
// so a half-typed reply is never lost.
(function () {
    var thread = document.getElementById('messages-thread');
    if (!thread) return;

    var url = thread.dataset.updatesUrl;
    var lastId = parseInt(thread.dataset.last || '0', 10);
    var busy = false;

    function nearBottom() {
        return thread.scrollHeight - thread.scrollTop - thread.clientHeight < 80;
    }

    function scrollToBottom() {
        thread.scrollTop = thread.scrollHeight;
    }

    function poll() {
        if (busy || document.visibilityState !== 'visible') return;
        busy = true;

        fetch(url + '?last=' + lastId, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (data) {
            if (!data || !data.changed || !data.html) {
                if (data && typeof data.last_id === 'number') lastId = data.last_id;
                return;
            }

            var stayAtBottom = nearBottom();
            thread.innerHTML = data.html;
            lastId = data.last_id;

            // Only follow the conversation if the reader was already at the end.
            if (stayAtBottom) scrollToBottom();
        })
        .catch(function () { /* offline — the next tick tries again */ })
        .finally(function () { busy = false; });
    }

    // Open at the newest message, like any chat window.
    scrollToBottom();
    setInterval(poll, 4000);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') poll();
    });
})();
</script>
@endpush
