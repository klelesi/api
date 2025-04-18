<?php


use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\UserInteraction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_unread_user_notifications()
    {
        $user = User::factory()->createOne();
        $notifications = \App\Models\Notification::factory([
            'user_id' => $user->id,
        ])->count(10)->create();

        $notifications = \App\Models\Notification::factory([
            'user_id' => $user->id,
            'read_at' => Carbon::now(),
        ])->count(10)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson(route('user.notifications'))->assertStatus(200);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_it_marks_a_notification_as_read()
    {
        $user = User::factory()->createOne();
        $notification = \App\Models\Notification::factory([
            'user_id' => $user->id,
        ])->createOne();

        $data = [
            'notificationId' => $notification->id,
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('user.notifications'), $data)->assertStatus(200);

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_it_stores_a_notification_when_a_comment_is_stored()
    {
        $user = User::factory()->createOne();
        $post = Post::factory()->markdownPost()->createOne();

        $data = ['markdown' => '#Hello', 'postId' => $post->id];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(201);

        $this->assertDatabaseHas(\App\Models\Notification::class, ['user_id' => $post->author_id, 'type' =>
            \App\Models\Notification::TYPE_NEW_COMMENT]);
    }

    public function test_it_stores_a_notification_when_a_nested_comment_is_stored()
    {
        $user = User::factory()->createOne();
        $comment = Comment::factory()->createOne();

        $data = ['markdown' => '#Hello', 'postId' => $comment->commentable_id, 'parentId' => $comment->id];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(201);

        $this->assertDatabaseHas(\App\Models\Notification::class, ['user_id' => $comment->author_id, 'type' =>
            \App\Models\Notification::TYPE_NEW_COMMENT]);
    }

    public function test_it_doesnt_store_a_notification_when_the_user_is_the_same()
    {
        $user = User::factory()->createOne();
        $post = Post::factory()->markdownPost()->createOne(['author_id' => $user->id]);

        $data = ['markdown' => '#Hello', 'postId' => $post->id];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('comments.store'), $data)->assertStatus(201);

        $this->assertDatabaseCount(\App\Models\Notification::class, 0);
    }
}
