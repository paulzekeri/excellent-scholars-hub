{{--
    The contact list rows. Shared by the inbox page and its auto-refresh endpoint
    (messages/updates/inbox), so the polled markup and the first render are the
    same Blade file.
--}}
@forelse($contacts as $contact)
    @php
        $contactMessages = $latestMessages->get($contact->id);
        $lastMessage = $contactMessages ? $contactMessages->first() : null;
        $unread = $unreadCounts->get($contact->id, 0);
    @endphp
    <li>
        <a href="{{ route('messages.show', $contact) }}"
           class="group flex items-center gap-3 px-4 sm:px-6 py-4 hover:bg-blue-50 transition"
           aria-label="{{ $lastMessage ? 'Open chat with' : 'Send a message to' }} {{ $contact->name }}">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-800">
                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($contact->name, 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-medium text-gray-800 flex items-center flex-wrap gap-2">
                    {{ $contact->name }}
                    <span class="px-2 py-0.5 text-xs rounded-full
                        @if($contact->isCeo()) bg-sun-light text-sun-ink
                        @elseif($contact->isAdmin()) bg-orange-100 text-orange-900
                        @elseif($contact->isTutor()) bg-blue-100 text-blue-800
                        @elseif($contact->isLearner()) bg-green-100 text-green-800
                        @else bg-orange-100 text-orange-800 @endif">
                        {{ ucfirst($contact->role->name) }}
                    </span>
                    @if($unread > 0)
                        <span class="bg-sun text-[#0a2550] text-xs rounded-full px-2 py-0.5">{{ $unread }} new</span>
                    @endif
                </p>
                <p class="text-sm text-gray-500 truncate">
                    {{ $lastMessage ? \Illuminate\Support\Str::limit($lastMessage->body, 80) : 'No messages yet — click below to send the first one.' }}
                </p>
            </div>
            <div class="flex shrink-0 flex-col items-end gap-1">
                @if($lastMessage)
                    <span class="text-xs text-gray-500 whitespace-nowrap">{{ $lastMessage->created_at->diffForHumans() }}</span>
                @endif
                {{-- The visible action: makes it obvious that clicking the name opens a chat. --}}
                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg bg-blue-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition group-hover:bg-blue-800">
                    <i class="fas fa-paper-plane text-[11px]"></i>
                    {{ $lastMessage ? 'Open chat' : 'Send message' }}
                    <i class="fas fa-chevron-right text-[10px]"></i>
                </span>
            </div>
        </a>
    </li>
@empty
    <li class="px-6 py-10 text-center text-gray-500">
        <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
        <p>No contacts available yet.</p>
        @if(auth()->user()->isAdministrator())
            <p class="text-sm text-gray-500 mt-1">Try another category, or send a categorised message from the button above.</p>
        @else
            <p class="text-sm text-gray-500 mt-1">Your school contact will appear here once your account is linked — ask the school office if you expected someone to be listed.</p>
        @endif
    </li>
@endforelse
