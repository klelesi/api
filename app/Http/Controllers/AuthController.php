<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function callback(string $provider, AuthService $authService, Request $request)
    {
        $request->session()->regenerate();

        if (mb_strtolower($provider) === 'github') {
            return $authService->tryToLogin(Socialite::driver('github')->user(), $request);
        }

        abort(404);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(config('app.login_redirect_url'));
    }
}
