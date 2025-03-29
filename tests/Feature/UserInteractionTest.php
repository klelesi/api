<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserInteractionTest extends TestCase
{
    use RefreshDatabase;


    public function test_it_stores_a_view_interaction(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->markdownPost()->createOne();

        $data = [
            'type' => UserInteraction::TYPE_VIEW,
            'postId' => $post->id,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data)->assertStatus(200);

        $this->assertDatabaseHas(UserInteraction::class, ['user_id' => $user->id, 'type' =>
            UserInteraction::TYPE_VIEW]);
        $this->assertDatabaseCount(UserInteraction::class, 1);
    }

    public function test_it_stores_only_1_view_interaction(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->markdownPost()->createOne();

        $data = [
            'type' => UserInteraction::TYPE_VIEW,
            'postId' => $post->id,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data)->assertStatus(200);
        $response = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data)->assertStatus(200);

        $this->assertDatabaseHas(UserInteraction::class, ['user_id' => $user->id, 'type' =>
            UserInteraction::TYPE_VIEW]);
        $this->assertDatabaseCount(UserInteraction::class, 1);
    }

    public function test_it_returns_interactions_for_posts(): void
    {
        $user = User::factory()->create();
        $post1 = Post::factory()->markdownPost()->createOne();
        $post2 = Post::factory()->markdownPost()->createOne();
        $post3 = Post::factory()->markdownPost()->createOne();

        $interaction1 = UserInteraction::factory()->createOne(['user_id' => $user->id, 'interactable_id' =>
            $post1->id]);

        $interaction2 = UserInteraction::factory()->createOne(['user_id' => $user->id, 'interactable_id' =>
            $post2->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson(route('interactions.posts', [
            'postIds' => [$post1->id, $post2->id, $post3->id],
        ]))->assertStatus(200);

        $json = $response->json('data');

        $this->assertSame($interaction1->interactable_id, $json[0]['interactableId']);
        $this->assertSame($interaction2->interactable_id, $json[1]['interactableId']);
    }
}
