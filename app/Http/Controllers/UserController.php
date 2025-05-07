<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\PasswordRequestRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\ReadNotificationRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\UserResource;
use App\Models\Notification;
use App\Models\SocialiteUser;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use App\Services\FrontendAppHelper;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\SlackAlerts\Facades\SlackAlert;

class UserController extends Controller
{
    public function register(RegisterUserRequest $request, FrontendAppHelper $helper)
    {
        $user = $this->handleRegister($request);

        $request->session()->regenerate();
        Auth::login($user);

        $user->notify(new WelcomeNotification($helper));
        SlackAlert::message(":tada: Nov uporabnik! :tada:");

        return new UserResource($user);
    }

    public function login(LoginUserRequest $request)
    {
        if (Auth::attempt($request->safe(['email', 'password']))) {
            $request->session()->regenerate();

            return new UserResource(Auth::user());
        }

        return response()->json([
            'message' => 'Tole pa ni pravilno.',
            'errors' => ['password' => ['Tole pa ni pravilno.']],
        ], 422);
    }

    public function deleteProvider(string $provider, Request $request)
    {
        SocialiteUser::where('user_id', $request->user()->id)
            ->where('provider', $provider)
            ->delete();

        return new UserResource(Auth::user()->refresh());
    }

    public function passwordRequest(PasswordRequestRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::ResetLinkSent
            ? response()->json(['data' => null], 200)
            : response()->json(['errors' => ['email' => [__($status)]]], 400);
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
            : response()->json(['errors' => ['token' => [__($status)]]], 400);
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

    public function permissions(Request $request)
    {
        $user = $request->user();

        return response()->json(['data' => [
            'permissions' => $user->getAllPermissions()->map(function ($item) {
                return $item->name;
            }),
            'roles' => $user->getRoleNames(),
        ]]);
    }

    public function notifications(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->where('read_at', null)
            ->with(['post', 'comment'])
            ->get();

        return NotificationResource::collection($notifications);
    }

    public function readNotification(ReadNotificationRequest $request)
    {
        $notificationId = $request->validated('notificationId');

        $notification = Notification::where('user_id', $request->user()->id)
            ->where('id', $notificationId)
            ->first();

        if ($notification) {
            $notification->read_at = now();
            $notification->save();
        }

        return response()->json(['data' => null]);
    }

    private function handleRegister(RegisterUserRequest $request)
    {
        if ($token = $request->validated('token')) {
            return $this->handleOAuthRegister($request, $token);
        }

        //  via non OAuth
        return User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => bcrypt($request->validated('password')),
            'username' => $request->validated('username'),
        ]);
    }

    private function handleOAuthRegister(RegisterUserRequest $request, string $token)
    {
        $socialiteUser = SocialiteUser::where('id', $token)->first();

        if (!$socialiteUser) {
            abort(400, "No user associated with this account. Please redo the OAuth process.");
        }

        if ($socialiteUser->user_id) {
            abort(400, "A user is already associated with this account. Please login.");
        }

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => bcrypt(Str::password()),
            'username' => $request->validated('username'),
        ]);

        $socialiteUser->user_id = $user->id;
        $socialiteUser->save();

        return $user;
    }
}
