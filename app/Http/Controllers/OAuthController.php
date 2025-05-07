<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocialiteRedirectRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function redirect(string $provider, Request $request)
    {
        $request->session()->regenerate();
        Session::put('flow', $request->query('flow'));

        if (mb_strtolower($provider) === 'github') {
            return Socialite::driver('github')
                ->redirect();
        }

        abort(404);
    }

    public function callback(string $provider, AuthService $authService, Request $request)
    {
        $flow = Session::get('flow', 'login');
        $request->session()->regenerate();

        if (mb_strtolower($provider) === 'github') {
            switch (mb_strtolower($flow)) {
                case 'login':
                    return $authService->tryToLogin(Socialite::driver('github')->user(), $request);
                case 'register':
                    return $authService->tryToRegister(Socialite::driver('github')->user(), $request);
                case 'link':
                    return $authService->tryToLink(Socialite::driver('github')->user(), $request);
            }
        }

        abort(404);
    }

    public function logout(AuthService $authService, Request $request)
    {
        $authService->logout($request);

        return response()->json();
    }
}
