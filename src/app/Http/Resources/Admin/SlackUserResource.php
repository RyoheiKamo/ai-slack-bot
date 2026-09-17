<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlackUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slack_user_id' => $this->slack_user_id,
            'display_name' => $this->display_name,
            'real_name' => $this->real_name,
            'first_used_at' => $this->first_used_at,
            'last_used_at' => $this->last_used_at,
            'message_count' => $this->messages_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
