<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlackChannel>
 */
class SlackChannelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slack_channel_id' => fake()->unique()->bothify('C########'),
            'name' => fake()->word(),
            'first_used_at' => now(),
            'last_used_at' => now(),
        ];
    }
}
