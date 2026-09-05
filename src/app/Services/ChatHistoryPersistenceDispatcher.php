<?php

namespace App\Services;

use App\Jobs\PersistChatHistoryJob;

class ChatHistoryPersistenceDispatcher
{
    public function __construct(
        private readonly ChatHistoryService $chatHistoryService,
    ) {}

    public function dispatchInactiveConversations(int $inactiveMinutes): int
    {
        $conversations = $this->chatHistoryService
            ->getInactiveConversations(
                $inactiveMinutes
            );

        foreach ($conversations as $conversation) {
            PersistChatHistoryJob::dispatch(
                $conversation['channel'],
                $conversation['thread_ts']
            );
        }

        return count($conversations);
    }
}
