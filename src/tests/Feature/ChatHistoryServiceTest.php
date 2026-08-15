<?php

namespace Tests\Feature;

use App\Services\ChatHistoryService;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ChatHistoryServiceTest extends TestCase
{
    private ChatHistoryService $service;

    private string $channel = 'C_TEST';

    private string $threadTs = '123.456';

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ChatHistoryService::class);

        $this->service->clearHistory(
            $this->channel,
            $this->threadTs
        );
    }

    protected function tearDown(): void
    {
        $this->service->clearHistory(
            $this->channel,
            $this->threadTs
        );

        parent::tearDown();
    }

    public function test_user_message_can_be_saved_and_retrieved(): void
    {
        $this->service->addUserMessage(
            $this->channel,
            $this->threadTs,
            'PHPとは？'
        );

        $history = $this->service->getHistory(
            $this->channel,
            $this->threadTs
        );

        $this->assertSame([
            [
                'role' => 'user',
                'content' => 'PHPとは？',
            ],
        ], $history);
    }

    public function test_messages_are_retrieved_in_order(): void
    {
        $this->service->addUserMessage(
            $this->channel,
            $this->threadTs,
            'PHPとは？'
        );

        $this->service->addAssistantMessage(
            $this->channel,
            $this->threadTs,
            'PHPはプログラミング言語です。'
        );

        $history = $this->service->getHistory(
            $this->channel,
            $this->threadTs
        );

        $this->assertSame([
            [
                'role' => 'user',
                'content' => 'PHPとは？',
            ],
            [
                'role' => 'assistant',
                'content' => 'PHPはプログラミング言語です。',
            ],
        ], $history);
    }

    public function test_history_can_be_cleared(): void
    {
        $this->service->addUserMessage(
            $this->channel,
            $this->threadTs,
            'テスト'
        );

        $this->service->clearHistory(
            $this->channel,
            $this->threadTs
        );

        $history = $this->service->getHistory(
            $this->channel,
            $this->threadTs
        );

        $this->assertSame([], $history);
    }

    public function test_only_latest_twenty_messages_are_kept(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->service->addUserMessage(
                $this->channel,
                $this->threadTs,
                "message {$i}"
            );
        }

        $history = $this->service->getHistory(
            $this->channel,
            $this->threadTs
        );

        $this->assertCount(20, $history);

        $this->assertSame(
            'message 6',
            $history[0]['content']
        );

        $this->assertSame(
            'message 25',
            $history[19]['content']
        );
    }

    public function test_ttl_is_set(): void
    {
        $this->service->addUserMessage(
            $this->channel,
            $this->threadTs,
            'TTL test'
        );

        $ttl = $this->service->getTtl(
            $this->channel,
            $this->threadTs
        );

        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(86400, $ttl);
    }
}
