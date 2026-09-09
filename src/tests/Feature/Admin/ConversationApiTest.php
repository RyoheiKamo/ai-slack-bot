<?php

namespace Tests\Feature\Admin;

use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_list_can_be_fetched(): void
    {
        $response = $this->getJson('/api/admin/conversations');

        $response->assertOk();
    }

    public function test_conversation_list_has_expected_json_structure(): void
    {
        $response = $this->getJson('/api/admin/conversations');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_conversation_list_returns_conversations(): void
    {
        $conversation = Conversation::factory()->create([
            'channel' => 'C12345678',
            'thread_ts' => '1757390000.123456',
        ]);

        $response = $this->getJson('/api/admin/conversations');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $conversation->id,
                'channel' => 'C12345678',
                'thread_ts' => '1757390000.123456',
            ]);
    }

    public function test_conversation_list_returns_message_count(): void
    {
        $conversation = Conversation::factory()->create();

        $conversation->messages()->createMany([
            [
                'message_id' => fake()->uuid(),
                'role' => 'user',
                'content' => 'Hello',
                'message_created_at' => now()->subMinute(),
            ],
            [
                'message_id' => fake()->uuid(),
                'role' => 'assistant',
                'content' => 'Hi',
                'message_created_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/admin/conversations');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.message_count',
                2
            );
    }

    public function test_conversation_list_returns_latest_message(): void
    {
        $conversation = Conversation::factory()->create();

        $conversation->messages()->createMany([
            [
                'message_id' => fake()->uuid(),
                'role' => 'user',
                'content' => '古いメッセージ',
                'message_created_at' => now()->subMinute(),
            ],
            [
                'message_id' => fake()->uuid(),
                'role' => 'assistant',
                'content' => '最新のメッセージ',
                'message_created_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/admin/conversations');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.latest_message',
                '最新のメッセージ'
            );
    }

    public function test_conversation_list_returns_started_at(): void
    {
        $conversation = Conversation::factory()->create();

        $startedAt = now()->subMinutes(5)->startOfSecond();

        $conversation->messages()->createMany([
            [
                'message_id' => fake()->uuid(),
                'role' => 'user',
                'content' => '最初のメッセージ',
                'message_created_at' => $startedAt,
            ],
            [
                'message_id' => fake()->uuid(),
                'role' => 'assistant',
                'content' => '次のメッセージ',
                'message_created_at' => now(),
            ],
        ]);

        $response = $this->getJson('/api/admin/conversations');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.started_at',
                $startedAt->toISOString()
            );
    }

    public function test_conversations_are_ordered_by_latest_message_desc(): void
    {
        $oldConversation = Conversation::factory()->create();
        $newConversation = Conversation::factory()->create();

        $oldConversation->messages()->create([
            'message_id' => fake()->uuid(),
            'role' => 'user',
            'content' => '古い会話',
            'message_created_at' => now()->subMinutes(10)->startOfSecond(),
        ]);

        $newConversation->messages()->create([
            'message_id' => fake()->uuid(),
            'role' => 'user',
            'content' => '新しい会話',
            'message_created_at' => now()->startOfSecond(),
        ]);

        $response = $this->getJson('/api/admin/conversations');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $newConversation->id)
            ->assertJsonPath('data.1.id', $oldConversation->id);
    }

    public function test_conversations_are_paginated(): void
    {
        Conversation::factory()
            ->count(25)
            ->create();

        $response = $this->getJson(
            '/api/admin/conversations?page=1'
        );

        $response
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_conversations_can_be_filtered_by_channel(): void
    {
        Conversation::factory()->create([
            'channel' => 'C111111',
        ]);

        Conversation::factory()->create([
            'channel' => 'C222222',
        ]);

        $response = $this->getJson(
            '/api/admin/conversations?channel=C111111'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.channel',
                'C111111'
            );
    }

    public function test_conversations_can_be_filtered_by_thread_ts(): void
    {
        Conversation::factory()->create([
            'thread_ts' => '1757390000.111111',
        ]);

        Conversation::factory()->create([
            'thread_ts' => '1757390000.222222',
        ]);

        $response = $this->getJson(
            '/api/admin/conversations?thread_ts=1757390000.111111'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.thread_ts',
                '1757390000.111111'
            );
    }

    public function test_conversations_can_be_filtered_by_channel_and_thread_ts(): void
    {
        Conversation::factory()->create([
            'channel' => 'C111111',
            'thread_ts' => '1757390000.111111',
        ]);

        Conversation::factory()->create([
            'channel' => 'C111111',
            'thread_ts' => '1757390000.222222',
        ]);

        Conversation::factory()->create([
            'channel' => 'C222222',
            'thread_ts' => '1757390000.111111',
        ]);

        $response = $this->getJson(
            '/api/admin/conversations'
                . '?channel=C111111'
                . '&thread_ts=1757390000.111111'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.channel',
                'C111111'
            )
            ->assertJsonPath(
                'data.0.thread_ts',
                '1757390000.111111'
            );
    }

    public function test_conversation_detail_can_be_fetched(): void
    {
        $conversation = Conversation::factory()->create();

        $response = $this->getJson(
            "/api/admin/conversations/{$conversation->id}"
        );

        $response->assertOk();
    }

    public function test_conversation_detail_has_expected_json_structure(): void
    {
        $conversation = Conversation::factory()->create();

        $response = $this->getJson(
            "/api/admin/conversations/{$conversation->id}"
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'channel',
                    'thread_ts',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_conversation_detail_contains_messages(): void
    {
        $conversation = Conversation::factory()->create();

        $conversation->messages()->createMany([
            [
                'message_id' => fake()->uuid(),
                'role' => 'user',
                'content' => 'Laravelについて教えて',
                'message_created_at' => now()
                    ->subMinute()
                    ->startOfSecond(),
            ],
            [
                'message_id' => fake()->uuid(),
                'role' => 'assistant',
                'content' => 'LaravelはPHPのWebフレームワークです。',
                'message_created_at' => now()
                    ->startOfSecond(),
            ],
        ]);

        $response = $this->getJson(
            "/api/admin/conversations/{$conversation->id}"
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'channel',
                    'thread_ts',
                    'created_at',
                    'updated_at',
                    'messages' => [
                        '*' => [
                            'id',
                            'message_id',
                            'role',
                            'content',
                            'message_created_at',
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(
                2,
                'data.messages'
            );
    }

    public function test_conversation_detail_messages_are_ordered_by_created_at_asc(): void
    {
        $conversation = Conversation::factory()->create();

        $newMessage = $conversation->messages()->create([
            'message_id' => fake()->uuid(),
            'role' => 'assistant',
            'content' => '新しいメッセージ',
            'message_created_at' => now()
                ->startOfSecond(),
        ]);

        $oldMessage = $conversation->messages()->create([
            'message_id' => fake()->uuid(),
            'role' => 'user',
            'content' => '古いメッセージ',
            'message_created_at' => now()
                ->subMinutes(5)
                ->startOfSecond(),
        ]);

        $response = $this->getJson(
            "/api/admin/conversations/{$conversation->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.messages.0.id',
                $oldMessage->id
            )
            ->assertJsonPath(
                'data.messages.1.id',
                $newMessage->id
            );
    }

    public function test_conversation_detail_returns_404_when_not_found(): void
    {
        $response = $this->getJson(
            '/api/admin/conversations/999999'
        );

        $response->assertNotFound();
    }
}
