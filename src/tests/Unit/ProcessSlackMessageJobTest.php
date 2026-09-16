<?php

namespace Tests\Feature;

use App\Jobs\ProcessSlackMessageJob;
use App\Services\ConversationService;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProcessSlackMessageJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_handle_processes_conversation(): void
    {
        $conversationService = Mockery::mock(
            ConversationService::class
        );

        $conversationService
            ->shouldReceive('process')
            ->once()
            ->with(
                'PHPとは？',
                'C123',
                '123.456',
                'Ev123',
                'U12345678'
            );

        $job = new ProcessSlackMessageJob(
            'PHPとは？',
            'C123',
            '123.456',
            'Ev123',
            'U12345678'
        );

        $job->handle($conversationService);

        $this->assertTrue(true);
    }

    public function test_failed_logs_job_failure(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with(
                'ProcessSlackMessageJob failed',
                [
                    'event_id' => 'Ev123',
                    'channel' => 'C123',
                    'thread_ts' => '123.456',
                    'slack_user_id' => 'U12345678',
                    'message' => 'Unexpected error',
                ]
            );

        $job = new ProcessSlackMessageJob(
            'PHPとは？',
            'C123',
            '123.456',
            'Ev123',
            'U12345678'
        );

        $job->failed(
            new RuntimeException('Unexpected error')
        );

        $this->assertTrue(true);
    }
}
