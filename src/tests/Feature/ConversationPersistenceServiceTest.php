<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Repositories\ConversationMessageRepository;
use App\Services\ChatHistoryService;
use App\Services\ConversationPersistenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ConversationPersistenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConversationPersistenceService $service;

    private ChatHistoryService $chatHistoryService;

    private string $channel = 'C_TEST';

    private string $threadTs = '123.456';

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(
            ConversationPersistenceService::class
        );

        $this->chatHistoryService = app(
            ChatHistoryService::class
        );

        $this->chatHistoryService->clearHistory(
            $this->channel,
            $this->threadTs
        );
    }

    protected function tearDown(): void
    {
        $this->chatHistoryService->clearHistory(
            $this->channel,
            $this->threadTs
        );

        parent::tearDown();
    }

    public function test_redis_history_can_be_persisted_to_database(): void
    {
        $this->chatHistoryService->addUserMessage(
            $this->channel,
            $this->threadTs,
            'PHPとは？'
        );

        $this->chatHistoryService->addAssistantMessage(
            $this->channel,
            $this->threadTs,
            'PHPはプログラミング言語です。'
        );

        $this->service->persist(
            $this->channel,
            $this->threadTs
        );

        $this->assertDatabaseCount(
            'conversations',
            1
        );

        $this->assertDatabaseHas(
            'conversations',
            [
                'channel' => $this->channel,
                'thread_ts' => $this->threadTs,
            ]
        );

        $conversation = Conversation::query()
            ->where('channel', $this->channel)
            ->where('thread_ts', $this->threadTs)
            ->firstOrFail();

        $this->assertDatabaseCount(
            'conversation_messages',
            2
        );

        $this->assertDatabaseHas(
            'conversation_messages',
            [
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => 'PHPとは？',
            ]
        );

        $this->assertDatabaseHas(
            'conversation_messages',
            [
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => 'PHPはプログラミング言語です。',
            ]
        );
    }

    public function test_redis_history_is_cleared_after_persistence(): void
    {
        $this->chatHistoryService->addUserMessage(
            $this->channel,
            $this->threadTs,
            'Redis削除テスト'
        );

        $this->assertCount(
            1,
            $this->chatHistoryService->getHistory(
                $this->channel,
                $this->threadTs
            )
        );

        $this->service->persist(
            $this->channel,
            $this->threadTs
        );

        $history = $this->chatHistoryService->getHistory(
            $this->channel,
            $this->threadTs
        );

        $this->assertSame([], $history);

        $this->assertDatabaseHas(
            'conversation_messages',
            [
                'role' => 'user',
                'content' => 'Redis削除テスト',
            ]
        );
    }

    public function test_same_messages_are_not_saved_twice(): void
    {
        $messageId = '11111111-1111-4111-8111-111111111111';

        $this->pushMessageDirectlyToRedis(
            $messageId,
            'user',
            '二重登録テスト',
            '2026-08-22T16:00:00+09:00'
        );

        // 1回目
        $this->service->persist(
            $this->channel,
            $this->threadTs
        );

        $this->assertDatabaseCount(
            'conversations',
            1
        );

        $this->assertDatabaseCount(
            'conversation_messages',
            1
        );

        /*
         * Queue retryを想定して、
         * 同じmessage_idを持つデータをRedisへ再投入する。
         */
        $this->pushMessageDirectlyToRedis(
            $messageId,
            'user',
            '二重登録テスト',
            '2026-08-22T16:00:00+09:00'
        );

        // 2回目
        $this->service->persist(
            $this->channel,
            $this->threadTs
        );

        $this->assertDatabaseCount(
            'conversations',
            1
        );

        $this->assertDatabaseCount(
            'conversation_messages',
            1
        );

        $this->assertDatabaseHas(
            'conversation_messages',
            [
                'message_id' => $messageId,
                'role' => 'user',
                'content' => '二重登録テスト',
            ]
        );
    }

    private function pushMessageDirectlyToRedis(
        string $id,
        string $role,
        string $content,
        string $createdAt
    ): void {
        $key = sprintf(
            'slack:chat:%s:%s',
            $this->channel,
            $this->threadTs
        );

        $value = json_encode(
            [
                'id' => $id,
                'role' => $role,
                'content' => $content,
                'created_at' => $createdAt,
            ],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        Redis::connection()->rpush(
            $key,
            $value
        );
    }

    public function test_redis_history_remains_when_database_persistence_fails(): void
    {
        $this->chatHistoryService->addUserMessage(
            $this->channel,
            $this->threadTs,
            'DB保存失敗テスト'
        );

        $historyBefore = $this->chatHistoryService->getHistory(
            $this->channel,
            $this->threadTs
        );

        $this->assertCount(1, $historyBefore);

        $messageRepository = Mockery::mock(
            ConversationMessageRepository::class
        );

        $messageRepository
            ->shouldReceive('createIfNotExists')
            ->once()
            ->andThrow(
                new RuntimeException(
                    'Persistence failure test'
                )
            );

        $this->app->instance(
            ConversationMessageRepository::class,
            $messageRepository
        );

        $service = app(
            ConversationPersistenceService::class
        );

        try {
            $service->persist(
                $this->channel,
                $this->threadTs
            );

            $this->fail(
                'RuntimeException was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Persistence failure test',
                $exception->getMessage()
            );
        }

        // Transaction内で作成したConversationもrollbackされること
        $this->assertDatabaseCount(
            'conversations',
            0
        );

        $this->assertDatabaseCount(
            'conversation_messages',
            0
        );

        // DB保存に失敗したためRedis履歴は削除されないこと
        $historyAfter = $this->chatHistoryService->getHistory(
            $this->channel,
            $this->threadTs
        );

        $this->assertCount(
            1,
            $historyAfter
        );

        $this->assertSame(
            $historyBefore[0]['id'],
            $historyAfter[0]['id']
        );

        $this->assertSame(
            'DB保存失敗テスト',
            $historyAfter[0]['content']
        );
    }
}
