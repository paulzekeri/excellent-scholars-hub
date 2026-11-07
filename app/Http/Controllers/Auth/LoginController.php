<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * The login field accepts an email address or a username (note 12: children
     * often have no mailbox, so they sign in with the username the school gave
     * them).
     */
    public function username()
    {
        return 'login';
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    protected function credentials(Request $request)
    {
        return [
            'login' => trim((string) $request->input($this->username())),
            'password' => $request->input('password'),
            // Deactivated accounts cannot sign in, whichever identifier they use.
            'is_active' => 1,
        ];
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->isAdministrator()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isTutor()) {
            return redirect()->route('tutor.dashboard');
        } elseif ($user->isParent()) {
            return redirect()->route('parent.dashboard');
        } elseif ($user->isLearner()) {
            return redirect()->route('learner.dashboard');
        }

        return redirect()->intended($this->redirectTo);
    }
}
