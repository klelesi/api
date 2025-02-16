<?php

namespace Database\Factories;

use App\Models\Link;
use App\Models\Markdown;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
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
            'title' => $this->faker->sentence(),
            'number_of_comments' => 0,
            'author_id' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-3 months', '3 months'),
        ];
    }

    public function markdownPost()
    {
        return $this->state(function (array $attributes) {
            Markdown::create([
                'markdownable_id' => $attributes['id'],
                'markdownable_type' => Post::class,
                'html' => $this->faker->randomHtml,
                'markdown' => $this->faker->paragraphs(6, true),
            ]);

            return [
                'post_type' => Post::POST_TYPE_MARKDOWN,
            ];
        });
    }

    public function linkPost()
    {
        return $this->state(function (array $attributes) {
            Link::create([
                'linkable_id' => $attributes['id'],
                'linkable_type' => Link::class,
                'url' => $this->faker->url,
            ]);

            return [
                'post_type' => Post::POST_TYPE_LINK,
            ];
        });
    }
}
