<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Throwable;

/**
 * "Forgot your password?" — emails a one-time reset link.
 *
 * The link is valid for 60 minutes (config/auth.php) and can be used once. If
 * the mail server is not reachable the request fails gracefully with a message
 * pointing the user at the school administrator, who can generate a link from
 * the Users screen instead of seeing a 500 error.
 */
class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        try {
            $response = $this->broker()->sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            report($e);

            return $this->sendResetLinkFailedResponse(
                $request,
                'We could not send the reset email right now. Please ask the school administrator to generate a reset link for you.'
            );
        }

        return $response === Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }
}
