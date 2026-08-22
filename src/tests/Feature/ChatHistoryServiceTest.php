<?php

namespace Tests\Feature;

use App\Services\ChatHistoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatHistoryServiceTest extends TestCase
{
    private ChatHistoryService $service;

    private string $channel = 'C_TEST';

    private string $threadTs = '123.456';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-22 14:55:00');

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

        Carbon::setTestNow();

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

        $this->assertCount(1, $history);

        $this->assertTrue(
            Str::isUuid($history[0]['id'])
        );

        $this->assertSame(
            'user',
            $history[0]['role']
        );

        $this->assertSame(
            'PHPとは？',
            $history[0]['content']
        );

        $this->assertSame(
            now()->toIso8601String(),
            $history[0]['created_at']
        );
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

        $this->assertCount(2, $history);

        $this->assertSame(
            'user',
            $history[0]['role']
        );

        $this->assertSame(
            'PHPとは？',
            $history[0]['content']
        );

        $this->assertTrue(
            Str::isUuid($history[0]['id'])
        );

        $this->assertSame(
            now()->toIso8601String(),
            $history[0]['created_at']
        );

        $this->assertSame(
            'assistant',
            $history[1]['role']
        );

        $this->assertSame(
            'PHPはプログラミング言語です。',
            $history[1]['content']
        );

        $this->assertTrue(
            Str::isUuid($history[1]['id'])
        );

        $this->assertSame(
            now()->toIso8601String(),
            $history[1]['created_at']
        );

        $this->assertNotSame(
            $history[0]['id'],
            $history[1]['id']
        );
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

        foreach ($history as $message) {
            $this->assertTrue(
                Str::isUuid($message['id'])
            );

            $this->assertSame(
                now()->toIso8601String(),
                $message['created_at']
            );
        }
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

        $this->assertLessThanOrEqual(
            86400,
            $ttl
        );
    }
}
