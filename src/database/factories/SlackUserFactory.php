<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlackUser>
 */
class SlackUserFactory extends Factory
{
    public function definition(): array
    {
        $firstUsedAt = fake()
            ->dateTimeBetween('-30 days', '-1 day');

        $lastUsedAt = fake()
            ->dateTimeBetween($firstUsedAt, 'now');

        return [
            'slack_user_id' => 'U' . fake()->unique()->numerify('########'),
            'display_name' => fake()->userName(),
            'real_name' => fake()->name(),
            'first_used_at' => $firstUsedAt,
            'last_used_at' => $lastUsedAt,
        ];
    }
}
