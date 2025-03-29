<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Models\UserInteraction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_a_paginated_feed()
    {
        $posts = Post::factory()->markdownPost()->count(100)->create();

        $response = $this->getJson(route('feed'))->assertStatus(200);

        $this->assertCount(50, $response->json('data'));
        $this->assertNotNull($response->json('meta'));
    }

    public function test_it_fetches_an_ordered_paginated_feed()
    {
        $count = 50;
        $posts = Post::factory()->markdownPost()->count($count)->create();

        $response = $this->getJson(route('feed'))->assertStatus(200);
        $posts = $response->json('data');

        for ($i = 1; $i < $count; $i++) {
            $this->assertTrue(Carbon::parse($posts[$i - 1]['createdAt'])->isAfter(Carbon::parse
            ($posts[$i]['createdAt'])));
        }
    }

    public function test_it_fetches_interactions_for_logged_in_user()
    {
        $user = User::factory()->createOne();
        $posts = Post::factory()->markdownPost()->count(3)->create();

        $interaction = UserInteraction::factory()->createOne(['user_id' => $user->id, 'interactable_id' =>
            $posts[0]->id]);

        UserInteraction::factory()->count(10)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson(route('feed'))->assertStatus(200);
        $posts = $response->json('data');

        foreach ($posts as $post) {
            if ($post['id'] === $interaction->interactable_id) {
                $this->assertCount(1, $post['interactions']);
            } else {
                $this->assertCount(0, $post['interactions']);
            }
        }
    }

    public function test_it_doesnt_load_interactions_for_guests()
    {
        $user = User::factory()->createOne();
        $posts = Post::factory()->markdownPost()->count(3)->create();

        UserInteraction::factory()->createOne(['user_id' => $user->id, 'interactable_id' => $posts[0]->id]);

        $response = $this->getJson(route('feed'))->assertStatus(200);
        $posts = $response->json('data');

        foreach ($posts as $post) {
            $this->assertFalse(isset($post['interactions']));
        }
    }
}
