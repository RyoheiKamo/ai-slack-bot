<?php

namespace App\Services;

use App\Repositories\ConversationMessageRepository;
use App\Repositories\ConversationRepository;
use Illuminate\Support\Facades\DB;

class ConversationPersistenceService
{
    public function __construct(
        private readonly ChatHistoryService $chatHistoryService,
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationMessageRepository $conversationMessageRepository,
    ) {}

    public function persist(
        string $channel,
        string $threadTs
    ): void {
        $history = $this->chatHistoryService->getHistory(
            $channel,
            $threadTs
        );

        if ($history === []) {
            return;
        }

        DB::transaction(function () use (
            $channel,
            $threadTs,
            $history
        ): void {
            $conversation = $this->conversationRepository
                ->findOrCreate(
                    $channel,
                    $threadTs
                );

            foreach ($history as $message) {
                $this->conversationMessageRepository
                    ->createIfNotExists(
                        $conversation->id,
                        $message['id'],
                        $message['role'],
                        $message['content'],
                        $message['created_at']
                    );
            }
        });

        $this->chatHistoryService->clearHistory(
            $channel,
            $threadTs
        );
    }
}
