<?php

namespace Evergreen\Generic\App\Http\Controllers\Auth;

use Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Http\Controllers\Auth\LoginController as LaravelLoginController;

class LoginController extends LaravelLoginController
{
    protected $redirectTo = '/your_details';
    
    public function showLoginForm()
    {
        return view('egl::auth.login');
    }

    public function redirectPath()
    {
        $user = Auth::user();
        $perm = "list-user";
        $redirect = "/user";
        $backup = "/home";

        if (config("egc.redirect")) {
            $r = config("egc.redirect");
            if (isset($r['permission'])) {
                $perm = $r['permission'];
            }
            if (isset($r['path'])) {
                $redirect = $r['path'];
            }
            if (isset($r['backup'])) {
                $backup = $r['backup'];
            }
        }

        if ($user->can($perm)) {
            $this->redirectTo = $redirect;
        }

        // dd($this->redirectTo);
        return property_exists($this, 'redirectTo') ? $this->redirectTo : $backup;
    }
}
