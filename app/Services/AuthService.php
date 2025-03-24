<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User;

class AuthService
{
    public function tryToLogin(User $socialiteUser, Request $request)
    {
        if (!$socialiteUser->getEmail()) {
            abort(400, "No valid emails associated with this account.");
        }

        $user = \App\Models\User::where('email', $socialiteUser->getEmail())->first();

        if (!$user) {
            abort(400, "No registered user with this email address. Please register.");
        }

        $request->session()->regenerate();
        Auth::login($user);

        return redirect(config('app.after_auth_redirect_url'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
