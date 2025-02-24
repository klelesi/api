<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User;

class AuthService
{
    const COOKIE_LOGGED_IN = 'logged_in';

    public function tryToLogin(User $socialiteUser, Request $request)
    {
        if (!$socialiteUser->getEmail()) {
            abort(400, "No valid emails associated with this account.");
        }

        $user = \App\Models\User::where('email', $socialiteUser->getEmail())->first();

        if (!$user) {
            $user = \App\Models\User::create([
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'email_verified_at' => now(),
                'password' => bcrypt(Str::random(64)),
            ]);
        }

        $request->session()->regenerate();
        Auth::login($user);

        Cookie::queue(Cookie::forever(self::COOKIE_LOGGED_IN, 1, httpOnly: false));

        return redirect(config('app.after_auth_redirect_url'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Cookie::expire(self::COOKIE_LOGGED_IN);
    }
}
