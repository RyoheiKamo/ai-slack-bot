<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackMessageService
{
    public function sendMessage(
        string $channel,
        string $text,
        ?string $threadTs = null
    ): void {
        $payload = [
            'channel' => $channel,
            'text' => $text,
        ];

        if ($threadTs !== null) {
            $payload['thread_ts'] = $threadTs;
        }

        $response = Http::withToken(config('services.slack.bot_token'))
            ->post(
                'https://slack.com/api/chat.postMessage',
                $payload
            );

        if (! $response->successful() || $response->json('ok') !== true) {
            Log::error('Slack message sending failed', [
                'status' => $response->status(),
                'error' => $response->json('error'),
                'body' => $response->json(),
            ]);

            return;
        }

        Log::info('Slack message sent', [
            'channel' => $channel,
            'ts' => $response->json('ts'),
        ]);
    }
}
