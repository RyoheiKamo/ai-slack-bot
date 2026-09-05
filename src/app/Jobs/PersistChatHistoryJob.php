<?php

namespace App\Jobs;

use App\Services\ConversationPersistenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class PersistChatHistoryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [5, 15, 30];

    public function __construct(
        private readonly string $channel,
        private readonly string $threadTs,
    ) {}

    public function handle(
        ConversationPersistenceService $conversationPersistenceService
    ): void {
        Log::info('PersistChatHistoryJob started', [
            'channel' => $this->channel,
            'thread_ts' => $this->threadTs,
        ]);

        $conversationPersistenceService->persist(
            $this->channel,
            $this->threadTs
        );

        Log::info('PersistChatHistoryJob completed', [
            'channel' => $this->channel,
            'thread_ts' => $this->threadTs,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('PersistChatHistoryJob failed', [
            'channel' => $this->channel,
            'thread_ts' => $this->threadTs,
            'message' => $exception->getMessage(),
        ]);
    }
}
