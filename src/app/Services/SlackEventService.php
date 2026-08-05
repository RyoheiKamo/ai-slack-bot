<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SlackEventService
{
    public function __construct(
        private readonly SlackMessageService $messageService,
        private readonly SlackEventDeduplicationService $deduplicationService,
        private readonly OpenAIService $openAIService
    ) {}

    public function handle(array $payload): void
    {
        $eventId = $payload['event_id'] ?? null;

        if (! is_string($eventId) || $eventId === '') {
            Log::warning('Slack event ID is missing');

            return;
        }

        if (! $this->deduplicationService->acquire($eventId)) {
            Log::info('Duplicate Slack event skipped', [
                'event_id' => $eventId,
            ]);

            return;
        }

        $event = $payload['event'] ?? [];

        Log::info('Slack Event received', [
            'event_id' => $payload['event_id'] ?? null,
            'type' => $event['type'] ?? null,
            'channel' => $event['channel'] ?? null,
            'user' => $event['user'] ?? null,
        ]);

        if (($event['type'] ?? null) !== 'app_mention') {
            return;
        }

        // Bot自身や他のBotによるイベントを除外
        if (isset($event['bot_id'])) {
            return;
        }

        $channel = $event['channel'] ?? null;
        $threadTs = $event['thread_ts'] ?? $event['ts'] ?? null;

        if (! is_string($channel) || ! is_string($threadTs)) {
            Log::warning('Slack event missing required fields', [
                'event_id' => $payload['event_id'] ?? null,
                'channel' => $channel,
                'thread_ts' => $threadTs,
            ]);

            return;
        }

        $text = $this->removeBotMention($event['text'] ?? '');

        try {
            $reply = $this->openAIService->generateReply($text);
        } catch (\Throwable $e) {
            Log::error('AI reply generation failed', [
                'event_id' => $eventId,
                'message' => $e->getMessage(),
            ]);

            $reply = '申し訳ありません。AIから回答を取得できませんでした。';
        }

        $this->messageService->sendMessage(
            $channel,
            $reply,
            $threadTs
        );
    }

    private function removeBotMention(string $text): string
    {
        return trim(
            preg_replace('/<@[A-Z0-9]+>/', '', $text) ?? $text
        );
    }
}
