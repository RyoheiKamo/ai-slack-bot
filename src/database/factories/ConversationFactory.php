<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'channel' => 'C' . fake()->numerify('########'),
            'thread_ts' => sprintf(
                '%d.%06d',
                fake()->numberBetween(1700000000, 1900000000),
                fake()->numberBetween(0, 999999),
            ),
        ];
    }
}
