<?php

namespace App\Jobs;

use App\Services\ConversationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessSlackMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        private readonly string $text,
        private readonly string $channel,
        private readonly string $threadTs,
        private readonly string $eventId
    ) {}

    public function handle(
        ConversationService $conversationService
    ): void {
        Log::info('ProcessSlackMessageJob started', [
            'event_id' => $this->eventId,
            'channel' => $this->channel,
            'thread_ts' => $this->threadTs,
        ]);

        $conversationService->process(
            $this->text,
            $this->channel,
            $this->threadTs,
            $this->eventId
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProcessSlackMessageJob failed', [
            'event_id' => $this->eventId,
            'channel' => $this->channel,
            'thread_ts' => $this->threadTs,
            'message' => $exception->getMessage(),
        ]);
    }
}
