<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The guardian's confirmation for a booked assessment: the date/time, the tutor
 * and the Google Meet/Zoom link to join from any device (no account needed).
 */
class AssessmentScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string */
    public $childName;

    /** @var string */
    public $guardianName;

    /** @var string */
    public $scheduleLabel;

    /** @var string */
    public $platformLabel;

    /** @var string|null */
    public $meetingLink;

    /** @var string */
    public $tutorName;

    /** @var string */
    public $reference;

    public function __construct(
        string $childName,
        string $guardianName,
        string $scheduleLabel,
        string $platformLabel,
        ?string $meetingLink,
        string $tutorName,
        string $reference
    ) {
        $this->childName = $childName;
        $this->guardianName = $guardianName;
        $this->scheduleLabel = $scheduleLabel;
        $this->platformLabel = $platformLabel;
        $this->meetingLink = $meetingLink;
        $this->tutorName = $tutorName;
        $this->reference = $reference;
    }

    public function build()
    {
        return $this
            ->subject('Assessment booked for ' . $this->childName . ' — ' . $this->scheduleLabel)
            ->markdown('emails.assessment-scheduled');
    }
}
