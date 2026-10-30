<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The interstitial every school-provisioned account must pass through before
 * using the application (see App\Http\Middleware\EnsurePasswordIsChanged).
 *
 * The account received a temporary password by email; here the owner replaces
 * it with one only they know.
 */
class ChangePasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show()
    {
        return view('auth.passwords.change');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'Your new password must be different from the temporary one.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'That is not your current password.'])
                ->withInput();
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        $route = $this->dashboardRouteFor($user);

        $redirect = $route ? redirect()->route($route) : redirect('/');

        return $redirect->with('success', 'Your password has been updated — welcome to Excellent Scholars Hub.');
    }

    /** Same role mapping as LoginController::authenticated(). */
    private function dashboardRouteFor($user): ?string
    {
        if ($user->isAdministrator()) {
            return 'admin.dashboard';
        }

        if ($user->isTutor()) {
            return 'tutor.dashboard';
        }

        if ($user->isParent()) {
            return 'parent.dashboard';
        }

        if ($user->isLearner()) {
            return 'learner.dashboard';
        }

        return null;
    }
}
