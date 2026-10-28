<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Learner;
use App\Models\Message;
use App\Models\Notification;
use App\Models\ParentModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Categorised messaging: one composer that fans a message out to every parent,
 * every tutor, every student, a whole class, or everyone.
 */
class MessageController extends Controller
{
    public function index()
    {
        $batches = Message::with('sender', 'schoolClass')
            ->whereNotNull('batch')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('batch')
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'batch' => $first->batch,
                    'audience_label' => $first->audienceLabel() ?? ucfirst((string) $first->audience),
                    'body' => $first->body,
                    'recipients' => $rows->count(),
                    'read' => $rows->where('is_read', true)->count(),
                    'sent_at' => $first->created_at,
                    'sender' => $first->sender->name ?? 'Administrator',
                ];
            })
            ->values();

        return view('admin.messages.index', [
            'batches' => $batches,
            'unreadTotal' => Message::whereNotNull('batch')->where('is_read', false)->count(),
        ]);
    }

    public function create()
    {
        return view('admin.messages.create', [
            'audiences' => Message::AUDIENCES,
            'classes' => Classes::with('tutor.user')->orderBy('name')->get(),
            'counts' => [
                Message::AUDIENCE_PARENTS => $this->recipients(Message::AUDIENCE_PARENTS)->count(),
                Message::AUDIENCE_TUTORS => $this->recipients(Message::AUDIENCE_TUTORS)->count(),
                Message::AUDIENCE_LEARNERS => $this->recipients(Message::AUDIENCE_LEARNERS)->count(),
                Message::AUDIENCE_EVERYONE => $this->recipients(Message::AUDIENCE_EVERYONE)->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'audience' => 'required|in:parents,tutors,learners,everyone,class',
            'class_id' => 'required_if:audience,class|nullable|exists:classes,id',
            'body' => 'required|string|max:2000',
        ]);

        $audience = $validated['audience'];
        $classId = $audience === Message::AUDIENCE_CLASS ? (int) $validated['class_id'] : null;
        $recipients = $this->recipients($audience, $classId);

        if ($recipients->isEmpty()) {
            return back()->withInput()->with('error', 'There are no active recipients in that category yet.');
        }

        $batch = (string) Str::uuid();

        foreach ($recipients as $recipient) {
            Message::create([
                'sender_id' => auth()->id(),
                'receiver_id' => $recipient->id,
                'body' => $validated['body'],
                'audience' => $audience,
                'class_id' => $classId,
                'batch' => $batch,
            ]);

            Notification::create([
                'user_id' => $recipient->id,
                'type' => Notification::TYPE_MESSAGE,
                'title' => 'New message from the school',
                'message' => Str::limit($validated['body'], 140),
                'channel' => 'in_app',
                'is_read' => false,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.messages.index')
            ->with('success', 'Message sent to ' . $recipients->count() . ' recipient(s).');
    }

    /**
     * Resolve the audience to the actual user accounts that receive a copy.
     */
    private function recipients(string $audience, ?int $classId = null): Collection
    {
        if ($audience === Message::AUDIENCE_CLASS) {
            $class = Classes::find($classId);

            if (! $class) {
                return collect();
            }

            $learnerIds = $class->learners()->pluck('learners.id');
            $learnerUserIds = $class->learners()->pluck('learners.user_id');
            $parentIds = Learner::whereIn('id', $learnerIds)->whereNotNull('parent_id')->pluck('parent_id');
            $parentUserIds = ParentModel::whereIn('id', $parentIds)->pluck('user_id');

            return User::where('is_active', true)
                ->whereIn('id', $learnerUserIds->merge($parentUserIds)->push($class->tutor->user_id)->filter()->unique())
                ->get();
        }

        $slugs = [
            Message::AUDIENCE_PARENTS => ['parent'],
            Message::AUDIENCE_TUTORS => ['tutor'],
            Message::AUDIENCE_LEARNERS => ['learner'],
        ][$audience] ?? ['parent', 'tutor', 'learner'];

        return User::where('is_active', true)
            ->whereHas('role', function ($query) use ($slugs) {
                $query->whereIn('slug', $slugs);
            })
            ->get();
    }
}
