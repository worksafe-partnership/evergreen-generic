<?php

namespace Evergreen\Generic\App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\ResetPasswordController as LaravelResetPasswordController;

class ResetPasswordController extends LaravelResetPasswordController
{
    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('egl::auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }
}
