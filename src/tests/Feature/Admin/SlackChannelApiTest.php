<?php

namespace Tests\Feature\Admin;

use App\Models\SlackChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlackChannelApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_slack_channel_list_can_be_fetched(): void
    {
        SlackChannel::factory()->create([
            'slack_channel_id' => 'C12345678',
            'name' => 'development',
        ]);

        $response = $this->getJson(
            '/api/admin/slack-channels'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_slack_channel_list_has_expected_structure(): void
    {
        SlackChannel::factory()->create([
            'slack_channel_id' => 'C12345678',
            'name' => 'development',
        ]);

        $response = $this->getJson(
            '/api/admin/slack-channels'
        );

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'slack_channel_id',
                    'name',
                    'first_used_at',
                    'last_used_at',
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

    public function test_slack_channels_are_ordered_by_last_used_at_desc(): void
    {
        SlackChannel::factory()->create([
            'slack_channel_id' => 'C11111111',
            'name' => 'old-channel',
            'last_used_at' => now()->subDay(),
        ]);

        SlackChannel::factory()->create([
            'slack_channel_id' => 'C22222222',
            'name' => 'new-channel',
            'last_used_at' => now(),
        ]);

        $response = $this->getJson(
            '/api/admin/slack-channels'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.slack_channel_id',
                'C22222222'
            )
            ->assertJsonPath(
                'data.1.slack_channel_id',
                'C11111111'
            );
    }

    public function test_slack_channel_list_is_paginated(): void
    {
        SlackChannel::factory()
            ->count(25)
            ->create();

        $response = $this->getJson(
            '/api/admin/slack-channels'
        );

        $response
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath(
                'meta.current_page',
                1
            )
            ->assertJsonPath(
                'meta.last_page',
                2
            )
            ->assertJsonPath(
                'meta.per_page',
                20
            )
            ->assertJsonPath(
                'meta.total',
                25
            );
    }

    public function test_can_filter_by_slack_channel_id(): void
    {
        SlackChannel::factory()->create([
            'slack_channel_id' => 'C12345678',
            'name' => 'development',
        ]);

        SlackChannel::factory()->create([
            'slack_channel_id' => 'C99999999',
            'name' => 'general',
        ]);

        $response = $this->getJson(
            '/api/admin/slack-channels?slack_channel_id=C12345678'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.slack_channel_id',
                'C12345678'
            );
    }

    public function test_can_filter_by_name(): void
    {
        SlackChannel::factory()->create([
            'slack_channel_id' => 'C12345678',
            'name' => 'development',
        ]);

        SlackChannel::factory()->create([
            'slack_channel_id' => 'C99999999',
            'name' => 'general',
        ]);

        $response = $this->getJson(
            '/api/admin/slack-channels?name=development'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.name',
                'development'
            );
    }
}
