<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SlackEventService
{
    public function __construct(
        private readonly SlackMessageService $messageService
    ) {}

    public function handle(array $payload): void
    {
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

        $this->messageService->sendMessage(
            $channel,
            "受信しました: {$text}",
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
