<?php

namespace Tests\Feature\Admin;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\SlackUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlackUserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_slack_user_list_can_be_fetched(): void
    {
        $response = $this->getJson('/api/admin/slack-users');

        $response->assertOk();
    }

    public function test_slack_user_list_has_expected_json_structure(): void
    {
        $response = $this->getJson('/api/admin/slack-users');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'slack_user_id',
                        'display_name',
                        'real_name',
                        'first_used_at',
                        'last_used_at',
                        'message_count',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_slack_user_list_returns_slack_users(): void
    {
        $slackUser = SlackUser::factory()->create([
            'slack_user_id' => 'U12345678',
            'display_name' => 'ryohei',
            'real_name' => 'Ryohei Kamo',
        ]);

        $response = $this->getJson('/api/admin/slack-users');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $slackUser->id,
                'slack_user_id' => 'U12345678',
                'display_name' => 'ryohei',
                'real_name' => 'Ryohei Kamo',
            ]);
    }

    public function test_slack_user_list_returns_first_used_at_and_last_used_at(): void
    {
        $firstUsedAt = now()
            ->subDays(10)
            ->startOfSecond();

        $lastUsedAt = now()
            ->subDay()
            ->startOfSecond();

        SlackUser::factory()->create([
            'slack_user_id' => 'U12345678',
            'first_used_at' => $firstUsedAt,
            'last_used_at' => $lastUsedAt,
        ]);

        $response = $this->getJson('/api/admin/slack-users');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.first_used_at',
                $firstUsedAt->toISOString()
            )
            ->assertJsonPath(
                'data.0.last_used_at',
                $lastUsedAt->toISOString()
            );
    }

    public function test_slack_users_are_ordered_by_last_used_at_desc(): void
    {
        $oldUser = SlackUser::factory()->create([
            'last_used_at' => now()
                ->subDays(2)
                ->startOfSecond(),
        ]);

        $newUser = SlackUser::factory()->create([
            'last_used_at' => now()
                ->subDay()
                ->startOfSecond(),
        ]);

        $response = $this->getJson('/api/admin/slack-users');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.id',
                $newUser->id
            )
            ->assertJsonPath(
                'data.1.id',
                $oldUser->id
            );
    }

    public function test_slack_users_are_paginated(): void
    {
        SlackUser::factory()
            ->count(25)
            ->create();

        $response = $this->getJson(
            '/api/admin/slack-users?page=1'
        );

        $response
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_slack_users_can_be_filtered_by_slack_user_id(): void
    {
        SlackUser::factory()->create([
            'slack_user_id' => 'U11111111',
        ]);

        SlackUser::factory()->create([
            'slack_user_id' => 'U22222222',
        ]);

        $response = $this->getJson(
            '/api/admin/slack-users?slack_user_id=U11111111'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.slack_user_id',
                'U11111111'
            );
    }

    public function test_slack_users_can_be_filtered_by_display_name(): void
    {
        SlackUser::factory()->create([
            'display_name' => 'ryohei',
        ]);

        SlackUser::factory()->create([
            'display_name' => 'taro',
        ]);

        $response = $this->getJson(
            '/api/admin/slack-users?display_name=ryohei'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.display_name',
                'ryohei'
            );
    }

    public function test_slack_user_list_returns_message_count(): void
    {
        $slackUser = SlackUser::factory()->create();

        $conversation = Conversation::factory()->create();

        ConversationMessage::factory()
            ->count(2)
            ->create([
                'conversation_id' => $conversation->id,
                'slack_user_id' => $slackUser->id,
                'role' => 'user',
            ]);

        $response = $this->getJson(
            '/api/admin/slack-users'
        );

        $response
            ->assertOk()
            ->assertJsonFragment([
                'slack_user_id' => $slackUser->slack_user_id,
                'message_count' => 2,
            ]);
    }
}
