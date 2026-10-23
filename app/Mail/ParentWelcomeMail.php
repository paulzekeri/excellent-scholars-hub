<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ParentWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $parentName;
    public $parentEmail;
    public $defaultPassword;
    public $learnerName;

    /**
     * Create a new message instance.
     */
    public function __construct($parentName, $parentEmail, $defaultPassword, $learnerName)
    {
        $this->parentName = $parentName;
        $this->parentEmail = $parentEmail;
        $this->defaultPassword = $defaultPassword;
        $this->learnerName = $learnerName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Welcome to Excellent Scholars Hub - Your Parent Account')
                    ->markdown('emails.parent-welcome');
    }
}
