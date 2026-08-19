<?php

namespace Tests\Feature;

use App\Services\ConversationHistoryLimiter;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ConversationHistoryLimiterTest extends TestCase
{
    private ConversationHistoryLimiter $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('openai.max_history_tokens', 10);

        $this->service = new ConversationHistoryLimiter();
    }

    public function test_returns_all_messages_when_within_token_limit(): void
    {
        $messages = [
            [
                'role' => 'user',
                'content' => 'PHPとは？',
            ],
            [
                'role' => 'assistant',
                'content' => 'PHPです。',
            ],
        ];

        $result = $this->service->limit($messages);

        $this->assertSame($messages, $result);
    }

    public function test_returns_empty_array_when_history_is_empty(): void
    {
        $result = $this->service->limit([]);

        $this->assertSame([], $result);
    }

    public function test_removes_old_messages_when_token_limit_is_exceeded(): void
    {
        $messages = [
            [
                'role' => 'user',
                'content' => str_repeat('あ', 15), // 5 tokens
            ],
            [
                'role' => 'assistant',
                'content' => str_repeat('い', 15), // 5 tokens
            ],
            [
                'role' => 'user',
                'content' => str_repeat('う', 15), // 5 tokens
            ],
        ];

        $result = $this->service->limit($messages);

        $this->assertCount(2, $result);

        $this->assertSame(
            str_repeat('い', 15),
            $result[0]['content']
        );

        $this->assertSame(
            str_repeat('う', 15),
            $result[1]['content']
        );
    }

    public function test_latest_messages_are_prioritized(): void
    {
        $messages = [
            [
                'role' => 'user',
                'content' => str_repeat('あ', 30), // 10 tokens
            ],
            [
                'role' => 'assistant',
                'content' => str_repeat('い', 15), // 5 tokens
            ],
            [
                'role' => 'user',
                'content' => str_repeat('う', 15), // 5 tokens
            ],
        ];

        $result = $this->service->limit($messages);

        $this->assertCount(2, $result);

        $this->assertSame(
            str_repeat('い', 15),
            $result[0]['content']
        );

        $this->assertSame(
            str_repeat('う', 15),
            $result[1]['content']
        );
    }

    public function test_message_is_included_when_exactly_at_token_limit(): void
    {
        $messages = [
            [
                'role' => 'user',
                'content' => str_repeat('あ', 30), // 10 tokens
            ],
        ];

        $result = $this->service->limit($messages);

        $this->assertCount(1, $result);
        $this->assertSame($messages, $result);
    }

    public function test_latest_message_is_kept_even_when_it_exceeds_token_limit(): void
    {
        Config::set('openai.max_history_tokens', 10);

        $messages = [
            [
                'role' => 'user',
                'content' => str_repeat('あ', 45), // 15 tokens
            ],
        ];

        $result = $this->service->limit($messages);

        $this->assertCount(1, $result);
        $this->assertSame($messages, $result);
    }

    public function test_only_latest_message_is_kept_when_latest_message_exceeds_limit(): void
    {
        Config::set('openai.max_history_tokens', 10);

        $messages = [
            [
                'role' => 'user',
                'content' => '古い質問',
            ],
            [
                'role' => 'assistant',
                'content' => '古い回答',
            ],
            [
                'role' => 'user',
                'content' => str_repeat('あ', 45), // 15 tokens
            ],
        ];

        $result = $this->service->limit($messages);

        $this->assertCount(1, $result);

        $this->assertSame(
            str_repeat('あ', 45),
            $result[0]['content']
        );
    }
}
