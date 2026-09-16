<?php

namespace Tests\Unit\Services;

use App\Models\SlackUser;
use App\Services\SlackUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlackUserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_new_slack_user_when_not_exists(): void
    {
        $service = new SlackUserService();

        $slackUser = $service->findOrCreate(
            'U12345678',
            'ryohei',
            'Ryohei Kamo',
        );

        $this->assertInstanceOf(
            SlackUser::class,
            $slackUser
        );

        $this->assertDatabaseHas(
            'slack_users',
            [
                'slack_user_id' => 'U12345678',
                'display_name' => 'ryohei',
                'real_name' => 'Ryohei Kamo',
            ]
        );
    }

    public function test_updates_existing_slack_user_when_exists(): void
    {
        $oldLastUsedAt = now()
            ->subDay()
            ->startOfSecond();

        $slackUser = SlackUser::factory()->create([
            'slack_user_id' => 'U12345678',
            'display_name' => 'old-name',
            'real_name' => 'Old Name',
            'last_used_at' => $oldLastUsedAt,
        ]);

        $service = new SlackUserService();

        $result = $service->findOrCreate(
            'U12345678',
            'new-name',
            'New Name',
        );

        $this->assertSame(
            $slackUser->id,
            $result->id
        );

        $this->assertDatabaseCount(
            'slack_users',
            1
        );

        $this->assertDatabaseHas(
            'slack_users',
            [
                'id' => $slackUser->id,
                'slack_user_id' => 'U12345678',
                'display_name' => 'new-name',
                'real_name' => 'New Name',
            ]
        );

        $this->assertTrue(
            $result->last_used_at->greaterThan(
                $oldLastUsedAt
            )
        );
    }

    public function test_keeps_existing_names_when_null_is_given(): void
    {
        $oldLastUsedAt = now()
            ->subDay()
            ->startOfSecond();

        $slackUser = SlackUser::factory()->create([
            'slack_user_id' => 'U12345678',
            'display_name' => 'existing-display-name',
            'real_name' => 'Existing Real Name',
            'last_used_at' => $oldLastUsedAt,
        ]);

        $service = new SlackUserService();

        $result = $service->findOrCreate(
            'U12345678',
            null,
            null,
        );

        $this->assertSame(
            $slackUser->id,
            $result->id
        );

        $this->assertDatabaseHas(
            'slack_users',
            [
                'id' => $slackUser->id,
                'slack_user_id' => 'U12345678',
                'display_name' => 'existing-display-name',
                'real_name' => 'Existing Real Name',
            ]
        );

        $this->assertTrue(
            $result->last_used_at->greaterThan(
                $oldLastUsedAt
            )
        );
    }

    public function test_first_used_at_is_not_changed_when_existing_user_is_updated(): void
    {
        $firstUsedAt = now()
            ->subDays(10)
            ->startOfSecond();

        $slackUser = SlackUser::factory()->create([
            'slack_user_id' => 'U12345678',
            'first_used_at' => $firstUsedAt,
            'last_used_at' => now()
                ->subDay()
                ->startOfSecond(),
        ]);

        $service = new SlackUserService();

        $result = $service->findOrCreate(
            'U12345678',
            'updated-name',
            'Updated Name',
        );

        $this->assertSame(
            $slackUser->id,
            $result->id
        );

        $this->assertTrue(
            $result->first_used_at->equalTo(
                $firstUsedAt
            )
        );

        $this->assertDatabaseHas(
            'slack_users',
            [
                'id' => $slackUser->id,
                'slack_user_id' => 'U12345678',
                'first_used_at' => $firstUsedAt,
            ]
        );
    }
}
