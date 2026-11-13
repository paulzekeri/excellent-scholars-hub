{{--
    The message bubbles of one conversation. Shared by the conversation page and
    its auto-refresh endpoint (messages/{user}/updates).
--}}
@forelse($messages as $message)
    <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
        <div class="max-w-lg rounded-2xl px-4 py-3 text-sm shadow-sm
            {{ $message->sender_id === auth()->id() ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-200' }}">
            <p class="whitespace-pre-line">{{ $message->body }}</p>
            <p class="text-xs mt-1 {{ $message->sender_id === auth()->id() ? 'text-blue-200' : 'text-gray-500' }}">
                {{ $message->created_at->format('M d, Y h:i A') }}
            </p>
        </div>
    </div>
@empty
    <p class="text-center text-gray-500 py-8">
        <i class="fas fa-comment-dots text-3xl text-gray-300 mb-2"></i><br>
        No messages yet. Send the first message below.
    </p>
@endforelse
