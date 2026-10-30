<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * A password-reset link for a child who has no mailbox.
 *
 * It is addressed to the parent/guardian and asks them to hand the link over
 * privately — the child never needs an email address to recover their account.
 */
class LearnerPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string */
    public $childName;

    /** @var string */
    public $resetUrl;

    /** @var int */
    public $expiresInMinutes;

    public function __construct(string $childName, string $resetUrl, int $expiresInMinutes = 60)
    {
        $this->childName = $childName;
        $this->resetUrl = $resetUrl;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    public function build()
    {
        return $this
            ->subject('Password reset for ' . $this->childName)
            ->markdown('emails.learner-password-reset');
    }
}
