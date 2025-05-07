<?php

namespace App\Services;

use App\Models\SocialiteUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User;

class AuthService
{
    public function __construct(private FrontendAppHelper $frontendAppHelper)
    {
    }

    public function tryToLogin(User $providedUser, Request $request)
    {
        $socialiteUser = SocialiteUser::where('provider_user_id', $providedUser->getId())
            ->whereNotNull('user_id')
            ->with('user')
            ->first();

        if (!$socialiteUser) {
            $user = \App\Models\User::where('email', $providedUser->getEmail())->first();
            if ($user) {
                return redirect($this->frontendAppHelper->getErrorPage('auth-github-link-account'));
            }

            return redirect($this->frontendAppHelper->getErrorPage('auth-github-user-not-found'));
        }

        $request->session()->regenerate();
        Auth::login($socialiteUser->user);

        return redirect($this->frontendAppHelper->getLoginFormUrl());
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function tryToRegister(User $providedUser, Request $request)
    {
        $socialiteUser = SocialiteUser::where('provider_user_id', $providedUser->getId())
            ->where('provider', 'github')
            ->whereNotNull('user_id')
            ->with('user')
            ->first();

        if ($socialiteUser) {
            return $this->tryToLogin($providedUser, $request);
        }

        $socialiteUser = SocialiteUser::updateOrCreate([
            'provider' => 'github',
            'provider_user_id' => $providedUser->getId(),
        ], [
            'user_id' => null,
            'provider_user_email' => $providedUser->getEmail(),
        ]);

        $formData = [
            'email' => $providedUser->getEmail(),
            'username' => $providedUser->getNickname(),
            'name' => $providedUser->getName(),
            'token' => $socialiteUser->id,
        ];

        return redirect($this->frontendAppHelper->getRegisterFormUrl($formData));
    }

    public function tryToLink(User $providedUser, Request $request)
    {
        if (!$request->user()) {
            abort(401, 'You must be logged in to link accounts.');
        }



        $existingLink = SocialiteUser::where('provider_user_id', $providedUser->getId())
            ->where('provider', 'github')
            ->whereNotNull('user_id')
            ->first();

        if ($existingLink) {
            return redirect($this->frontendAppHelper->getErrorPage('auth-github-link-exist'));
        }

        $socialiteUser = SocialiteUser::updateOrCreate([
            'provider' => 'github',
            'provider_user_id' => $providedUser->getId(),
        ], [
            'user_id' => $request->user()->id,
            'provider_user_email' => $providedUser->getEmail(),
        ]);

        return redirect($this->frontendAppHelper->getUserProfileUrl());
    }
}
