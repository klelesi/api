<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;

class AuthController extends Controller
{
    public function redirect(string $provider)
    {
        if (mb_strtolower($provider) === 'github') {
            return Socialite::driver('github')->redirect();
        }

        abort(404);
    }

    public function callback(string $provider, AuthService $authService)
    {
        if (mb_strtolower($provider) === 'github') {
            return response()->json([
                'data' => [
                    'token' => $authService->getAccessTokenFromSocialiteUser(Socialite::driver('github')->user()),
                ]
            ]);
        }

        abort(404);
    }
}
