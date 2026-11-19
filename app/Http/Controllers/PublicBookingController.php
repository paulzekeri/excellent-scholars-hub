<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\EnquiryNote;
use App\Models\Grade;
use App\Models\Notification;
use App\Models\User;
use App\Services\AssessmentNotifier;
use Illuminate\Http\Request;

/**
 * Public assessment booking (note 13, phase 2).
 *
 * A parent can request an assessment from the landing page without an account —
 * the child may never register, so this creates an enquiry only. The school is
 * notified, contacts the guardian by email/WhatsApp, creates the Meet/Zoom link
 * and invites the tutor.
 */
class PublicBookingController extends Controller
{
    public function store(Request $request)
    {
        // Honeypot: real people never fill this hidden field.
        if (filled($request->input('website'))) {
            return redirect('/#book-assessment')
                ->with('success', 'Thank you — we will contact you shortly to arrange the assessment.');
        }

        $validated = $request->validate([
            'child_name' => ['required', 'string', 'max:255'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_phone' => ['required', 'string', 'max:30'],
            'guardian_email' => ['required', 'string', 'email', 'max:255'],
            'preferred_at' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'guardian_email.required' => 'We need an email address to send the assessment link.',
        ]);

        $grade = ! empty($validated['grade_id']) ? Grade::find($validated['grade_id']) : null;

        $enquiry = Enquiry::create([
            'child_name' => $validated['child_name'],
            'grade_id' => $grade ? $grade->id : null,
            'child_grade_level' => $grade ? $grade->label() : null,
            'guardian_name' => $validated['guardian_name'],
            'guardian_phone' => $validated['guardian_phone'],
            'guardian_email' => $validated['guardian_email'],
            'preferred_at' => $validated['preferred_at'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'source' => Enquiry::SOURCE_WEBSITE,
            'status' => Enquiry::STATUS_NEW,
        ]);

        $body = 'Assessment requested from the public website';

        if ($enquiry->preferred_at) {
            $body .= ' (preferred: ' . $enquiry->preferred_at->format('D j M Y, g:i a') . ')';
        }

        $enquiry->timeline()->create([
            'user_id' => null,
            'type' => EnquiryNote::TYPE_STATUS,
            'body' => $body . '.',
        ]);

        $this->notifyStaff($enquiry);

        // The office also gets this by email (To/CC configured per environment).
        app(AssessmentNotifier::class)->notifySchool($enquiry);

        return redirect('/#book-assessment')
            ->with('success', 'Thank you — we have your request (' . $enquiry->reference . '). We will contact you within one working day to arrange the assessment.');
    }

    /** Every administrator and the CEO should see a new public request. */
    private function notifyStaff(Enquiry $enquiry): void
    {
        $staff = User::visible()->whereHas('role', function ($query) {
            $query->whereIn('slug', ['admin', 'ceo']);
        })->get();

        foreach ($staff as $member) {
            Notification::create([
                'user_id' => $member->id,
                'type' => Notification::TYPE_ALERT,
                'title' => 'New assessment request',
                'message' => $enquiry->child_name . ' (' . $enquiry->guardian_name . ') requested an assessment. Ref ' . $enquiry->reference . '.',
                'channel' => 'in_app',
                'is_read' => false,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }
    }
}
