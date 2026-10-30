<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LearnerPasswordResetService;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * "Forgot your password?" accepts an email address **or** a username.
 *
 * Users with their own email keep the standard Laravel broker. A child with no
 * mailbox has the one-time link emailed to their guardian instead
 * (see App\Services\LearnerPasswordResetService).
 *
 * Every request receives the same neutral message, so the form cannot be used
 * to discover which usernames exist.
 */
class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request, LearnerPasswordResetService $learners)
    {
        $request->validate(
            ['login' => ['required', 'string', 'max:255']],
            ['login.required' => 'Enter your email address or username.']
        );

        $throttleKey = 'password-reset-link|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 6)) {
            return back()->withErrors([
                'login' => 'Too many reset requests. Please wait a minute and try again.',
            ])->withInput();
        }

        RateLimiter::hit($throttleKey, 60);

        $identifier = trim((string) $request->input('login'));

        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if ($user) {
            if ($user->email) {
                try {
                    Password::broker()->sendResetLink(['email' => $user->email]);
                } catch (Throwable $e) {
                    report($e);

                    return back()->withErrors([
                        'login' => 'We could not send the reset email right now. Please ask the school administrator to generate a reset link for you.',
                    ])->withInput();
                }
            } else {
                $learners->send($user);
            }
        }

        return back()
            ->with('status', 'If that account has a reset address, a one-time link is on its way.')
            ->withInput($request->only('login'));
    }
}
