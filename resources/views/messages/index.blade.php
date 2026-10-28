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
                    Choose a person to open the private conversation.
                @else
                    Choose a contact to open the conversation.
                @endif
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
                    <a href="{{ route('messages.show', $contact) }}" class="flex items-center justify-between px-6 py-4 hover:bg-blue-50 transition">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 flex items-center flex-wrap gap-2">
                                {{ $contact->name }}
                                <span class="px-2 py-0.5 text-xs rounded-full
                                    @if($contact->isCeo()) bg-purple-100 text-purple-800
                                    @elseif($contact->isAdmin()) bg-red-100 text-red-800
                                    @elseif($contact->isTutor()) bg-blue-100 text-blue-800
                                    @elseif($contact->isLearner()) bg-green-100 text-green-800
                                    @else bg-orange-100 text-orange-800 @endif">
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
                    @if(auth()->user()->isAdministrator())
                        <p class="text-sm text-gray-400 mt-1">Try another category, or send a categorised message from the button above.</p>
                    @endif
                </li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
