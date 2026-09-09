<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'thread_ts' => $this->thread_ts,
            'message_count' => $this->messages_count,
            'latest_message' => $this->latestMessage?->content,
            'started_at' => $this->firstMessage?->message_created_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
