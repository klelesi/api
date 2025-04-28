<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Score;
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

    public function test_it_stores_a_post_upvote_interaction(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->markdownPost()->createOne();

        $data = [
            'type' => UserInteraction::TYPE_UPVOTE,
            'postId' => $post->id,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data)->assertStatus(200);
        //  To verify only 1 interaction is stored
        $response = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data)->assertStatus(200);

        $this->assertDatabaseHas(UserInteraction::class, ['user_id' => $user->id, 'type' =>
            UserInteraction::TYPE_UPVOTE, 'interactable_id' => $post->id, 'interactable_type' => Post::class]);
        $this->assertDatabaseCount(UserInteraction::class, 1);
    }

    public function test_it_stores_a_comment_upvote_interaction(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->createOne();

        $data = [
            'type' => UserInteraction::TYPE_UPVOTE,
            'commentId' => $comment->id,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data)->assertStatus(200);
        //  To verify only 1 interaction is stored
        $response = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data)->assertStatus(200);

        $this->assertDatabaseHas(UserInteraction::class, ['user_id' => $user->id, 'type' =>
            UserInteraction::TYPE_UPVOTE, 'interactable_id' => $comment->id, 'interactable_type' => Comment::class]);
        $this->assertDatabaseCount(UserInteraction::class, 1);
    }

    public function test_it_stores_a_post_downvote_interaction(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->markdownPost()->createOne();

        $data = [
            'type' => UserInteraction::TYPE_DOWNVOTE,
            'postId' => $post->id,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data)->assertStatus(200);
        //  To verify only 1 interaction is stored
        $response = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data)->assertStatus(200);

        $this->assertDatabaseHas(UserInteraction::class, ['user_id' => $user->id, 'type' =>
            UserInteraction::TYPE_DOWNVOTE, 'interactable_id' => $post->id, 'interactable_type' => Post::class]);
        $this->assertDatabaseCount(UserInteraction::class, 1);
    }

    public function test_it_stores_a_comment_downvote_interaction(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->createOne();

        $data = [
            'type' => UserInteraction::TYPE_DOWNVOTE,
            'commentId' => $comment->id,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data)->assertStatus(200);
        //  To verify only 1 interaction is stored
        $response = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data)->assertStatus(200);

        $this->assertDatabaseHas(UserInteraction::class, ['user_id' => $user->id, 'type' =>
            UserInteraction::TYPE_DOWNVOTE, 'interactable_id' => $comment->id, 'interactable_type' => Comment::class]);
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

        $interaction1 = UserInteraction::factory()->postViewInteraction()->createOne(['user_id' => $user->id, 'interactable_id' =>
            $post1->id]);

        $interaction2 = UserInteraction::factory()->postViewInteraction()->createOne(['user_id' => $user->id, 'interactable_id' =>
            $post2->id]);

        $interaction3 = UserInteraction::factory()->postUpvoteInteraction()->createOne(['user_id' => $user->id, 'interactable_id' =>
            $post3->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson(route('interactions.posts', [
            'postIds' => [$post1->id, $post2->id, $post3->id],
        ]))->assertStatus(200);

        $json = $response->json('data');

        $this->assertSame($interaction1->interactable_id, $json[0]['interactableId']);
        $this->assertSame($interaction2->interactable_id, $json[1]['interactableId']);
        $this->assertSame($interaction3->interactable_id, $json[2]['interactableId']);
    }

    public function test_upvoting_downvoting_updates_score(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->createOne();
        $postId = $comment->commentable_id;

        //  Add upvotes
        $data1 = [
            'type' => UserInteraction::TYPE_UPVOTE,
            'postId' => $postId,
        ];

        $data2 = [
            'type' => UserInteraction::TYPE_UPVOTE,
            'commentId' => $comment->id,
        ];

        $response1 = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data1)->assertStatus(200);
        $response2 = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data2)->assertStatus(200);

        $this->assertDatabaseHas(Score::class, ['scorable_id' => $comment->id, 'score' => 1]);
        $this->assertDatabaseHas(Score::class, ['scorable_id' => $postId, 'score' => 1]);

        $this->assertDatabaseCount(UserInteraction::class, 2);

        //  Remove upvotes
        $response3 = $this->actingAs($user, 'sanctum')->postJson(route('interactions.delete'), $data1)->assertStatus
        (200);
        $response4 = $this->actingAs($user, 'sanctum')->postJson(route('interactions.delete'), $data2)->assertStatus
        (200);

        $this->assertDatabaseHas(Score::class, ['scorable_id' => $comment->id, 'score' => 0]);
        $this->assertDatabaseHas(Score::class, ['scorable_id' => $postId, 'score' => 0]);

        $this->assertDatabaseCount(UserInteraction::class, 0);

        //  Add downvotes
        $data5 = [
            'type' => UserInteraction::TYPE_DOWNVOTE,
            'postId' => $postId,
        ];

        $data6 = [
            'type' => UserInteraction::TYPE_DOWNVOTE,
            'commentId' => $comment->id,
        ];

        $response5 = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data5)->assertStatus
        (200);
        $response6 = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data6)->assertStatus
        (200);

        $this->assertDatabaseHas(Score::class, ['scorable_id' => $comment->id, 'score' => -1]);
        $this->assertDatabaseHas(Score::class, ['scorable_id' => $postId, 'score' => -1]);

        $this->assertDatabaseCount(UserInteraction::class, 2);

        //  Test going from downvote to upvote directly
        $response1 = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data1)->assertStatus(200);
        $response2 = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data2)->assertStatus
        (200);

        $this->assertDatabaseHas(Score::class, ['scorable_id' => $comment->id, 'score' => 1]);
        $this->assertDatabaseHas(Score::class, ['scorable_id' => $postId, 'score' => 1]);

        $this->assertDatabaseCount(UserInteraction::class, 2);
    }

    public function test_multiple_upvoting_does_not_increase_the_score(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->markdownPost()->createOne();

        //  Add upvotes
        $data1 = [
            'type' => UserInteraction::TYPE_UPVOTE,
            'postId' => $post->id,
        ];

        $response1 = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data1)->assertStatus(200);
        $response1 = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data1)->assertStatus(200);
        $response1 = $this->actingAs($user, 'sanctum')->postJson(route('interactions.store'), $data1)->assertStatus(200);

        $this->assertDatabaseHas(Score::class, ['scorable_id' => $post->id, 'score' => 1]);
    }
}
