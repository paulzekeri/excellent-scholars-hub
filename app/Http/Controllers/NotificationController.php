<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $type = $this->typeFilter($request);
        $notifications = $this->feed($type);

        return view('notifications.index', [
            'notifications' => $notifications,
            'type' => $type,
            'latestId' => $notifications->onFirstPage() ? $this->latestId($type) : 0,
            'unreadTotal' => $this->unreadTotal($type),
        ]);
    }

    /**
     * Auto-refresh for the notification feed: tells the page whether anything new
     * arrived and, when it did, returns the freshly rendered list.
     */
    public function updates(Request $request)
    {
        $type = $this->typeFilter($request);
        $lastId = $this->latestId($type);
        $unreadTotal = $this->unreadTotal($type);

        $changed = $lastId !== (int) $request->query('since')
            || $unreadTotal !== (int) $request->query('unread');

        if (! $changed) {
            return response()->json(['last_id' => $lastId, 'unread_total' => $unreadTotal, 'changed' => false]);
        }

        return response()->json([
            'last_id' => $lastId,
            'unread_total' => $unreadTotal,
            'changed' => true,
            'html' => view('notifications._list', [
                'notifications' => $this->feed($type),
                'type' => $type,
            ])->render(),
        ]);
    }

    /** Only the three composable types are accepted from the query string. */
    private function typeFilter(Request $request): ?string
    {
        $type = $request->query('type');

        return in_array($type, Notification::COMPOSABLE_TYPES, true) ? $type : null;
    }

    /** The notifications this account can see, newest first, 20 per page. */
    private function feed(?string $type)
    {
        return $this->visibleToMe($type)->latest()->paginate(20)->withQueryString();
    }

    /** Newest visible notification id — the poll watermark. */
    private function latestId(?string $type): int
    {
        return (int) $this->visibleToMe($type)->max('id');
    }

    private function unreadTotal(?string $type): int
    {
        return (int) $this->visibleToMe($type)->where('is_read', false)->count();
    }

    /** Personal notifications plus the global ones (user_id is null). */
    private function visibleToMe(?string $type)
    {
        return Notification::where(function ($query) {
            $query->where('user_id', auth()->id())->orWhereNull('user_id');
        })->when($type, function ($query) use ($type) {
            $query->where('type', $type);
        });
    }

    public function markRead(Notification $notification)
    {
        if ($notification->user_id && $notification->user_id !== auth()->id()) {
            abort(403);
        }
        $notification->update(['is_read' => true]);
        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
        return back()->with('success', 'All notifications marked as read.');
    }

    public function unreadCount()
    {
        return Notification::where(function($q) {
            $q->where('user_id', auth()->id())->orWhereNull('user_id');
        })->where('is_read', false)->count();
    }
}
