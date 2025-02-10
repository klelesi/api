<?php

namespace App\Services;

use Illuminate\Support\Str;

class AuthService
{
    public function getAccessTokenFromSocialiteUser(\Laravel\Socialite\Contracts\User $socialiteUser): string
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

        return $user->createToken('default')->plainTextToken;
    }
}
