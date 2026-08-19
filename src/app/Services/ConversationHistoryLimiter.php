<?php

namespace App\Services;

class ConversationHistoryLimiter
{
    /**
     * @param array<int, array{role: string, content: string}> $messages
     * @return array<int, array{role: string, content: string}>
     */
    public function limit(array $messages): array
    {
        $result = [];
        $tokenCount = 0;
        $maxTokens = config('openai.max_history_tokens', 4000);

        foreach (array_reverse($messages) as $message) {
            $tokens = $this->estimateTokens(
                $message['content']
            );

            // 最新メッセージは必ず含める
            if ($result === []) {
                $result[] = $message;
                $tokenCount += $tokens;

                continue;
            }

            if ($tokenCount + $tokens > $maxTokens) {
                break;
            }

            $result[] = $message;
            $tokenCount += $tokens;
        }

        return array_reverse($result);
    }

    private function estimateTokens(string $text): int
    {
        return (int) ceil(
            mb_strlen($text) / 3
        );
    }
}
