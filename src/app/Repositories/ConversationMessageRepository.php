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
        string $createdAt,
        ?int $slackUserId = null,
    ): void {
        ConversationMessage::firstOrCreate(
            [
                'message_id' => $messageId,
            ],
            [
                'conversation_id' => $conversationId,
                'slack_user_id' => $slackUserId,
                'role' => $role,
                'content' => $content,
                'message_created_at' => $createdAt,
            ]
        );
    }
}
