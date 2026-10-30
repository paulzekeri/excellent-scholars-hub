<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AssessmentSession;
use App\Models\Enquiry;
use App\Models\EnquiryNote;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Note 13 phase 3 — recording the assessment result and moving the enquiry.
 *
 * A completed result with a recommended class puts the child in "Awaiting
 * enrolment"; without a class it stops at "Assessed". A no-show sends the
 * enquiry back to "Contacted" so the school can rebook (the assessment history
 * is kept).
 */
class AssessmentResultService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(AssessmentSession $assessment, array $data, ?UploadedFile $report = null): AssessmentSession
    {
        if (! $assessment->canRecordResult()) {
            throw new RuntimeException('A cancelled or no-show assessment cannot be changed — book a new one.');
        }

        $completed = ($data['result'] ?? null) === AssessmentSession::STATUS_COMPLETED;
        $enquiry = $assessment->enquiry;

        $attributes = [
            'status' => $completed ? AssessmentSession::STATUS_COMPLETED : AssessmentSession::STATUS_NO_SHOW,
            'completed_at' => now(),
            'conducted_by' => auth()->id(),
            'outcome' => $completed ? $data['outcome'] : null,
        ];

        if ($completed) {
            $attributes['strengths'] = $data['strengths'] ?? null;
            $attributes['focus_areas'] = $data['focus_areas'] ?? null;
            $attributes['notes'] = $data['notes'] ?? null;
            $attributes['recommended_class_id'] = $data['recommended_class_id'] ?? null;
            $attributes['recommended_level'] = $data['recommended_level'] ?? null;
        }

        if ($report) {
            if ($assessment->attachment_path) {
                Storage::disk('public')->delete($assessment->attachment_path);
            }

            $attributes['attachment_path'] = $report->store('assessment-reports', 'public');
        }

        $assessment->update($attributes);
        $assessment->refresh();

        $this->moveEnquiry($enquiry, $assessment, $completed);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'assessment.result',
            'description' => ($completed ? 'Assessment completed' : 'Assessment no-show recorded')
                . ' for ' . $enquiry->reference,
            'model_type' => AssessmentSession::class,
            'model_id' => $assessment->id,
        ]);

        return $assessment;
    }

    private function moveEnquiry(Enquiry $enquiry, AssessmentSession $assessment, bool $completed): void
    {
        if ($completed) {
            $target = $assessment->recommended_class_id
                ? Enquiry::STATUS_PLACED
                : Enquiry::STATUS_ASSESSED;

            if (in_array($enquiry->status, [
                Enquiry::STATUS_ASSESSMENT_BOOKED,
                Enquiry::STATUS_ASSESSED,
                Enquiry::STATUS_PLACED,
            ], true)) {
                $enquiry->update(['status' => $target]);
            }

            $body = 'Assessment completed — ' . $assessment->outcomeLabel();

            if ($assessment->recommended_class_id) {
                $body .= '; recommended ' . (optional($assessment->recommendedClass)->name ?: 'a class');
            }

            $body .= '.';
        } else {
            if ($enquiry->status === Enquiry::STATUS_ASSESSMENT_BOOKED) {
                $enquiry->update(['status' => Enquiry::STATUS_CONTACTED]);
            }

            $body = 'Assessment no-show — the enquiry is back in Contacted for rebooking.';
        }

        $enquiry->timeline()->create([
            'user_id' => auth()->id(),
            'type' => EnquiryNote::TYPE_STATUS,
            'body' => $body,
        ]);
    }
}
