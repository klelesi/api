<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\WelcomeNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_a_new_user()
    {
        Notification::fake();

        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password',
        ];

        $response = $this->postJson(route('register'), $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas(User::class, ['name' => $data['name'], 'email' => $data['email'], 'email_verified_at' => null]);

        $user = User::where('email', $data['email'])->first();
        Notification::assertSentTo($user, WelcomeNotification::class);

    }

    public function test_it_fails_to_register_an_existing_user()
    {
        $user = User::factory()->create();

        $data = [
            'name' => 'John Doe',
            'email' => $user->email,
            'password' => 'password',
        ];

        $response = $this->postJson(route('register'), $data);

        $response->assertStatus(422);
    }

    public function test_a_user_can_request_a_new_password()
    {
        Notification::fake();
        $user = User::factory()->create();

        $data = ['email' => $user->email];

        $response = $this->postJson(route('password.request'), $data);
        $response->assertStatus(200);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_a_user_can_reset_a_password()
    {
        Notification::fake();

        $user = User::factory()->createOne(['email' => 'john.doe@example.com']);
        $token = Password::createToken($user);

        $data = [
            'email' => $user->email,
            'token' => $token,
            'password' => 'exampleSecret',
        ];

        $response = $this->postJson(route('password.reset'), $data);
        $response->assertStatus(200);

        //  Check if the new password works
        $response = $this->postJson(route('login'), $data);

        $response->assertStatus(200)
            ->assertJson(['data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]]);
    }

    public function test_it_logins_a_user()
    {
        $data = [
            'email' => 'john.doe@example.com',
            'password' => 'password',
        ];

        $user = User::factory()->createOne([
            'email' => 'john.doe@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson(route('login'), $data);

        $response->assertStatus(200)
            ->assertJson(['data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]]);
    }

    public function test_it_fails_to_login()
    {
        $data = [
            'email' => 'john.doe@example.com',
            'password' => 'BROKENbroken',
        ];

        $user = User::factory()->createOne([
            'email' => 'john.doe@example.com',
        ]);

        $response = $this->postJson(route('login'), $data);

        $response->assertStatus(422);
    }

    public function test_it_returns_the_authenticated_user(): void
    {
        $user = User::factory()->createOne();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('user.show'));

        $response->assertStatus(200)
            ->assertJson(['data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]]);
    }

    public function test_it_updates_the_name(): void
    {
        $user = User::factory()->createOne();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson(route('user.update'), ['name' => 'Example name']);

        $response->assertStatus(200);

        $this->assertDatabaseHas(User::class, ['name' => 'Example name']);
    }

    public function test_it_returns_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->moderator()->createOne();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('user.permissions'));

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.permissions'));
        $this->assertNotEmpty($response->json('data.roles'));
    }
}
