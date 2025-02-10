<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

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
}
