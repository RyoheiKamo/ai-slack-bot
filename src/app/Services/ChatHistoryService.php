<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;

class ChatHistoryService
{
    private const KEY_PREFIX = 'slack:chat';

    private const MAX_MESSAGES = 20;

    private const TTL_SECONDS = 86400;

    /**
     * 会話履歴を取得する。
     *
     * @return array<int, array{
     *     id: string,
     *     role: string,
     *     content: string,
     *     created_at: string
     * }>
     *
     * @throws JsonException
     */
    public function getHistory(
        string $channel,
        string $threadTs
    ): array {
        $key = $this->createKey($channel, $threadTs);

        $messages = Redis::connection()->lrange($key, 0, -1);

        $history = [];

        foreach ($messages as $message) {
            $decoded = json_decode(
                $message,
                true,
                flags: JSON_THROW_ON_ERROR
            );

            if (
                ! is_array($decoded)
                || ! isset(
                    $decoded['id'],
                    $decoded['role'],
                    $decoded['content'],
                    $decoded['created_at']
                )
                || ! is_string($decoded['id'])
                || ! is_string($decoded['role'])
                || ! is_string($decoded['content'])
                || ! is_string($decoded['created_at'])
            ) {
                continue;
            }

            $history[] = [
                'id' => $decoded['id'],
                'role' => $decoded['role'],
                'content' => $decoded['content'],
                'created_at' => $decoded['created_at'],
            ];
        }

        return $history;
    }

    /**
     * ユーザーのメッセージを追加する。
     *
     * @throws JsonException
     */
    public function addUserMessage(
        string $channel,
        string $threadTs,
        string $message
    ): void {
        $this->addMessage(
            $channel,
            $threadTs,
            'user',
            $message
        );
    }

    /**
     * AIの回答を追加する。
     *
     * @throws JsonException
     */
    public function addAssistantMessage(
        string $channel,
        string $threadTs,
        string $message
    ): void {
        $this->addMessage(
            $channel,
            $threadTs,
            'assistant',
            $message
        );
    }

    /**
     * 会話履歴を削除する。
     */
    public function clearHistory(
        string $channel,
        string $threadTs
    ): void {
        Redis::connection()->del(
            $this->createKey($channel, $threadTs)
        );
    }

    /**
     * 会話履歴の有効期限を取得する。
     */
    public function getTtl(
        string $channel,
        string $threadTs
    ): int {
        return Redis::connection()->ttl(
            $this->createKey($channel, $threadTs)
        );
    }

    /**
     * メッセージをRedisへ追加する。
     *
     * @throws JsonException
     */
    private function addMessage(
        string $channel,
        string $threadTs,
        string $role,
        string $message
    ): void {
        $message = trim($message);

        if ($message === '') {
            throw new InvalidArgumentException(
                '保存するメッセージが空です。'
            );
        }

        $key = $this->createKey($channel, $threadTs);

        $value = json_encode(
            [
                'id' => (string) Str::uuid(),
                'role' => $role,
                'content' => $message,
                'created_at' => now()->toIso8601String(),
            ],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $redis = Redis::connection();

        // 末尾へ追加
        $redis->rpush($key, $value);

        // 最新20件だけ残す
        $redis->ltrim($key, -self::MAX_MESSAGES, -1);

        // 最終更新から24時間後に削除
        $redis->expire($key, self::TTL_SECONDS);
    }

    /**
     * Redisキーを生成する。
     */
    private function createKey(
        string $channel,
        string $threadTs
    ): string {
        return sprintf(
            '%s:%s:%s',
            self::KEY_PREFIX,
            $channel,
            $threadTs
        );
    }
}
