<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Markdown;
use App\Models\Post;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
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
            'commentable_id' => Post::factory()->markdownPost(),
            'commentable_type' => Post::class,
            'author_id' => User::factory(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Comment $comment) {
            $comment->markdown()->save(Markdown::create([
                'markdownable_id' => $comment->id,
                'markdownable_type' => Post::class,
                'html' => $this->faker->randomHtml,
                'markdown' => $this->faker->paragraphs(6, true),
            ]));
        });
    }
}
