<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // The CEO shares the admin area, so both roles may manage notifications.
        $this->middleware('role:admin,ceo');
    }

    public function index()
    {
        $notifications = Notification::latest()->paginate(20);

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'latestId' => (int) Notification::max('id'),
        ]);
    }

    /**
     * Auto-refresh for the notification manager: new rows appear without a
     * reload. Only page one is polled, so pagination is never disturbed.
     */
    public function updates(Request $request)
    {
        $lastId = (int) Notification::max('id');

        if ($lastId === (int) $request->query('since')) {
            return response()->json(['last_id' => $lastId, 'changed' => false]);
        }

        return response()->json([
            'last_id' => $lastId,
            'changed' => true,
            'html' => view('admin.notifications._rows', [
                'notifications' => Notification::latest()->paginate(20, ['*'], 'page', 1),
            ])->render(),
        ]);
    }

    public function create()
    {
        $users = User::visible()->where('is_active', true)->get();
        return view('admin.notifications.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => ['required', 'string', Rule::in(Notification::COMPOSABLE_TYPES)],
            'user_id' => 'nullable|exists:users,id',
        ]);

        // Notifications are delivered in-app, so record that delivery state
        // instead of leaving the row at the "email / pending" defaults.
        $attributes = [
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => $validated['type'],
            'channel' => 'in_app',
            'is_read' => false,
            'status' => 'sent',
            'sent_at' => now(),
        ];

        if (!empty($validated['user_id'])) {
            Notification::create($attributes + ['user_id' => $validated['user_id']]);
        } else {
            // Send to all active users
            $users = User::visible()->where('is_active', true)->get();
            foreach ($users as $user) {
                Notification::create($attributes + ['user_id' => $user->id]);
            }
        }

        return redirect()->route('admin.notifications.index')->with('success', 'Notification sent successfully.');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();
        return back()->with('success', 'Notification deleted.');
    }
}
