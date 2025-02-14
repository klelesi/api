<?php

namespace Tests\Feature;

use App\Models\Post;
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
}
