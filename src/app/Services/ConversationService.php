<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class ConversationService
{
    public function __construct(
        private readonly ChatHistoryService $chatHistoryService,
        private readonly ConversationHistoryLimiter $historyLimiter,
        private readonly OpenAIService $openAIService,
        private readonly SlackChannelService $slackChannelService,
        private readonly SlackMessageService $slackMessageService,
        private readonly SlackUserService $slackUserService,
    ) {}

    public function process(
        string $text,
        string $channel,
        string $threadTs,
        string $eventId,
        string $slackUserId,
    ): void {
        try {
            $message = $this->removeBotMention($text);

            if ($message === '/reset') {
                $this->chatHistoryService->clearHistory(
                    $channel,
                    $threadTs
                );

                $this->slackMessageService->sendMessage(
                    $channel,
                    'このスレッドの会話履歴をリセットしました。',
                    $threadTs
                );

                return;
            }

            $slackUser = $this->slackUserService->findOrCreate(
                $slackUserId
            );

            $this->slackChannelService->findOrCreate(
                $channel
            );

            $this->chatHistoryService->addUserMessage(
                $channel,
                $threadTs,
                $message,
                $slackUser->id
            );

            $history = $this->chatHistoryService->getHistory(
                $channel,
                $threadTs
            );

            $history = $this->historyLimiter->limit(
                $history
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
