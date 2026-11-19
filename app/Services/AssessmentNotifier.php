<?php

namespace App\Services;

use App\Mail\AssessmentBookedMail;
use App\Models\AssessmentSession;
use App\Models\Enquiry;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the internal assessment notification to the school office.
 *
 * Addresses come from config/notifications.php (overridable through
 * ASSESSMENT_NOTIFY_TO / ASSESSMENT_NOTIFY_CC). A mail failure is logged and
 * never blocks the booking itself.
 */
class AssessmentNotifier
{
    public function notifySchool(Enquiry $enquiry, ?AssessmentSession $assessment = null): bool
    {
        $to = $this->addresses(config('notifications.assessment.to'));
        $cc = $this->addresses(config('notifications.assessment.cc'));

        if ($to === []) {
            return false;
        }

        try {
            Mail::to($to)->cc($cc)->send(new AssessmentBookedMail($enquiry, $assessment));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    /** @return array<int, string> */
    private function addresses($value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return collect((array) $value)
            ->map(function ($email) {
                return trim((string) $email);
            })
            ->filter(function ($email) {
                return $email !== '';
            })
            ->unique()
            ->values()
            ->all();
    }
}
