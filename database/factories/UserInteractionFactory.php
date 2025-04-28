<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserInteraction>
 */
class UserInteractionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::orderedUuid()->toString(),
            'user_id' => User::factory(),
            'type' => UserInteraction::TYPE_VIEW,
            'interactable_type' => Post::class,
            'interactable_id' => Post::factory()->markdownPost(),
        ];
    }

    public function postViewInteraction()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => UserInteraction::TYPE_VIEW,
                'interactable_type' => Post::class,
                'interactable_id' => Post::factory()->markdownPost(),
            ];
        });
    }

    public function postUpvoteInteraction()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => UserInteraction::TYPE_UPVOTE,
                'interactable_type' => Post::class,
                'interactable_id' => Post::factory()->markdownPost(),
            ];
        });
    }
}
