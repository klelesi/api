<?php

namespace App\Services;

class FrontendAppHelper
{

    public function getRegisterFormUrl(array $params = []): string
    {
        return url()->query(config('app.frontend_url') . '/registracija', $params);
    }

    public function getLoginFormUrl(array $params = []): string
    {
        return url()->query(config('app.frontend_url') . '/prijava', $params);
    }

    public function getPasswordResetUrl(string $token): string
    {
        return url()->query(config('app.frontend_url') . '/ponastavi-geslo', ['token' => $token]);
    }

    public function getErrorPage(string $code)
    {
        return url()->query(config('app.frontend_url') . '/napaka/' . $code);
    }

    public function getUserProfileUrl()
    {
        return url()->query(config('app.frontend_url') . '/profil/');
    }
}
