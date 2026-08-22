<?php

namespace App\Repositories;

use App\Models\ConversationMessage;

class ConversationMessageRepository
{
    public function createIfNotExists(
        int $conversationId,
        string $messageId,
        string $role,
        string $content,
        string $messageCreatedAt
    ): ConversationMessage {
        return ConversationMessage::firstOrCreate(
            [
                'message_id' => $messageId,
            ],
            [
                'conversation_id' => $conversationId,
                'role' => $role,
                'content' => $content,
                'message_created_at' => $messageCreatedAt,
            ]
        );
    }
}
