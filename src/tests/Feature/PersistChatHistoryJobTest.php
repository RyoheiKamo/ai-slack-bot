<?php

namespace Tests\Feature;

use App\Jobs\PersistChatHistoryJob;
use App\Models\Conversation;
use App\Services\ChatHistoryService;
use App\Services\ConversationPersistenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersistChatHistoryJobTest extends TestCase
{
    use RefreshDatabase;

    private ChatHistoryService $chatHistoryService;

    private string $channel = 'C_TEST';

    private string $threadTs = '123.456';

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_chat_history_can_be_persisted_by_job(): void
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

        $job = new PersistChatHistoryJob(
            $this->channel,
            $this->threadTs
        );

        $job->handle(
            app(ConversationPersistenceService::class)
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

        $this->assertDatabaseCount(
            'conversation_messages',
            2
        );

        $this->assertSame(
            [],
            $this->chatHistoryService->getHistory(
                $this->channel,
                $this->threadTs
            )
        );
    }
}
