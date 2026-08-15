<?php

namespace Tests\Feature;

use App\Jobs\ProcessSlackMessageJob;
use App\Services\SlackEventDeduplicationService;
use App\Services\SlackEventService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class SlackEventServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_app_mention_dispatches_job(): void
    {
        Bus::fake();

        $deduplicationService = Mockery::mock(
            SlackEventDeduplicationService::class
        );

        $deduplicationService
            ->shouldReceive('acquire')
            ->once()
            ->with('Ev123')
            ->andReturn(true);

        $service = new SlackEventService(
            $deduplicationService
        );

        $payload = [
            'event_id' => 'Ev123',
            'event' => [
                'type' => 'app_mention',
                'user' => 'U123',
                'channel' => 'C123',
                'ts' => '123.456',
                'text' => '<@U999> PHPとは？',
            ],
        ];

        $service->handle($payload);

        Bus::assertDispatched(
            ProcessSlackMessageJob::class,
            function (ProcessSlackMessageJob $job) {
                return true;
            }
        );
    }

    public function test_duplicate_event_does_not_dispatch_job(): void
    {
        Bus::fake();
        Log::spy();

        $deduplicationService = Mockery::mock(
            SlackEventDeduplicationService::class
        );

        $deduplicationService
            ->shouldReceive('acquire')
            ->once()
            ->with('Ev123')
            ->andReturn(false);

        $service = new SlackEventService(
            $deduplicationService
        );

        $payload = [
            'event_id' => 'Ev123',
            'event' => [
                'type' => 'app_mention',
                'user' => 'U123',
                'channel' => 'C123',
                'ts' => '123.456',
                'text' => '<@U999> PHPとは？',
            ],
        ];

        $service->handle($payload);

        Bus::assertNotDispatched(
            ProcessSlackMessageJob::class
        );

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Duplicate Slack event skipped', [
                'event_id' => 'Ev123',
            ]);
    }

    public function test_missing_event_id_does_not_dispatch_job(): void
    {
        Bus::fake();
        Log::spy();

        $deduplicationService = Mockery::mock(
            SlackEventDeduplicationService::class
        );

        $deduplicationService
            ->shouldNotReceive('acquire');

        $service = new SlackEventService(
            $deduplicationService
        );

        $payload = [
            'event' => [
                'type' => 'app_mention',
                'channel' => 'C123',
                'ts' => '123.456',
                'text' => '<@U999> PHPとは？',
            ],
        ];

        $service->handle($payload);

        Bus::assertNotDispatched(
            ProcessSlackMessageJob::class
        );

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Slack event ID is missing');
    }

    public function test_non_app_mention_does_not_check_duplicate_or_dispatch_job(): void
    {
        Bus::fake();

        $deduplicationService = Mockery::mock(
            SlackEventDeduplicationService::class
        );

        $deduplicationService
            ->shouldNotReceive('acquire');

        $service = new SlackEventService(
            $deduplicationService
        );

        $payload = [
            'event_id' => 'Ev123',
            'event' => [
                'type' => 'reaction_added',
                'user' => 'U123',
                'channel' => 'C123',
            ],
        ];

        $service->handle($payload);

        Bus::assertNotDispatched(
            ProcessSlackMessageJob::class
        );
    }
}
