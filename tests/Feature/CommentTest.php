<?php


use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_a_comment()
    {
        $user = User::factory()->createOne();
        $post = Post::factory()->markdownPost()->createOne();

        $data = ['markdown' => '#Hello', 'postId' => $post->id];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(201);
        $commentId = $response->json('data.id');

        $this->assertDatabaseHas('comments', ['commentable_id' => $post->id, 'author_id' => $user->id]);
        $this->assertDatabaseHas('markdowns', ['markdownable_id' => $commentId, 'html' => '<h1>Hello</h1>']);
    }

    public function test_storing_a_comment_increases_comment_count_on_post()
    {
        $user = User::factory()->createOne();
        $post = Post::factory()->markdownPost()->createOne();

        $data = ['markdown' => '#Hello', 'postId' => $post->id];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(201);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'number_of_comments' => 1]);

        $data['parentId'] = Comment::first()->id;
        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(201);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'number_of_comments' => 2]);
    }

    public function test_it_stores_a_nested_comment()
    {
        $user = User::factory()->createOne();
        $comment = Comment::factory()->createOne();

        $data = ['markdown' => '#Hello', 'postId' => $comment->commentable_id, 'parentId' => $comment->id];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(201);
        $commentId = $response->json('data.id');

        $this->assertDatabaseHas('comments', ['commentable_id' => $comment->commentable_id, 'author_id' => $user->id,
            'parent_id' => $comment->id]);
        $this->assertDatabaseHas('markdowns', ['markdownable_id' => $commentId, 'html' => '<h1>Hello</h1>']);
    }

    public function test_it_uses_the_nested_comments_post_id()
    {
        $user = User::factory()->createOne();
        $comment = Comment::factory()->createOne();
        $imposterPost = Post::factory()->markdownPost()->createOne();

        $data = ['markdown' => '#Hello', 'postId' => $imposterPost->id, 'parentId' => $comment->id];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(201);
        $commentId = $response->json('data.id');

        $this->assertDatabaseHas('comments', ['commentable_id' => $comment->commentable_id, 'author_id' => $user->id,
            'parent_id' => $comment->id]);
        $this->assertDatabaseHas('markdowns', ['markdownable_id' => $commentId, 'html' => '<h1>Hello</h1>']);
    }

    public function test_it_updates_a_comment()
    {
        $comment = Comment::factory()->createOne();

        $data = ['markdown' => '#Hello'];

        $response = $this->actingAs($comment->author, 'sanctum')->putJson(route('comments.update', ['id' =>
            $comment->id]), $data)
            ->assertStatus
            (200);
        $commentId = $response->json('data.id');

        $this->assertDatabaseHas('markdowns', ['markdownable_id' => $commentId, 'html' => '<h1>Hello</h1>']);
    }

    public function test_it_deletes_a_comment()
    {
        $comment = Comment::factory()->createOne();

        $response = $this->actingAs($comment->author, 'sanctum')->deleteJson(route('comments.delete', ['id' =>
            $comment->id]))->assertStatus(200);

        $this->assertSoftDeleted(Comment::class, ['id' => $comment->id]);
    }

    public function test_it_restores_a_comment()
    {
        $comment = Comment::factory()->createOne();

        $response = $this->actingAs($comment->author, 'sanctum')->postJson(route('comments.restore', ['id' =>
            $comment->id]))->assertStatus(200);

        $this->assertNotSoftDeleted(Comment::class, ['id' => $comment->id]);
    }

    public function test_only_the_author_can_update_a_comment()
    {
        $imposter = User::factory()->createOne();
        $comment = Comment::factory()->createOne();

        $data = ['markdown' => '#Hello'];

        $response = $this->actingAs($imposter, 'sanctum')->putJson(route('comments.update', ['id' =>
            $comment->id]), $data)->assertStatus(403);
    }

    public function test_only_the_author_can_delete_a_comment()
    {
        $imposter = User::factory()->createOne();
        $comment = Comment::factory()->createOne();

        $response = $this->actingAs($imposter, 'sanctum')->deleteJson(route('comments.update', ['id' =>
            $comment->id]))->assertStatus(403);
    }

    public function test_only_the_author_can_restore_a_comment()
    {
        $imposter = User::factory()->createOne();
        $comment = Comment::factory()->createOne(['deleted_at' => Carbon::now()]);

        $response = $this->actingAs($imposter, 'sanctum')->postJson(route('comments.restore', ['id' =>
            $comment->id]))->assertStatus(403);
    }

    public function test_it_prevents_storing_a_comment_to_a_locked_post()
    {
        $user = User::factory()->createOne();
        $post = Post::factory()->markdownPost()->createOne(['locked_at' => Carbon::now()]);

        $data = ['markdown' => '#Hello', 'postId' => $post->id];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(400);
    }

    public function test_it_prevents_updating_a_comment_to_a_locked_post()
    {
        $comment = Comment::factory()->createOne();
        $post = $comment->commentable;
        $post->locked_at = Carbon::now();
        $post->save();

        $data = ['markdown' => '#Hello'];

        $response = $this->actingAs($comment->author, 'sanctum')->putJson(route('comments.update', ['id' =>
            $comment->id]), $data)
            ->assertStatus
            (400);
        $commentId = $response->json('data.id');
    }

    public function test_it_prevents_storing_a_nested_comment_to_a_locked_comment()
    {
        $user = User::factory()->createOne();
        $comment = Comment::factory()->createOne(['locked_at' => Carbon::now()]);

        $data = ['markdown' => '#Hello', 'postId' => $comment->commentable_id, 'parentId' => $comment->id];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(400);
    }

    public function test_it_prevents_updating_a_locked_comment()
    {
        $comment = Comment::factory()->createOne(['locked_at' => Carbon::now()]);

        $data = ['markdown' => '#Hello'];

        $response = $this->actingAs($comment->author, 'sanctum')->putJson(route('comments.update', ['id' =>
            $comment->id]), $data)
            ->assertStatus
            (400);
    }

    public function test_it_prevents_storing_a_sub_nested_comment_to_a_locked_comment()
    {
        $user = User::factory()->createOne();
        $comment = Comment::factory()->createOne();
        $commentLocked = Comment::factory()->createOne(['commentable_id' => $comment->commentable_id, 'parent_id' =>
            $comment->id, 'locked_at' => Carbon::now()]);

        $data = ['markdown' => '#Hello', 'postId' => $comment->commentable_id, 'parentId' => $commentLocked->id];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(400);
    }

    public function test_it_prevents_updating_a_sub_nested_comment_to_a_locked_comment()
    {
        $comment = Comment::factory()->createOne();
        $commentLocked = Comment::factory()->createOne(['commentable_id' => $comment->commentable_id, 'parent_id' =>
            $comment->id, 'locked_at' => Carbon::now()]);
        $commentSub = Comment::factory()->createOne(['commentable_id' => $comment->commentable_id, 'parent_id' =>
            $commentLocked->id, 'locked_at' => null]);

        $data = ['markdown' => '#Hello', 'postId' => $comment->commentable_id, 'parentId' => $commentLocked->id];

        $response = $this->actingAs($commentSub->author, 'sanctum')->putJson(route('comments.update', ['id' =>
            $commentSub->id]), $data)
            ->assertStatus(400);
    }
}
