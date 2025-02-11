<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;
use Mockery;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_404s_for_a_missing_provider(): void
    {
        $response = $this->get(route('auth.redirect', ['provider' => 'example']));
        $response->assertStatus(404);
    }

    public function test_it_redirects_to_github(): void
    {
        $response = $this->get(route('auth.redirect', ['provider' => 'github']));
        $response->assertRedirectContains('https://github.com/login/oauth/authorize');
    }

    public function test_it_logins_a_new_user(): void
    {
        $this->mockSocialiteUser('github');

        $response = $this->get(route('auth.callback', ['provider' => 'github']));
        $response->assertStatus(200);

        $this->assertDatabaseCount(\App\Models\User::class, 1);
        $this->assertDatabaseHas(\App\Models\User::class, ['email' => 'john.doe@example.com']);

        $this->assertNotNull($response->json('data.token'));
    }

    public function test_it_logins_an_existing_user(): void
    {
        $this->mockSocialiteUser('github');
        \App\Models\User::factory()->createOne(['email' => 'john.doe@example.com']);

        $response = $this->get(route('auth.callback', ['provider' => 'github']));
        $response->assertStatus(200);

        $this->assertDatabaseCount(\App\Models\User::class, 1);
        $this->assertDatabaseHas(\App\Models\User::class, ['email' => 'john.doe@example.com']);

        $this->assertNotNull($response->json('data.token'));
    }

    private function mockSocialiteUser(string $driver): void
    {
        $socialiteUser = Mockery::mock(User::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn('john.doe@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('John Doe');

        // Mock the Socialite driver
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        // Bind the mock to Socialite facade
        Socialite::shouldReceive('driver')->with($driver)->andReturn($provider);
    }
}
