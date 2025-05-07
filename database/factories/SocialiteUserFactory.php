<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialiteUser>
 */
class SocialiteUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'github',
            'provider_user_id' => $this->faker->uuid,
            'provider_user_email' => $this->faker->email,
        ];
    }
}
