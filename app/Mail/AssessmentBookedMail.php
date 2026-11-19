<?php

namespace App\Mail;

use App\Models\AssessmentSession;
use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Internal school notification for an assessment request or a booked call.
 *
 * The guardian gets their own confirmation (AssessmentScheduledMail); this one
 * goes to the office so admissions can follow up even when nobody is signed in.
 */
class AssessmentBookedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Enquiry $enquiry;

    public ?AssessmentSession $assessment;

    public function __construct(Enquiry $enquiry, ?AssessmentSession $assessment = null)
    {
        $this->enquiry = $enquiry;
        $this->assessment = $assessment;
    }

    public function build()
    {
        $subject = $this->assessment
            ? 'Assessment booked — ' . $this->enquiry->child_name . ' — ' . $this->assessment->scheduleLabel()
            : 'New assessment request — ' . $this->enquiry->child_name;

        return $this
            ->subject($subject)
            ->markdown('emails.assessment-booked');
    }
}
