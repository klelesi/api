<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_a_report_for_a_post(): void
    {
        $post = Post::factory()->markdownPost()->createOne();

        $data = [
            'comment' => 'this is a comment',
            'postId' => $post->id,
        ];

        $response = $this->postJson(route('reports.store'), $data)->assertStatus(200);

        $this->assertDatabaseHas(Report::class, ['reportable_id' => $post->id, 'reportable_type' => Post::class]);
    }

    public function test_it_stores_a_report_for_a_comment(): void
    {
        $comment = Comment::factory()->createOne();

        $data = [
            'comment' => 'this is a comment',
            'commentId' => $comment->id,
        ];

        $response = $this->postJson(route('reports.store'), $data)->assertStatus(200);

        $this->assertDatabaseHas(Report::class, ['reportable_id' => $comment->id, 'reportable_type' => Comment::class]);
    }

    public function test_it_stores_a_user_if_they_are_logged_in(): void
    {
        $user = User::factory()->createOne();
        $post = Post::factory()->markdownPost()->createOne();

        $data = [
            'comment' => 'this is a comment',
            'postId' => $post->id,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('reports.store'), $data)->assertStatus(200);

        $this->assertDatabaseHas(Report::class, ['user_id' => $user->id]);
    }
}
