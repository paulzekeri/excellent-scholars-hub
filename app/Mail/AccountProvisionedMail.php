<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * "Here are your login details" — used for every account the school provisions
 * (a parent created by an administrator, a child added to a parent, a converted
 * enquiry) and for the guardian account created during self-registration.
 *
 * The password in this mail is temporary: the account is flagged
 * `must_change_password` and must replace it at first sign-in.
 */
class AccountProvisionedMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string */
    public $name;

    /** @var string */
    public $username;

    /** @var string */
    public $temporaryPassword;

    /** @var string */
    public $loginUrl;

    /** @var array<int, array{name: string, username: string, relationship: string}> */
    public $linkedAccounts;

    /** @var string|null Child name when this mail goes to a guardian. */
    public $guardianOf;

    /**
     * @param  array<int, array{name: string, username: string, relationship: string}>  $linkedAccounts
     */
    public function __construct(
        string $name,
        string $username,
        string $temporaryPassword,
        array $linkedAccounts = [],
        ?string $guardianOf = null
    ) {
        $this->name = $name;
        $this->username = $username;
        $this->temporaryPassword = $temporaryPassword;
        $this->linkedAccounts = $linkedAccounts;
        $this->guardianOf = $guardianOf;
        $this->loginUrl = url('/login');
    }

    public function build()
    {
        $subject = $this->guardianOf
            ? 'Excellent Scholars Hub — login details for ' . $this->guardianOf
            : 'Your Excellent Scholars Hub account';

        return $this->subject($subject)->markdown('emails.account-provisioned');
    }
}
