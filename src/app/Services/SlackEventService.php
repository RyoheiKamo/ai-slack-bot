<?php

namespace App\Services;

use App\Jobs\ProcessSlackMessageJob;
use Illuminate\Support\Facades\Log;

class SlackEventService
{
    public function __construct(
        private readonly SlackEventDeduplicationService $deduplicationService,
    ) {}

    public function handle(array $payload): void
    {
        $eventId = $payload['event_id'] ?? null;

        if (! is_string($eventId) || $eventId === '') {
            Log::warning('Slack event ID is missing');

            return;
        }

        $event = $payload['event'] ?? [];

        if (($event['type'] ?? null) !== 'app_mention') {
            return;
        }

        // Bot自身や他のBotによるイベントを除外
        if (isset($event['bot_id'])) {
            return;
        }

        $channel = $event['channel'] ?? null;
        $threadTs = $event['thread_ts'] ?? $event['ts'] ?? null;
        $text = $event['text'] ?? '';

        if (
            ! is_string($channel)
            || ! is_string($threadTs)
            || ! is_string($text)
        ) {
            Log::warning('Slack event missing required fields', [
                'event_id' => $eventId,
                'channel' => $channel,
                'thread_ts' => $threadTs,
            ]);

            return;
        }

        // 実際に処理するイベントに対して重複チェック
        if (! $this->deduplicationService->acquire($eventId)) {
            Log::info('Duplicate Slack event skipped', [
                'event_id' => $eventId,
            ]);

            return;
        }

        Log::info('Slack Event received', [
            'event_id' => $eventId,
            'type' => $event['type'],
            'channel' => $channel,
            'user' => $event['user'] ?? null,
        ]);

        ProcessSlackMessageJob::dispatch(
            $text,
            $channel,
            $threadTs,
            $eventId
        );
    }
}
