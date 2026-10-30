<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LearnerPasswordResetService;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The page an emailed reset link opens.
 *
 * Two shapes are supported:
 *  - the standard flow (email + token) from the Laravel broker;
 *  - the guardian flow (uid + token) for a child who has no mailbox, where the
 *    link was emailed to the parent/guardian by LearnerPasswordResetService.
 */
class ResetPasswordController extends Controller
{
    use ResetsPasswords {
        showResetForm as protected showEmailResetForm;
        reset as protected resetWithEmail;
    }

    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showResetForm(Request $request, $token = null, LearnerPasswordResetService $learners)
    {
        if (! $request->filled('uid')) {
            return $this->showEmailResetForm($request, $token);
        }

        $user = User::find($request->input('uid'));

        // Only show the form (and the child's username) for a live token, so the
        // page cannot be used to discover usernames from user ids.
        if (! $user || ! $token || ! $learners->tokenIsValid($user, (string) $token)) {
            return redirect()->route('password.request')
                ->with('status', 'That reset link is invalid or has expired. Please request a new one.');
        }

        return view('auth.passwords.reset')->with([
            'token' => $token,
            'email' => $user->email,
            'uid' => $user->id,
            'identifier' => $user->username ?: $user->email,
        ]);
    }

    public function reset(Request $request, LearnerPasswordResetService $learners)
    {
        if (! $request->filled('uid')) {
            return $this->resetWithEmail($request);
        }

        $request->validate([
            'uid' => ['required', 'integer'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::find($request->input('uid'));

        if (! $user || ! $learners->tokenIsValid($user, (string) $request->input('token'))) {
            return back()
                ->withInput($request->only('uid'))
                ->withErrors(['token' => 'This password reset link is invalid or has expired.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'must_change_password' => false,
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        $learners->forget($user);

        Auth::login($user);

        return redirect($this->redirectPath())->with('success', 'Your password has been reset.');
    }

    /** Send the user to the dashboard for their role after a successful reset. */
    protected function redirectPath(): string
    {
        $user = auth()->user();

        if ($user) {
            if ($user->isAdministrator()) {
                return route('admin.dashboard');
            }

            if ($user->isTutor()) {
                return route('tutor.dashboard');
            }

            if ($user->isParent()) {
                return route('parent.dashboard');
            }

            if ($user->isLearner()) {
                return route('learner.dashboard');
            }
        }

        return $this->redirectTo;
    }
}
