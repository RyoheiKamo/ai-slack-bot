<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ConversationMessageFactory extends Factory
{
    protected $model = ConversationMessage::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'slack_user_id' => null,
            'message_id' => (string) Str::uuid(),
            'role' => 'user',
            'content' => fake()->sentence(),
            'message_created_at' => now(),
        ];
    }
}
