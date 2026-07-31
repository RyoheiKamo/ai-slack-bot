<?php

namespace App\Services;

use App\Services\SlackMessageService;
use App\Services\SlackSignatureService;
use Illuminate\Http\Request;

class SlackService
{
    public function __construct(
        private readonly SlackMessageService $messageService,
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

        $this->messageService->sendMessage(
            $channel,
            "受信しました: {$text}",
            $threadTs
        );

        return response()->json(['ok' => true]);
    }

    private function removeBotMention(string $text): string
    {
        return trim(
            preg_replace('/<@[A-Z0-9]+>/', '', $text) ?? $text
        );
    }
}
