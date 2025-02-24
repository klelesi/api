<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthService
{
    public function tryToLogin(\Laravel\Socialite\Contracts\User $socialiteUser, Request $request)
    {
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

        return redirect(config('app.after_auth_redirect_url'));
    }
}
