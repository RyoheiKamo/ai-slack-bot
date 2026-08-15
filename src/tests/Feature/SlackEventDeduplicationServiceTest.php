<?php

namespace Tests\Feature;

use App\Services\SlackEventDeduplicationService;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class SlackEventDeduplicationServiceTest extends TestCase
{
    private SlackEventDeduplicationService $service;

    private string $eventId = 'Ev_TEST_123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(
            SlackEventDeduplicationService::class
        );

        Redis::connection()->del(
            "slack:event:{$this->eventId}"
        );
    }

    protected function tearDown(): void
    {
        Redis::connection()->del(
            "slack:event:{$this->eventId}"
        );

        parent::tearDown();
    }

    public function test_first_event_is_acquired(): void
    {
        $result = $this->service->acquire(
            $this->eventId
        );

        $this->assertTrue($result);
    }

    public function test_duplicate_event_is_rejected(): void
    {
        $first = $this->service->acquire(
            $this->eventId
        );

        $second = $this->service->acquire(
            $this->eventId
        );

        $this->assertTrue($first);
        $this->assertFalse($second);
    }

    public function test_event_key_has_ttl(): void
    {
        $this->service->acquire(
            $this->eventId
        );

        $ttl = Redis::connection()->ttl(
            "slack:event:{$this->eventId}"
        );

        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(600, $ttl);
    }
}
