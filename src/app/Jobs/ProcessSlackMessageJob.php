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

    public array $backoff = [5, 15, 30];

    public function __construct(
        public readonly string $text,
        public readonly string $channel,
        public readonly string $threadTs,
        public readonly string $eventId,
        public readonly string $slackUserId
    ) {}

    public function handle(ConversationService $conversationService): void
    {
        Log::info('ProcessSlackMessageJob started', [
            'event_id' => $this->eventId,
            'channel' => $this->channel,
            'thread_ts' => $this->threadTs,
            'slack_user_id' => $this->slackUserId,
        ]);

        $conversationService->process(
            $this->text,
            $this->channel,
            $this->threadTs,
            $this->eventId,
            $this->slackUserId
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProcessSlackMessageJob failed', [
            'event_id' => $this->eventId,
            'channel' => $this->channel,
            'thread_ts' => $this->threadTs,
            'slack_user_id' => $this->slackUserId,
            'message' => $exception->getMessage(),
        ]);
    }
}
