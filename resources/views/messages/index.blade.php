@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-comments text-indigo-600 mr-2"></i>Messages
        </h1>
        <p class="text-gray-600 mt-1">
            @if(auth()->user()->isParent())
                Send a private message to the school's CEO or an administrator.
            @else
                Private conversations with parents and guardians.
            @endif
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                {{ auth()->user()->isParent() ? 'School Administration' : 'Parents & Guardians' }}
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ auth()->user()->isParent() ? 'Choose who you would like to message.' : 'Open a conversation to reply to a parent.' }}
            </p>
        </div>

        <ul class="divide-y divide-gray-200">
            @forelse($contacts as $contact)
                @php
                    $contactMessages = $latestMessages->get($contact->id);
                    $lastMessage = $contactMessages ? $contactMessages->first() : null;
                    $unread = $unreadCounts->get($contact->id, 0);
                @endphp
                <li>
                    <a href="{{ route('messages.show', $contact) }}" class="flex items-center justify-between px-6 py-4 hover:bg-indigo-50 transition">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 flex items-center flex-wrap gap-2">
                                {{ $contact->name }}
                                <span class="px-2 py-0.5 text-xs rounded-full
                                    @if($contact->isCeo()) bg-purple-100 text-purple-800
                                    @elseif($contact->isAdmin()) bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ ucfirst($contact->role->name) }}
                                </span>
                                @if($unread > 0)
                                    <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5">{{ $unread }} new</span>
                                @endif
                            </p>
                            <p class="text-sm text-gray-500 truncate">
                                {{ $lastMessage ? \Illuminate\Support\Str::limit($lastMessage->body, 80) : 'No messages yet — start the conversation.' }}
                            </p>
                        </div>
                        <span class="text-xs text-gray-400 whitespace-nowrap ml-4">
                            @if($lastMessage)
                                {{ $lastMessage->created_at->diffForHumans() }}
                            @endif
                        </span>
                    </a>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-gray-500">
                    <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
                    <p>No contacts available yet.</p>
                </li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
