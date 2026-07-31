<?php

namespace App\Services;

use App\Services\SlackSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackService
{
    public function __construct(
        private readonly SlackSignatureService $signatureService
    ) {}

    public function handleEvent(Request $request)
    {
        if (! $this->signatureService->verify(
            $request->header('X-Slack-Request-Timestamp'),
            $request->header('X-Slack-Signature'),
            $request->getContent()
        )) {
            abort(401);
        }

        $payload = $request->all();

        Log::info('Slack Event', $payload);

        if (($payload['type'] ?? null) === 'url_verification') {
            return response($payload['challenge'], 200);
        }

        $event = $payload['event'] ?? [];

        if (($event['type'] ?? null) !== 'app_mention') {
            return response()->json(['ok' => true]);
        }

        if (isset($event['bot_id'])) {
            return response()->json(['ok' => true]);
        }

        $channel = $event['channel'] ?? null;
        $threadTs = $event['thread_ts'] ?? $event['ts'] ?? null;

        if (!$channel || !$threadTs) {
            return response()->json(['ok' => true]);
        }

        $text = $this->removeBotMention($event['text'] ?? '');

        $response = Http::withToken(config('services.slack.bot_token'))
            ->post('https://slack.com/api/chat.postMessage', [
                'channel' => $channel,
                'thread_ts' => $threadTs,
                'text' => "受信しました: {$text}",
            ]);

        Log::info('Slack API response', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return response()->json(['ok' => true]);
    }

    private function removeBotMention(string $text): string
    {
        return trim(
            preg_replace('/<@[A-Z0-9]+>/', '', $text) ?? $text
        );
    }
}
