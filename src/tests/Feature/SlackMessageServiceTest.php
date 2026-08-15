<?php

namespace Tests\Feature;

use App\Services\SlackMessageService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SlackMessageServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.slack.bot_token', 'xoxb-test-token');
    }

    public function test_send_message_posts_to_slack_api(): void
    {
        Http::fake([
            'https://slack.com/api/chat.postMessage' => Http::response([
                'ok' => true,
                'ts' => '123.456',
            ], 200),
        ]);

        Log::spy();

        $service = app(SlackMessageService::class);

        $service->sendMessage(
            'C123',
            'こんにちは',
            '111.222'
        );

        Http::assertSent(function ($request) {
            return $request->url()
                === 'https://slack.com/api/chat.postMessage'
                && $request['channel'] === 'C123'
                && $request['text'] === 'こんにちは'
                && $request['thread_ts'] === '111.222';
        });

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Slack message sent', [
                'channel' => 'C123',
                'ts' => '123.456',
            ]);
    }

    public function test_send_message_without_thread_ts(): void
    {
        Http::fake([
            'https://slack.com/api/chat.postMessage' => Http::response([
                'ok' => true,
                'ts' => '123.456',
            ], 200),
        ]);

        Log::spy();

        $service = app(SlackMessageService::class);

        $service->sendMessage(
            'C123',
            'こんにちは'
        );

        Http::assertSent(function ($request) {
            return $request['channel'] === 'C123'
                && $request['text'] === 'こんにちは'
                && ! isset($request['thread_ts']);
        });
    }

    public function test_send_message_logs_error_when_slack_api_returns_error(): void
    {
        Http::fake([
            'https://slack.com/api/chat.postMessage' => Http::response([
                'ok' => false,
                'error' => 'channel_not_found',
            ], 200),
        ]);

        Log::spy();

        $service = app(SlackMessageService::class);

        $service->sendMessage(
            'C_INVALID',
            'テスト'
        );

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Slack message sending failed', [
                'channel' => 'C_INVALID',
                'status' => 200,
                'error' => 'channel_not_found',
                'body' => [
                    'ok' => false,
                    'error' => 'channel_not_found',
                ],
            ]);

        Log::shouldReceive('info')
            ->with('Slack message sent', \Mockery::any())
            ->never();
    }

    public function test_send_message_logs_error_when_http_request_fails(): void
    {
        Http::fake([
            'https://slack.com/api/chat.postMessage' => Http::response([
                'message' => 'Internal Server Error',
            ], 500),
        ]);

        Log::spy();

        $service = app(SlackMessageService::class);

        $service->sendMessage(
            'C123',
            'テスト'
        );

        Log::shouldHaveReceived('error')
            ->once();
    }
}
