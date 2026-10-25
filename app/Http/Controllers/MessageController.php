<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Inbox: parents see the CEO/administrators, administrators see parents.
     */
    public function index()
    {
        $this->ensureMessagingRole();

        $contacts = $this->contactQuery()->orderBy('name')->get();

        $latestMessages = Message::where('sender_id', auth()->id())
            ->orWhere('receiver_id', auth()->id())
            ->latest()
            ->get()
            ->groupBy(function ($message) {
                return $message->sender_id === auth()->id()
                    ? $message->receiver_id
                    : $message->sender_id;
            });

        $unreadCounts = Message::where('receiver_id', auth()->id())
            ->where('is_read', false)
            ->selectRaw('sender_id, count(*) as total')
            ->groupBy('sender_id')
            ->pluck('total', 'sender_id');

        return view('messages.index', compact('contacts', 'latestMessages', 'unreadCounts'));
    }

    /**
     * Show a single conversation and mark incoming messages as read.
     */
    public function show(User $user)
    {
        $this->ensureConversationAllowed($user);

        Message::where('sender_id', $user->id)
            ->where('receiver_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        $messages = Message::between(auth()->id(), $user->id)
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        return view('messages.show', compact('user', 'messages'));
    }

    /**
     * Send a message and notify the recipient in their notification centre.
     */
    public function store(Request $request, User $user)
    {
        $this->ensureConversationAllowed($user);

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $user->id,
            'body' => $validated['body'],
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'message',
            'title' => 'New message from ' . auth()->user()->name,
            'message' => Str::limit($validated['body'], 140),
            'channel' => 'in_app',
            'is_read' => false,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return redirect()->route('messages.show', $user)->with('success', 'Message sent.');
    }

    private function contactQuery()
    {
        if (auth()->user()->isParent()) {
            return User::where('is_active', true)
                ->whereHas('role', function ($query) {
                    $query->whereIn('slug', ['admin', 'ceo']);
                });
        }

        return User::where('is_active', true)
            ->whereHas('role', function ($query) {
                $query->where('slug', 'parent');
            });
    }

    private function ensureMessagingRole()
    {
        abort_unless(
            auth()->user()->isParent() || auth()->user()->isAdministrator(),
            403,
            'Messaging is available to parents, administrators and the CEO only.'
        );
    }

    private function ensureConversationAllowed(User $contact)
    {
        $this->ensureMessagingRole();

        $allowed = auth()->user()->isParent()
            ? $contact->isAdministrator()
            : $contact->isParent();

        abort_unless($allowed && $contact->is_active, 403, 'You are not allowed to message this user.');
    }
}
