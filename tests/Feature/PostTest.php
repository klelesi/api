<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\LinkData;
use App\Services\LinkService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_a_markdown_post()
    {
        $user = User::factory()->create();

        $data = ['postType' => Post::POST_TYPE_MARKDOWN, 'title' => 'Example', 'markdown' => '#Hello'];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('posts.store'), $data)->assertStatus(201);
        $postId = $response->json('data.id');

        $this->assertDatabaseHas('posts', ['post_type' => Post::POST_TYPE_MARKDOWN, 'title' => $data['title'], 'author_id' => $user->id]);
        $this->assertDatabaseHas('markdowns', ['markdownable_id' => $postId, 'html' => '<h1>Hello</h1>']);
    }

    public function test_it_stores_a_link_post()
    {
        $linkData = new LinkData('Example title');
        $linkService = Mockery::mock(LinkService::class);
        $linkService->shouldReceive('parse')->andReturn($linkData);

        $this->instance(LinkService::class, $linkService);

        $user = User::factory()->create();

        $data = ['postType' => Post::POST_TYPE_LINK, 'title' => 'Example', 'url' => 'https://example.com'];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('posts.store'), $data)->assertStatus(201);
        $postId = $response->json('data.id');

        $this->assertDatabaseHas('posts', ['post_type' => Post::POST_TYPE_LINK, 'title' => $data['title'], 'author_id' => $user->id]);
        $this->assertDatabaseHas('links', ['linkable_id' => $postId, 'url' => $data['url'], 'meta' => json_encode($linkData)]);
    }

    public function test_it_fetches_a_post_by_id_or_slug()
    {
        $post = Post::factory()->markdownPost()->createOne();

        $responseTitle = $this->getJson(route('posts.show', ['id' => $post->id]))->assertStatus(200);
        $responseSlug = $this->getJson(route('posts.show', ['id' => $post->slug]))->assertStatus(200);
    }

    public function test_it_returns_all_needed_properties_for_a_post()
    {
        $post = Post::factory()->markdownPost()->createOne();

        $response = $this->getJson(route('posts.show', ['id' => $post->id]))->assertStatus(200);

        $this->assertSame($response->json('data.id'), $post->id);
        $this->assertSame($response->json('data.postType'), $post->post_type);
        $this->assertSame($response->json('data.title'), $post->title);
        $this->assertSame($response->json('data.slug'), $post->slug);
        $this->assertNotNull($response->json('data.createdAt'));
        $this->assertNotNull($response->json('data.updatedAt'));
    }

    public function test_only_the_author_can_update_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->markdownPost()->createOne(['author_id' => $user->id]);

        $imposter = User::factory()->create();

        $response = $this->actingAs($imposter, 'sanctum')->putJson(route('posts.update', ['id' => $post->id]),
            ['title' => 'Test', 'markdown' => '#Test'])->assertStatus(403);
    }

    public function test_only_the_author_can_delete_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->markdownPost()->createOne(['author_id' => $user->id]);

        $imposter = User::factory()->create();

        $response = $this->actingAs($imposter, 'sanctum')->deleteJson(route('posts.delete', ['id' => $post->id]))
            ->assertStatus(403);
    }

    public function test_only_the_author_can_restore_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->markdownPost()->createOne(['author_id' => $user->id, 'deleted_at' => Carbon::now()]);

        $imposter = User::factory()->create();

        $response = $this->actingAs($imposter, 'sanctum')->postJson(route('posts.restore', ['id' => $post->id]))
            ->assertStatus(403);
    }

    public function test_it_updates_a_markdown_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->markdownPost()->createOne(['author_id' => $user->id]);

        $data = ['title' => 'Example', 'markdown' => '#Hello'];

        $response = $this->actingAs($user, 'sanctum')->putJson(route('posts.update', ['id' => $post->id]), $data)
            ->assertStatus(200);

        $this->assertDatabaseHas('posts', ['title' => $data['title'], 'author_id' => $user->id]);
        $this->assertDatabaseHas('markdowns', ['html' => '<h1>Hello</h1>']);
    }

    public function test_it_updates_a_link_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->linkPost()->createOne(['author_id' => $user->id]);

        $data = ['title' => 'Example'];

        $response = $this->actingAs($user, 'sanctum')->putJson(route('posts.update', ['id' => $post->id]), $data)
            ->assertStatus(200);

        $this->assertDatabaseHas('posts', ['title' => $data['title'], 'author_id' => $user->id]);
    }

    public function test_it_deletes_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->markdownPost()->createOne(['author_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson(route('posts.delete', ['id' => $post->id]))
            ->assertStatus(200);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_it_restores_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->markdownPost()->createOne(['author_id' => $user->id, 'deleted_at' => Carbon::now()]);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson(route('posts.restore', ['id' => $post->id]))
            ->assertStatus(200);

        $this->assertNotSoftDeleted('posts', ['id' => $post->id]);
    }
}
