<?php

namespace Tests\Feature;

use App\Models\SocialiteUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;
use Mockery;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_404s_for_a_missing_provider(): void
    {
        $response = $this->get(route('auth.redirect', ['provider' => 'example', 'flow' => 'login']));
        $response->assertStatus(404);
    }

    public function test_it_redirects_to_github_flow_login(): void
    {
        $response = $this->get(route('auth.redirect', ['provider' => 'github', 'flow' => 'login']));
        $response->assertSessionHas('flow', 'login');
    }

    public function test_it_redirects_to_github_flow_register(): void
    {
        $response = $this->get(route('auth.redirect', ['provider' => 'github', 'flow' => 'register']));
        $response->assertSessionHas('flow', 'register');
    }

    public function test_it_redirects_to_github_flow_link(): void
    {
        $response = $this->get(route('auth.redirect', ['provider' => 'github', 'flow' => 'link']));
        $response->assertSessionHas('flow', 'link');
    }

    public function test_it_logins_an_existing_linked_github_user(): void
    {
        $user = \App\Models\User::factory()->createOne();
        $socialiteUser = SocialiteUser::factory()->createOne([
            'provider_user_id' => '123456789',
            'user_id' => $user->id
        ]);
        $this->mockSocialiteUser('github');

        $response = $this->get(route('auth.callback', ['provider' => 'github', 'flow' => 'login']));
        $response->assertStatus(302);

        $this->assertAuthenticated('sanctum');
    }

    public function test_it_doesnt_login_a_non_linked_github_user(): void
    {
        $user = \App\Models\User::factory()->createOne();
        $socialiteUser = SocialiteUser::factory()->createOne([
            'provider_user_id' => 'invalid',
            'user_id' => $user->id
        ]);
        $this->mockSocialiteUser('github');

        $response = $this->get(route('auth.callback', ['provider' => 'github', 'flow' => 'login']));
        $response->assertStatus(302)->assertRedirectContains('/napaka/');

        $this->assertFalse($this->isAuthenticated('sanctum'));
    }

    public function test_it_returns_a_register_form_with_socialite_data(): void
    {
        $this->mockSocialiteUser('github');

        $response = $this->withSession(['flow' => 'register'])->get(route('auth.callback', ['provider' => 'github']));
        $url = $response->headers->get('Location');

        $this->assertStringContainsString('token=', $url);
        $this->assertStringContainsString('email=', $url);
        $this->assertStringContainsString('username=', $url);
        $this->assertStringContainsString('name=', $url);

        $this->assertDatabaseHas(\App\Models\SocialiteUser::class, ['provider' => 'github', 'provider_user_id' => '123456789', 'user_id' => null]);
    }

    public function test_it_register_a_user_via_socialite(): void
    {
        Notification::fake();

        $socialiteUser = SocialiteUser::factory()->createOne();

        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'username' => 'john.doe',
            'password' => 'password',
            'token' => $socialiteUser->id,
        ];

        $response = $this->postJson(route('register'), $data)->assertStatus(201);

        $user = \App\Models\User::first();

        $this->assertDatabaseHas(\App\Models\SocialiteUser::class, ['provider' => 'github', 'user_id' => $user->id]);
        $this->assertAuthenticated('sanctum');
    }

    public function test_it_links_a_logged_in_account_with_github(): void
    {
        $user = \App\Models\User::factory()->createOne();
        $socialiteUser = SocialiteUser::factory()->createOne(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('auth.provider.delete',
            ['provider' =>         'github']));

        $this->assertDatabaseEmpty(\App\Models\SocialiteUser::class);
        $this->assertAuthenticated('sanctum');
    }

    public function test_it_deletes_a_provider(): void
    {
        $user = \App\Models\User::factory()->createOne();
        $this->mockSocialiteUser('github');

        $response = $this->actingAs($user)->withSession(['flow' => 'link'])->get(route('auth.callback',
            ['provider' =>
                'github']));

        $this->assertDatabaseHas(\App\Models\SocialiteUser::class, ['provider' => 'github', 'user_id' => $user->id]);
        $this->assertAuthenticated('sanctum');
    }

    private function mockSocialiteUser(string $driver, ?string $email = 'john.doe@example.com', ?string $id = '123456789'): void
    {
        $socialiteUser = Mockery::mock(User::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn('John Doe');
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getNickname')->andReturn('johnDoe');

        // Mock the Socialite driver
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        // Bind the mock to Socialite facade
        Socialite::shouldReceive('driver')->with($driver)->andReturn($provider);
    }

}
