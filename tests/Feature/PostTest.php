<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\LinkData;
use App\Services\LinkService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
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

    public function test_it_fetches_a_post_and_comments()
    {
        $post = Post::factory()->markdownPost()->createOne();
        $comment1 = Comment::factory()->createOne(['commentable_id' => $post->id, 'commentable_type' => Post::class]);
        $comment2 = Comment::factory()->createOne(['commentable_id' => $post->id, 'commentable_type' => Post::class]);
        $comment3 = Comment::factory()->createOne(['commentable_id' => $post->id, 'commentable_type' => Post::class]);

        $response = $this->getJson(route('posts.show', ['id' => $post->id]))->assertStatus(200);

        $this->assertCount(3, $response->json('data.comments'));
    }

    public function test_it_returns_all_needed_properties_for_a_markdown_post()
    {
        $post = Post::factory()->markdownPost()->createOne();

        $response = $this->getJson(route('posts.show', ['id' => $post->id]))->assertStatus(200);

        $this->assertSame($response->json('data.id'), $post->id);
        $this->assertSame($response->json('data.postType'), $post->post_type);
        $this->assertSame($response->json('data.numberOfComments'), $post->number_of_comments);
        $this->assertSame($response->json('data.title'), $post->title);
        $this->assertSame($response->json('data.slug'), "/guna/" . $post->slug);
        $this->assertSame($response->json('data.html'), $post->markdown->html);
        $this->assertSame($response->json('data.author.id'), $post->author->id);
        $this->assertSame($response->json('data.author.name'), $post->author->name);
        $this->assertNull($response->json('data.lockedAt'));
        $this->assertNotNull($response->json('data.createdAt'));
        $this->assertNotNull($response->json('data.updatedAt'));
    }

    public function test_it_returns_all_needed_properties_for_a_link_post()
    {
        $post = Post::factory()->linkPost()->createOne();

        $response = $this->getJson(route('posts.show', ['id' => $post->id]))->assertStatus(200);

        $this->assertSame($response->json('data.id'), $post->id);
        $this->assertSame($response->json('data.postType'), $post->post_type);
        $this->assertSame($response->json('data.numberOfComments'), $post->number_of_comments);
        $this->assertSame($response->json('data.title'), $post->title);
        $this->assertSame($response->json('data.slug'), "/guna/" . $post->slug);
        $this->assertSame($response->json('data.url'), $post->link->url);
        $this->assertSame($response->json('data.author.id'), $post->author->id);
        $this->assertSame($response->json('data.author.name'), $post->author->name);
        $this->assertNotNull($response->json('data.urlMeta'));
        $this->assertNull($response->json('data.lockedAt'));
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

    public function test_it_returns_a_comments_tree()
    {
        $post = Post::factory()->markdownPost()->createOne();
        $comment1 = Comment::factory()->createOne(['commentable_id' => $post->id, 'commentable_type' => Post::class,
            'created_at' => Carbon::now(),]);
        $comment2 = Comment::factory()->createOne(['commentable_id' => $post->id, 'commentable_type' => Post::class,
            'created_at' => Carbon::now(),]);
        $comment3 = Comment::factory()->createOne(['commentable_id' => $post->id, 'commentable_type' => Post::class,
            'parent_id' => $comment1->id, 'created_at' => Carbon::now(),]);

        $response = $this->getJson(route('posts.show', ['id' => $post->id]))->assertStatus(200);

        $this->assertCount(2, $response->json('data.comments'));
        $this->assertCount(1, $response->json('data.comments')[0]['comments']);
    }

    public function test_it_prevents_updates_to_a_locked_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->markdownPost()->createOne(['author_id' => $user->id, 'locked_at' => Carbon::now()]);

        $data = ['title' => 'Example', 'markdown' => '#Hello'];

        $response = $this->actingAs($user, 'sanctum')->putJson(route('posts.update', ['id' => $post->id]), $data)
            ->assertStatus(400);
    }

    public function test_a_moderator_deletes_a_post()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $moderator = User::factory()->moderator()->createOne();
        $post = Post::factory()->markdownPost()->createOne([]);

        $response = $this->actingAs($moderator, 'sanctum')->deleteJson(route('posts.delete', ['id' => $post->id]))
            ->assertStatus(200);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_a_moderator_restores_a_post()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $moderator = User::factory()->moderator()->createOne();
        $post = Post::factory()->markdownPost()->createOne(['deleted_at' => Carbon::now()]);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);

        $response = $this->actingAs($moderator, 'sanctum')->postJson(route('posts.restore', ['id' => $post->id]))
            ->assertStatus(200);

        $this->assertNotSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_a_moderator_can_update_a_markdown_post()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $moderator = User::factory()->moderator()->createOne();
        $post = Post::factory()->markdownPost()->createOne([]);

        $data = ['title' => 'Example', 'markdown' => '#Hello'];

        $response = $this->actingAs($moderator, 'sanctum')->putJson(route('posts.update', ['id' => $post->id]), $data)
            ->assertStatus(200);

        $this->assertDatabaseHas('posts', ['title' => $data['title']]);
        $this->assertDatabaseHas('markdowns', ['html' => '<h1>Hello</h1>']);
    }

    public function test_a_moderator_locks_a_post()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $moderator = User::factory()->moderator()->createOne();
        $post = Post::factory()->markdownPost()->createOne([]);

        $response = $this->actingAs($moderator, 'sanctum')->postJson(route('posts.lock', ['id' => $post->id]))
            ->assertStatus(200);

        $this->assertTrue($post->refresh()->isLocked());
    }

    public function test_a_moderator_unlocks_a_post()
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $moderator = User::factory()->moderator()->createOne();
        $post = Post::factory()->markdownPost()->createOne(['locked_at' => Carbon::now()]);

        $response = $this->actingAs($moderator, 'sanctum')->postJson(route('posts.unlock', ['id' => $post->id]))
            ->assertStatus(200);

        $this->assertFalse($post->refresh()->isLocked());
    }
}
