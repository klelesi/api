<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => Notification::TYPE_NEW_COMMENT,
            'post_id' => Post::factory()->markdownPost(),
            'target_id' => Comment::factory(),
        ];
    }
}
