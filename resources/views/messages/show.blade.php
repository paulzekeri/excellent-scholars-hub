@extends('layouts.app')

@section('title', 'Chat with ' . $user->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-4">
        <a href="{{ route('messages.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Messages
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-indigo-600 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-white">{{ $user->name }}</h1>
                <p class="text-indigo-200 text-xs">
                    {{ ucfirst($user->role->name) }}@if($user->email) &middot; {{ $user->email }}@endif
                </p>
            </div>
            <i class="fas fa-user-circle text-3xl text-indigo-200"></i>
        </div>

        <div class="p-6 space-y-4 max-h-[28rem] overflow-y-auto bg-gray-50">
            @forelse($messages as $message)
                <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-lg rounded-2xl px-4 py-3 text-sm shadow-sm
                        {{ $message->sender_id === auth()->id() ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-200' }}">
                        <p class="whitespace-pre-line">{{ $message->body }}</p>
                        <p class="text-xs mt-1 {{ $message->sender_id === auth()->id() ? 'text-indigo-200' : 'text-gray-400' }}">
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
        </div>

        <form action="{{ route('messages.store', $user) }}" method="POST" class="border-t border-gray-200 p-4 bg-white">
            @csrf
            @error('body')
                <p class="text-sm text-red-600 mb-2">{{ $message }}</p>
            @enderror
            <div class="flex items-end gap-3">
                <textarea name="body" rows="2" required maxlength="2000" placeholder="Type your message..."
                    class="flex-1 border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('body') }}</textarea>
                <button type="submit" class="bg-indigo-600 text-white px-5 py-3 rounded-lg hover:bg-indigo-700 transition text-sm">
                    <i class="fas fa-paper-plane mr-1"></i> Send
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-2">Messages are private between you and {{ $user->name }}. Maximum 2,000 characters.</p>
        </form>
    </div>
</div>
@endsection
