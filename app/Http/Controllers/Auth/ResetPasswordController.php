<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;

/**
 * The page the emailed reset link opens. A valid, unused token lets the user set
 * a new password and is then signed in to their own dashboard.
 */
class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest');
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
