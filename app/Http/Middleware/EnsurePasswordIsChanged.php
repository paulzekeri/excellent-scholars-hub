<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * School-provisioned accounts (a parent created by an administrator, a child
 * added to a parent, a converted enquiry) receive a temporary password by
 * email. Until they replace it they may only reach the change-password screen
 * or log out.
 *
 * The flag lives on the user row, so it cannot be skipped with a deep link or
 * by closing a dialog.
 */
class EnsurePasswordIsChanged
{
    /** Routes a flagged user is still allowed to reach. */
    private const ALLOWED = [
        'password.change',
        'password.change.update',
        'logout',
    ];

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $request->routeIs(self::ALLOWED)) {
            return redirect()
                ->route('password.change')
                ->with('error', 'Please choose your own password before continuing.');
        }

        return $next($request);
    }
}
