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
        $type = $request->query('type');
        $type = in_array($type, Notification::COMPOSABLE_TYPES, true) ? $type : null;

        $notifications = Notification::where(function ($query) {
                $query->where('user_id', auth()->id())->orWhereNull('user_id');
            })
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', compact('notifications', 'type'));
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
