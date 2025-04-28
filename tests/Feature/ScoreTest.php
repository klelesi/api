<?php


use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_storing_a_post_stores_a_score()
    {
        $user = User::factory()->create();

        $data = ['postType' => Post::POST_TYPE_MARKDOWN, 'title' => 'Example', 'markdown' => '#Hello'];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('posts.store'), $data)->assertStatus(201);
        $postId = $response->json('data.id');

        $this->assertDatabaseHas('scores', ['scorable_id' => $postId, 'score' => 1]);

        $this->assertDatabaseHas(UserInteraction::class, ['user_id' => $user->id,
            'type' => UserInteraction::TYPE_UPVOTE,
            'interactable_id' => $postId]);
    }

    public function test_storing_a_comment_stores_a_score()
    {
        $user = User::factory()->createOne();
        $post = Post::factory()->markdownPost()->createOne();

        $data = ['markdown' => '#Hello', 'postId' => $post->id];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(201);
        $commentId = $response->json('data.id');

        $this->assertDatabaseHas('scores', ['scorable_id' => $commentId, 'score' => 1]);

        $this->assertDatabaseHas(UserInteraction::class, ['user_id' => $user->id,
            'type' => UserInteraction::TYPE_UPVOTE,
            'interactable_id' => $commentId]);
    }

    public function test_storing_a_nested_comment_stores_a_score()
    {
        $user = User::factory()->createOne();
        $comment = Comment::factory()->createOne();

        $data = ['markdown' => '#Hello', 'postId' => $comment->commentable_id, 'parentId' => $comment->id];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(201);
        $commentId = $response->json('data.id');

        $this->assertDatabaseHas('scores', ['scorable_id' => $commentId, 'score' => 1]);

        $this->assertDatabaseHas(UserInteraction::class, ['user_id' => $user->id,
            'type' => UserInteraction::TYPE_UPVOTE,
            'interactable_id' => $commentId]);
    }
}
