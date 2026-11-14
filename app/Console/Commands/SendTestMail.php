<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * "Does mail actually leave this server?" — sends one plain-text message now, so
 * a production .env can be verified without creating an account.
 *
 *   php artisan esh:mail-test you@example.com
 *
 * A `log`/`array` mailer accepts mail without delivering it, so the command
 * refuses with the reason instead of reporting a false success.
 */
class SendTestMail extends Command
{
    /**
     * @var string
     */
    protected $signature = 'esh:mail-test {email : Address that should receive the test message}';

    /**
     * @var string
     */
    protected $description = 'Send one test email so you can confirm the mailer works on this installation';

    public function handle()
    {
        $email = trim((string) $this->argument('email'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('That is not a valid email address.');

            return 1;
        }

        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array', ''], true)) {
            $this->error("MAIL_MAILER = '{$mailer}', so nothing is delivered.");
            $this->line('Set MAIL_MAILER=smtp and the MAIL_* values in this environment\'s .env, then run `php artisan config:clear` (or `config:cache`) and try again.');

            return 1;
        }

        try {
            Mail::raw(
                'This is a test message from '.config('app.name').' ('.config('app.url').').'
                .' If you can read it, email delivery is working on this installation.',
                function ($message) use ($email) {
                    $message->to($email)->subject('ESH mail test');
                }
            );
        } catch (Throwable $e) {
            report($e);

            $this->error('The mailer could not send the message: '.$e->getMessage());

            return 1;
        }

        $this->info("Test email sent to {$email} using the '{$mailer}' mailer. Check the inbox and the spam folder.");

        return 0;
    }
}
