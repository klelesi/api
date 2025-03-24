<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\PasswordRequestRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => bcrypt($request->validated('name')),
        ]);

        $request->session()->regenerate();
        Auth::login($user);

        $user->notify(new WelcomeNotification());

        return new UserResource($user);
    }

    public function login(LoginUserRequest $request)
    {
        if (Auth::attempt($request->safe(['email', 'password']))) {
            $request->session()->regenerate();

            return new UserResource(Auth::user());
        }

        return response()->json(['data' => [
            'errors' => ['email' => ['Tole pa ni pravilno.']],
        ]], 422);
    }

    public function passwordRequest(PasswordRequestRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::ResetLinkSent
            ? response()->json(['data' => null], 200)
            : response()->json(['data' => ['email' => [__($status)]]], 400);
    }

    public function passwordReset(PasswordResetRequest $request)
    {
        $creds = $request->only('email', 'password', 'token');

        $status = Password::reset(
            $creds,
            function (\App\Models\User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PasswordReset
            ? response()->json(['data' => null], 200)
            : response()->json(['data' => ['email' => [__($status)]]], 400);
    }

    public function show(Request $request)
    {
        return new UserResource($request->user());
    }

    public function update(UpdateUserRequest $request)
    {
        $user = $request->user();

        $user->fill($request->validated());
        $user->update();

        return new UserResource($user->refresh());
    }
}
