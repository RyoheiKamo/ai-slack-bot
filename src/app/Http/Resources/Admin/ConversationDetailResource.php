<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'thread_ts' => $this->thread_ts,
            'message_count' => $this->messages->count(),
            'latest_message' => $this->latestMessage?->content,
            'started_at' => $this->firstMessage?->message_created_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'messages' => $this->messages->map(
                fn($message) => [
                    'id' => $message->id,
                    'message_id' => $message->message_id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'message_created_at' => $message->message_created_at,
                ]
            ),
        ];
    }
}
