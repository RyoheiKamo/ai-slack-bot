<?php

namespace Tests\Unit\Services;

use App\Models\SlackChannel;
use App\Services\SlackChannelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlackChannelServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_new_slack_channel_when_not_exists(): void
    {
        $service = app(SlackChannelService::class);

        $slackChannel = $service->findOrCreate(
            'C12345678',
            'development',
        );

        $this->assertDatabaseHas('slack_channels', [
            'slack_channel_id' => 'C12345678',
            'name' => 'development',
        ]);

        $this->assertNotNull($slackChannel->first_used_at);
        $this->assertNotNull($slackChannel->last_used_at);
    }

    public function test_updates_existing_slack_channel_when_exists(): void
    {
        $existing = SlackChannel::factory()->create([
            'slack_channel_id' => 'C12345678',
            'name' => 'old-name',
            'first_used_at' => now()->subDay(),
            'last_used_at' => now()->subDay(),
        ]);

        $firstUsedAt = $existing->first_used_at;

        $service = app(SlackChannelService::class);

        $slackChannel = $service->findOrCreate(
            'C12345678',
            'development',
        );

        $this->assertDatabaseHas('slack_channels', [
            'id' => $existing->id,
            'slack_channel_id' => 'C12345678',
            'name' => 'development',
        ]);

        $this->assertTrue(
            $slackChannel->first_used_at->equalTo($firstUsedAt)
        );

        $this->assertTrue(
            $slackChannel->last_used_at->greaterThan(
                $existing->last_used_at
            )
        );
    }
}
