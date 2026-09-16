<?php

namespace Tests\Feature;

use App\Models\SlackUser;
use App\Services\ChatHistoryService;
use App\Services\ConversationHistoryLimiter;
use App\Services\ConversationService;
use App\Services\OpenAIService;
use App\Services\SlackMessageService;
use App\Services\SlackUserService;
use Mockery;
use Tests\TestCase;

class ConversationServiceTest extends TestCase
{
    private string $slackUserId = 'U12345678';

    private int $slackUserDbId = 1;

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
        $historyLimiter = Mockery::mock(ConversationHistoryLimiter::class);
        $openAIService = Mockery::mock(OpenAIService::class);
        $slackMessageService = Mockery::mock(SlackMessageService::class);
        $slackUserService = $this->mockSlackUserService(
            $this->slackUserId,
            $this->slackUserDbId
        );

        $chatHistoryService
            ->shouldReceive('addUserMessage')
            ->once()
            ->with(
                $channel,
                $threadTs,
                'PHPとは？',
                $this->slackUserDbId
            );

        $chatHistoryService
            ->shouldReceive('getHistory')
            ->once()
            ->with(
                $channel,
                $threadTs
            )
            ->andReturn($history);

        $historyLimiter
            ->shouldReceive('limit')
            ->once()
            ->with($history)
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
            $historyLimiter,
            $openAIService,
            $slackMessageService,
            $slackUserService
        );

        $service->process(
            $text,
            $channel,
            $threadTs,
            $eventId,
            $this->slackUserId
        );

        $this->assertTrue(true);
    }

    /**
     * 異常系：OpenAI失敗
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
        $historyLimiter = Mockery::mock(ConversationHistoryLimiter::class);
        $openAIService = Mockery::mock(OpenAIService::class);
        $slackMessageService = Mockery::mock(SlackMessageService::class);
        $slackUserService = $this->mockSlackUserService(
            $this->slackUserId,
            $this->slackUserDbId
        );

        $chatHistoryService
            ->shouldReceive('addUserMessage')
            ->once()
            ->with(
                $channel,
                $threadTs,
                'PHPとは？',
                $this->slackUserDbId
            );

        $chatHistoryService
            ->shouldReceive('getHistory')
            ->once()
            ->with(
                $channel,
                $threadTs
            )
            ->andReturn($history);

        $historyLimiter
            ->shouldReceive('limit')
            ->once()
            ->with($history)
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
            $historyLimiter,
            $openAIService,
            $slackMessageService,
            $slackUserService
        );

        $service->process(
            $text,
            $channel,
            $threadTs,
            $eventId,
            $this->slackUserId
        );

        $this->assertTrue(true);
    }

    /**
     * 異常系：履歴保存失敗
     */
    public function test_process_sends_error_message_when_history_service_fails(): void
    {
        $text = '<@U999> PHPとは？';
        $channel = 'C123';
        $threadTs = '123.456';
        $eventId = 'Ev123';

        $errorMessage = 'Redisへの保存に失敗しました。';

        $chatHistoryService = Mockery::mock(ChatHistoryService::class);
        $historyLimiter = Mockery::mock(ConversationHistoryLimiter::class);
        $openAIService = Mockery::mock(OpenAIService::class);
        $slackMessageService = Mockery::mock(SlackMessageService::class);
        $slackUserService = $this->mockSlackUserService(
            $this->slackUserId,
            $this->slackUserDbId
        );

        $chatHistoryService
            ->shouldReceive('addUserMessage')
            ->once()
            ->with(
                $channel,
                $threadTs,
                'PHPとは？',
                $this->slackUserDbId
            )
            ->andThrow(
                new \RuntimeException($errorMessage)
            );

        $chatHistoryService
            ->shouldNotReceive('getHistory');

        $historyLimiter
            ->shouldNotReceive('limit');

        $openAIService
            ->shouldNotReceive('generateReply');

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
            $historyLimiter,
            $openAIService,
            $slackMessageService,
            $slackUserService
        );

        $service->process(
            $text,
            $channel,
            $threadTs,
            $eventId,
            $this->slackUserId
        );

        $this->assertTrue(true);
    }

    public function test_process_clears_history_when_reset_command_is_received(): void
    {
        $text = '<@U999> /reset';
        $channel = 'C123';
        $threadTs = '123.456';
        $eventId = 'Ev123';

        $chatHistoryService = Mockery::mock(ChatHistoryService::class);
        $historyLimiter = Mockery::mock(ConversationHistoryLimiter::class);
        $openAIService = Mockery::mock(OpenAIService::class);
        $slackMessageService = Mockery::mock(SlackMessageService::class);
        $slackUserService = Mockery::mock(SlackUserService::class);

        $slackUserService
            ->shouldNotReceive('findOrCreate');

        $chatHistoryService
            ->shouldReceive('clearHistory')
            ->once()
            ->with(
                $channel,
                $threadTs
            );

        $chatHistoryService
            ->shouldNotReceive('addUserMessage');

        $chatHistoryService
            ->shouldNotReceive('getHistory');

        $historyLimiter
            ->shouldNotReceive('limit');

        $openAIService
            ->shouldNotReceive('generateReply');

        $slackMessageService
            ->shouldReceive('sendMessage')
            ->once()
            ->with(
                $channel,
                'このスレッドの会話履歴をリセットしました。',
                $threadTs
            );

        $service = new ConversationService(
            $chatHistoryService,
            $historyLimiter,
            $openAIService,
            $slackMessageService,
            $slackUserService
        );

        $service->process(
            $text,
            $channel,
            $threadTs,
            $eventId,
            $this->slackUserId
        );

        $this->assertTrue(true);
    }

    private function mockSlackUserService(
        string $slackUserId,
        int $slackUserDbId = 1
    ): SlackUserService {
        $slackUserService = Mockery::mock(
            SlackUserService::class
        );

        $slackUser = new SlackUser();
        $slackUser->id = $slackUserDbId;
        $slackUser->slack_user_id = $slackUserId;

        $slackUserService
            ->shouldReceive('findOrCreate')
            ->once()
            ->with($slackUserId)
            ->andReturn($slackUser);

        return $slackUserService;
    }
}
