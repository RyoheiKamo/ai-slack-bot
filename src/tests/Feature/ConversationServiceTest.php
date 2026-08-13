<?php

namespace Tests\Feature;

use App\Services\ChatHistoryService;
use App\Services\ConversationService;
use App\Services\OpenAIService;
use App\Services\SlackMessageService;
use Mockery;
use Tests\TestCase;

class ConversationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * 正常系
     */
    public function test_process_generates_reply_and_sends_slack_message(): void
    {
        $text = '<@U999> PHPとは？';
        $channel = 'C123';
        $threadTs = '123.456';
        $eventId = 'Ev123';

        $history = [
            [
                'role' => 'user',
                'content' => 'PHPとは？',
            ],
        ];

        $reply = 'PHPはWeb開発でよく使われるプログラミング言語です。';

        $chatHistoryService = Mockery::mock(ChatHistoryService::class);
        $openAIService = Mockery::mock(OpenAIService::class);
        $slackMessageService = Mockery::mock(SlackMessageService::class);

        $chatHistoryService
            ->shouldReceive('addUserMessage')
            ->once()
            ->with(
                $channel,
                $threadTs,
                'PHPとは？'
            );

        $chatHistoryService
            ->shouldReceive('getHistory')
            ->once()
            ->with(
                $channel,
                $threadTs
            )
            ->andReturn($history);

        $openAIService
            ->shouldReceive('generateReply')
            ->once()
            ->with($history)
            ->andReturn($reply);

        $chatHistoryService
            ->shouldReceive('addAssistantMessage')
            ->once()
            ->with(
                $channel,
                $threadTs,
                $reply
            );

        $slackMessageService
            ->shouldReceive('sendMessage')
            ->once()
            ->with(
                $channel,
                $reply,
                $threadTs
            );

        $service = new ConversationService(
            $chatHistoryService,
            $openAIService,
            $slackMessageService
        );

        $service->process(
            $text,
            $channel,
            $threadTs,
            $eventId
        );

        $this->assertTrue(true);
    }

    /**
     * 異常系
     */
    public function test_process_sends_error_message_when_openai_fails(): void
    {
        $text = '<@U999> PHPとは？';
        $channel = 'C123';
        $threadTs = '123.456';
        $eventId = 'Ev123';

        $history = [
            [
                'role' => 'user',
                'content' => 'PHPとは？',
            ],
        ];

        $errorMessage = '現在OpenAI APIの利用枠が不足しています。管理者へお問い合わせください。';

        $chatHistoryService = Mockery::mock(ChatHistoryService::class);
        $openAIService = Mockery::mock(OpenAIService::class);
        $slackMessageService = Mockery::mock(SlackMessageService::class);

        $chatHistoryService
            ->shouldReceive('addUserMessage')
            ->once()
            ->with(
                $channel,
                $threadTs,
                'PHPとは？'
            );

        $chatHistoryService
            ->shouldReceive('getHistory')
            ->once()
            ->with(
                $channel,
                $threadTs
            )
            ->andReturn($history);

        $openAIService
            ->shouldReceive('generateReply')
            ->once()
            ->with($history)
            ->andThrow(
                new \RuntimeException($errorMessage)
            );

        $chatHistoryService
            ->shouldNotReceive('addAssistantMessage');

        $slackMessageService
            ->shouldReceive('sendMessage')
            ->once()
            ->with(
                $channel,
                $errorMessage,
                $threadTs
            );

        $service = new ConversationService(
            $chatHistoryService,
            $openAIService,
            $slackMessageService
        );

        $service->process(
            $text,
            $channel,
            $threadTs,
            $eventId
        );

        $this->assertTrue(true);
    }
}
