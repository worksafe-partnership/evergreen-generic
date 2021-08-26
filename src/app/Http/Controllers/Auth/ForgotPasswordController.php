<?php

namespace Evergreen\Generic\App\Http\Controllers\Auth;

use Password;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\ForgotPasswordController as LaravelForgotPasswordController;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

class ForgotPasswordController extends LaravelForgotPasswordController
{
    use SendsPasswordResetEmails;

    public function showLinkRequestForm()
    {
        return view('egl::auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendResetLink(
            $request->only('email')
        );

        toast()->success("If an account exists with the provided details, a password reset email has been sent...")->timeout(3000);
        return $this->sendResetLinkResponse($response);
    }
}
