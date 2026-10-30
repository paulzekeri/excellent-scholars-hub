<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Testimonial;
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
     * Inbox. Everyone can now read their own messages (administrators can also
     * broadcast by category), and administrators can filter contacts by role.
     */
    public function index(Request $request)
    {
        $this->ensureMessagingRole();

        $me = auth()->user();
        $category = $me->isAdministrator() ? $request->query('category') : null;

        if (! in_array($category, ['parents', 'tutors', 'learners'], true)) {
            $category = null;
        }

        $contacts = $this->contactQuery($category)->orderBy('name')->get();

        $latestMessages = Message::where('sender_id', $me->id)
            ->orWhere('receiver_id', $me->id)
            ->latest()
            ->get()
            ->groupBy(function ($message) use ($me) {
                return $message->sender_id === $me->id
                    ? $message->receiver_id
                    : $message->sender_id;
            });

        $unreadCounts = Message::where('receiver_id', $me->id)
            ->where('is_read', false)
            ->selectRaw('sender_id, count(*) as total')
            ->groupBy('sender_id')
            ->pluck('total', 'sender_id');

        return view('messages.index', [
            'contacts' => $contacts,
            'latestMessages' => $latestMessages,
            'unreadCounts' => $unreadCounts,
            'category' => $category,
            'categories' => [
                'parents' => 'Parents / Guardians',
                'tutors' => 'Tutors',
                'learners' => 'Students',
            ],
            'broadcastCount' => $me->isAdministrator()
                ? Message::where('sender_id', $me->id)->whereNotNull('batch')->distinct('batch')->count('batch')
                : 0,
        ]);
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
     *
     * A member may also mark the message as a testimonial: the same text is
     * filed as a testimonial for the school to approve (and polish) before it
     * can appear on the landing page.
     */
    public function store(Request $request, User $user)
    {
        $this->ensureConversationAllowed($user);

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
            'type' => 'nullable|in:general,testimonial',
        ]);

        $type = $validated['type'] ?? Message::TYPE_GENERAL;

        // Only members write testimonials about the school, never the school itself.
        $isTestimonial = $type === Message::TYPE_TESTIMONIAL
            && ! auth()->user()->isAdministrator();

        Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $user->id,
            'body' => $validated['body'],
            'type' => $isTestimonial ? Message::TYPE_TESTIMONIAL : Message::TYPE_GENERAL,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => Notification::TYPE_MESSAGE,
            'title' => 'New message from ' . auth()->user()->name,
            'message' => Str::limit($validated['body'], 140),
            'channel' => 'in_app',
            'is_read' => false,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        if ($isTestimonial) {
            $this->fileTestimonial($validated['body']);
        }

        return redirect()->route('messages.show', $user)->with(
            'success',
            $isTestimonial
                ? 'Message sent. Your testimonial is with the school for review — they may tidy the wording before publishing it.'
                : 'Message sent.'
        );
    }

    /**
     * Keep one testimonial per member: sending a new one replaces the text and
     * puts it back in the review queue.
     */
    private function fileTestimonial(string $body): void
    {
        $me = auth()->user();

        Testimonial::updateOrCreate(
            ['user_id' => $me->id],
            [
                'body' => $body,
                'status' => Testimonial::STATUS_PENDING,
                'approved_by' => null,
                'approved_at' => null,
                'review_note' => null,
                'publish_photo' => (bool) $me->photo_path,
            ]
        );

        ActivityLog::create([
            'user_id' => $me->id,
            'action' => 'testimonial.submitted',
            'description' => $me->name . ' sent a testimonial through messages.',
            'model_type' => Testimonial::class,
            'model_id' => optional(Testimonial::where('user_id', $me->id)->first())->id,
        ]);

        // Every reviewer (administrators and the CEO) gets a nudge.
        $reviewers = User::whereHas('role', function ($query) {
            $query->whereIn('slug', ['admin', 'ceo']);
        })->where('is_active', true)->get();

        foreach ($reviewers as $reviewer) {
            if ($reviewer->id === $me->id) {
                continue;
            }

            Notification::create([
                'user_id' => $reviewer->id,
                'type' => Notification::TYPE_REMINDER,
                'title' => 'New testimonial to review',
                'message' => Str::limit($me->name . ' (' . strtolower(optional($me->role)->slug ?? 'member') . '): ' . $body, 240),
                'channel' => 'in_app',
                'is_read' => false,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }
    }

    private function contactQuery(?string $category = null)
    {
        $me = auth()->user();
        $query = User::where('is_active', true)->where('id', '!=', $me->id);

        if ($me->isAdministrator()) {
            $map = [
                'parents' => ['parent'],
                'tutors' => ['tutor'],
                'learners' => ['learner'],
            ];
            $slugs = $map[$category] ?? ['parent', 'tutor', 'learner'];

            return $query->whereHas('role', function ($roleQuery) use ($slugs) {
                $roleQuery->whereIn('slug', $slugs);
            });
        }

        if ($me->isLearner() && $me->learner) {
            $classIds = $me->learner->classes()->pluck('classes.id');

            return $query->where(function ($inner) use ($classIds) {
                $inner->whereHas('role', function ($roleQuery) {
                    $roleQuery->whereIn('slug', ['admin', 'ceo']);
                })->orWhereHas('tutor.classes', function ($classQuery) use ($classIds) {
                    $classQuery->whereIn('classes.id', $classIds);
                });
            });
        }

        if ($me->isTutor() && $me->tutor) {
            $tutorId = $me->tutor->id;

            return $query->where(function ($inner) use ($tutorId) {
                $inner->whereHas('role', function ($roleQuery) {
                    $roleQuery->whereIn('slug', ['admin', 'ceo']);
                })->orWhereHas('learner.classes', function ($classQuery) use ($tutorId) {
                    $classQuery->where('classes.tutor_id', $tutorId);
                });
            });
        }

        // Parents and anyone else talk to the school.
        return $query->whereHas('role', function ($roleQuery) {
            $roleQuery->whereIn('slug', ['admin', 'ceo']);
        });
    }

    private function ensureMessagingRole()
    {
        abort_unless(
            auth()->user()->isParent()
                || auth()->user()->isLearner()
                || auth()->user()->isTutor()
                || auth()->user()->isAdministrator(),
            403,
            'Messaging is not available for this account.'
        );
    }

    private function ensureConversationAllowed(User $contact)
    {
        $this->ensureMessagingRole();

        abort_unless($this->canMessage(auth()->user(), $contact), 403, 'You are not allowed to message this user.');
    }

    /**
     * Who may talk to whom:
     * - administrators / CEO: parents, tutors, learners and each other;
     * - parents, learners, tutors: the school;
     * - learners and their own tutors; tutors and the learners in their classes.
     */
    private function canMessage(User $me, User $contact): bool
    {
        if ($me->id === $contact->id || ! $contact->is_active) {
            return false;
        }

        if ($me->isAdministrator()) {
            return $contact->isParent() || $contact->isTutor() || $contact->isLearner() || $contact->isAdministrator();
        }

        if ($contact->isAdministrator()) {
            return true;
        }

        if ($me->isLearner() && $me->learner && $contact->tutor) {
            return $me->learner->classes()
                ->where('classes.tutor_id', $contact->tutor->id)
                ->exists();
        }

        if ($me->isTutor() && $me->tutor && $contact->learner) {
            return $contact->learner->classes()
                ->where('classes.tutor_id', $me->tutor->id)
                ->exists();
        }

        return false;
    }
}
