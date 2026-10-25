<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

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
        return view('admin.notifications.index', compact('notifications'));
    }

    public function create()
    {
        $users = User::where('is_active', true)->get();
        return view('admin.notifications.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        if ($request->user_id) {
            Notification::create([
                'user_id' => $request->user_id,
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'is_read' => false,
            ]);
        } else {
            // Send to all users
            $users = User::where('is_active', true)->get();
            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'title' => $request->title,
                    'message' => $request->message,
                    'type' => $request->type,
                    'is_read' => false,
                ]);
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
