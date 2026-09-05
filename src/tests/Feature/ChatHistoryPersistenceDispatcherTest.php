<?php

namespace Tests\Feature;

use App\Jobs\PersistChatHistoryJob;
use App\Services\ChatHistoryPersistenceDispatcher;
use App\Services\ChatHistoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ChatHistoryPersistenceDispatcherTest extends TestCase
{
    private ChatHistoryService $chatHistoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chatHistoryService = app(
            ChatHistoryService::class
        );

        Redis::connection()->del(
            'slack:chat:updated'
        );

        $this->chatHistoryService->clearHistory(
            'C_TEST',
            '123.456'
        );
    }

    protected function tearDown(): void
    {
        $this->chatHistoryService->clearHistory(
            'C_TEST',
            '123.456'
        );

        Redis::connection()->del(
            'slack:chat:updated'
        );

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_inactive_conversation_job_is_dispatched(): void
    {
        Queue::fake();

        Carbon::setTestNow('2026-09-05 10:00:00');

        $this->chatHistoryService->addUserMessage(
            'C_TEST',
            '123.456',
            'テスト'
        );

        Carbon::setTestNow('2026-09-05 10:31:00');

        $dispatcher = app(
            ChatHistoryPersistenceDispatcher::class
        );

        $count = $dispatcher
            ->dispatchInactiveConversations(30);

        $this->assertSame(1, $count);

        Queue::assertPushed(
            PersistChatHistoryJob::class,
            1
        );
    }

    public function test_active_conversation_job_is_not_dispatched(): void
    {
        Queue::fake();

        Carbon::setTestNow('2026-09-05 10:00:00');

        $this->chatHistoryService->addUserMessage(
            'C_TEST',
            '123.456',
            'テスト'
        );

        Carbon::setTestNow('2026-09-05 10:29:00');

        $dispatcher = app(
            ChatHistoryPersistenceDispatcher::class
        );

        $count = $dispatcher
            ->dispatchInactiveConversations(30);

        $this->assertSame(0, $count);

        Queue::assertNothingPushed();
    }
}
