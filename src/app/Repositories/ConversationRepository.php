<?php

namespace App\Repositories;

use App\Models\Conversation;

class ConversationRepository
{
    public function findOrCreate(
        string $channel,
        string $threadTs
    ): Conversation {
        return Conversation::firstOrCreate([
            'channel' => $channel,
            'thread_ts' => $threadTs,
        ]);
    }
}
