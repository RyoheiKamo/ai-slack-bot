<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class ConversationService
{
    public function __construct(
        private readonly ChatHistoryService $chatHistoryService,
        private readonly OpenAIService $openAIService,
        private readonly SlackMessageService $slackMessageService
    ) {}

    public function process(
        string $text,
        string $channel,
        string $threadTs,
        string $eventId
    ): void {
        try {
            $this->chatHistoryService->addUserMessage(
                $channel,
                $threadTs,
                $this->removeBotMention($text)
            );

            $history = $this->chatHistoryService->getHistory(
                $channel,
                $threadTs
            );

            $reply = $this->openAIService->generateReply(
                $history
            );

            $this->chatHistoryService->addAssistantMessage(
                $channel,
                $threadTs,
                $reply
            );

            $this->slackMessageService->sendMessage(
                $channel,
                $reply,
                $threadTs
            );
        } catch (RuntimeException $e) {
            Log::warning('Conversation processing failed', [
                'event_id' => $eventId,
                'message' => $e->getMessage(),
            ]);

            $this->slackMessageService->sendMessage(
                $channel,
                $e->getMessage(),
                $threadTs
            );
        }
    }

    private function removeBotMention(string $text): string
    {
        return trim(
            preg_replace('/<@[A-Z0-9]+>/', '', $text) ?? $text
        );
    }
}
