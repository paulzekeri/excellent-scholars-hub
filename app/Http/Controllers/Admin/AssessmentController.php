<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AssessmentScheduledMail;
use App\Models\ActivityLog;
use App\Models\AssessmentSession;
use App\Models\Enquiry;
use App\Models\EnquiryNote;
use App\Models\Notification;
use App\Models\Tutor;
use App\Models\Classes;
use App\Services\AssessmentResultService;
use App\Http\Requests\AssessmentResultRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

/**
 * Note 13 phase 2 — the school creates the Meet/Zoom link, assigns the tutor,
 * and the guardian receives the join link by email (and WhatsApp from the
 * enquiry screen). Administrator, CEO and tutor all see the link; the parent
 * joins as a guest.
 */
class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        $scope = (string) $request->query('scope', 'upcoming');
        $tutorId = (string) $request->query('tutor_id');

        $query = AssessmentSession::with(['enquiry', 'tutor.user']);

        if ($scope === 'today') {
            $query->whereDate('scheduled_at', today())->orderBy('scheduled_at');
        } elseif ($scope === 'past') {
            $query->where('scheduled_at', '<', now())->orderByDesc('scheduled_at');
        } elseif ($scope === 'all') {
            $query->orderByDesc('scheduled_at');
        } else {
            $query->where('scheduled_at', '>=', now())->orderBy('scheduled_at');
        }

        if ($tutorId !== '') {
            $query->where('tutor_id', $tutorId);
        }

        return view('admin.assessments.index', [
            'assessments' => $query->paginate(20)->withQueryString(),
            'scope' => $scope,
            'tutorId' => $tutorId,
            'tutors' => Tutor::with('user')->get()
                ->sortBy(function ($tutor) {
                    return strtolower((string) optional($tutor->user)->name);
                })
                ->values(),
            'counts' => [
                'today' => AssessmentSession::whereDate('scheduled_at', today())->count(),
                'upcoming' => AssessmentSession::where('scheduled_at', '>=', now())->count(),
                'past' => AssessmentSession::where('scheduled_at', '<', now())->count(),
                'all' => AssessmentSession::count(),
            ],
        ]);
    }

    public function store(Request $request, Enquiry $enquiry)
    {
        if ($enquiry->status !== Enquiry::STATUS_CONTACTED) {
            return back()->with('error', 'Mark the enquiry as Contacted before booking the assessment.');
        }

        $validated = $request->validate([
            'tutor_id' => ['required', 'exists:tutors,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:120'],
            'platform' => ['required', Rule::in(array_keys(AssessmentSession::PLATFORMS))],
            'meeting_link' => ['nullable', 'url', 'max:255', 'required_unless:platform,phone,in_person'],
            'guardian_email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'meeting_link.required_unless' => 'Paste the Google Meet or Zoom link — the guardian joins from it.',
            'guardian_email.required' => 'The guardian needs an email address to receive the call link.',
        ]);

        $tutor = Tutor::with('user')->findOrFail($validated['tutor_id']);

        $assessment = $enquiry->assessments()->create([
            'tutor_id' => $tutor->id,
            'scheduled_at' => $validated['scheduled_at'],
            'duration_minutes' => $validated['duration_minutes'],
            'platform' => $validated['platform'],
            'meeting_link' => $validated['meeting_link'] ?? null,
            'status' => AssessmentSession::STATUS_SCHEDULED,
            'notes' => $validated['notes'] ?? null,
        ]);

        $enquiry->update([
            'status' => Enquiry::STATUS_ASSESSMENT_BOOKED,
            'guardian_email' => $validated['guardian_email'],
        ]);

        $enquiry->timeline()->create([
            'user_id' => auth()->id(),
            'type' => EnquiryNote::TYPE_STATUS,
            'body' => 'Assessment booked for ' . $assessment->scheduleLabel()
                . ' with ' . optional($tutor->user)->name
                . ' (' . $assessment->platformLabel() . ').',
        ]);

        $this->notifyTutor($tutor, $assessment, $enquiry);
        $this->emailGuardian($assessment, $enquiry);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'assessment.booked',
            'description' => 'Assessment booked for ' . $enquiry->reference . ' on ' . $assessment->scheduled_at->format('Y-m-d H:i'),
            'model_type' => AssessmentSession::class,
            'model_id' => $assessment->id,
        ]);

        return redirect()->route('admin.enquiries.show', $enquiry)
            ->with('success', 'Assessment booked for ' . $assessment->scheduleLabel() . '. The guardian has the join link.');
    }

    public function show(AssessmentSession $assessment)
    {
        $assessment->load(['enquiry', 'tutor.user', 'recommendedClass', 'conductedBy']);

        return view('admin.assessments.show', [
            'assessment' => $assessment,
            'classes' => Classes::orderBy('name')->get(),
        ]);
    }

    public function recordResult(AssessmentResultRequest $request, AssessmentSession $assessment, AssessmentResultService $results)
    {
        $validated = $request->validated();

        try {
            $results->record($assessment, $validated, $request->file('report'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.assessments.show', $assessment)
            ->with('success', $validated['result'] === AssessmentSession::STATUS_COMPLETED
                ? 'Assessment result saved.'
                : 'Marked as a no-show. The enquiry is back in Contacted for rebooking.');
    }

    public function downloadReport(AssessmentSession $assessment)
    {
        $path = $assessment->attachment_path;

        abort_unless($path && Storage::disk('public')->exists($path), 404, 'No report was uploaded for this assessment.');

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';
        $name = 'assessment-' . (optional($assessment->enquiry)->reference ?: $assessment->id) . '.' . $extension;

        return Storage::disk('public')->download($path, $name);
    }

    public function cancel(Request $request, AssessmentSession $assessment)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $assessment->isScheduled()) {
            return back()->with('error', 'Only a scheduled assessment can be cancelled.');
        }

        $assessment->update(['status' => AssessmentSession::STATUS_CANCELLED]);

        $enquiry = $assessment->enquiry;
        $otherScheduled = $enquiry->assessments()
            ->where('status', AssessmentSession::STATUS_SCHEDULED)
            ->exists();

        if (! $otherScheduled && $enquiry->status === Enquiry::STATUS_ASSESSMENT_BOOKED) {
            $enquiry->update(['status' => Enquiry::STATUS_CONTACTED]);
        }

        $enquiry->timeline()->create([
            'user_id' => auth()->id(),
            'type' => EnquiryNote::TYPE_STATUS,
            'body' => 'Assessment cancelled' . (! empty($validated['reason']) ? ': ' . $validated['reason'] : '.'),
        ]);

        return back()->with('success', 'Assessment cancelled.');
    }

    /** The tutor is "invited" with an in-app notification carrying the join link. */
    private function notifyTutor(Tutor $tutor, AssessmentSession $assessment, Enquiry $enquiry): void
    {
        if (! $tutor->user) {
            return;
        }

        $message = $enquiry->child_name . ' on ' . $assessment->scheduleLabel();

        if ($assessment->meeting_link) {
            $message .= ' — join: ' . $assessment->meeting_link;
        }

        Notification::create([
            'user_id' => $tutor->user->id,
            'type' => Notification::TYPE_REMINDER,
            'title' => 'New assessment booked',
            'message' => $message,
            'channel' => 'in_app',
            'is_read' => false,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /** The guardian receives the join link by email; the enquiry screen offers WhatsApp too. */
    private function emailGuardian(AssessmentSession $assessment, Enquiry $enquiry): void
    {
        if (! $enquiry->guardian_email) {
            return;
        }

        try {
            Mail::to($enquiry->guardian_email)->send(new AssessmentScheduledMail(
                $enquiry->child_name,
                $enquiry->guardian_name,
                $assessment->scheduleLabel(),
                $assessment->platformLabel(),
                $assessment->meeting_link,
                optional(optional($assessment->tutor)->user)->name ?: 'the school',
                (string) $enquiry->reference
            ));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
