<?php

namespace Tests\Feature;

use App\Services\OpenAIService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAIServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.openai.api_key', 'test-api-key');
        Config::set('services.openai.model', 'gpt-test');
        Config::set('services.openai.timeout', 30);
    }

    public function test_generate_reply_returns_output_text(): void
    {
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_test',
                'status' => 'completed',
                'model' => 'gpt-test',
                'output' => [
                    [
                        'type' => 'message',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => 'PHPはプログラミング言語です。',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(OpenAIService::class);

        $reply = $service->generateReply([
            [
                'role' => 'user',
                'content' => 'PHPとは？',
            ],
        ]);

        $this->assertSame(
            'PHPはプログラミング言語です。',
            $reply
        );

        Http::assertSent(function ($request) {
            return $request->url()
                === 'https://api.openai.com/v1/responses'
                && $request['model'] === 'gpt-test'
                && $request['input'] === [
                    [
                        'role' => 'user',
                        'content' => 'PHPとは？',
                    ],
                ];
        });
    }

    public function test_generate_reply_throws_exception_when_api_key_is_invalid(): void
    {
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'error' => [
                    'code' => 'invalid_api_key',
                    'message' => 'Invalid API key',
                ],
            ], 401),
        ]);

        $service = app(OpenAIService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'OpenAI APIキーが正しく設定されていません。管理者へお問い合わせください。'
        );

        $service->generateReply([
            [
                'role' => 'user',
                'content' => 'PHPとは？',
            ],
        ]);
    }

    public function test_generate_reply_throws_exception_when_quota_is_exceeded(): void
    {
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'error' => [
                    'code' => 'insufficient_quota',
                    'message' => 'Quota exceeded',
                ],
            ], 429),
        ]);

        $service = app(OpenAIService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            '現在OpenAI APIの利用枠が不足しています。管理者へお問い合わせください。'
        );

        $service->generateReply([
            [
                'role' => 'user',
                'content' => 'PHPとは？',
            ],
        ]);
    }

    public function test_generate_reply_throws_exception_when_output_text_is_missing(): void
    {
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'id' => 'resp_test',
                'status' => 'completed',
                'model' => 'gpt-test',
                'output' => [],
            ], 200),
        ]);

        $service = app(OpenAIService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'OpenAI APIから回答テキストを取得できませんでした。'
        );

        $service->generateReply([
            [
                'role' => 'user',
                'content' => 'PHPとは？',
            ],
        ]);
    }

    public function test_generate_reply_throws_exception_when_api_key_is_missing(): void
    {
        Config::set('services.openai.api_key', '');

        $service = app(OpenAIService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'OPENAI_API_KEYが設定されていません。'
        );

        $service->generateReply([
            [
                'role' => 'user',
                'content' => 'PHPとは？',
            ],
        ]);
    }

    public function test_openai_uses_configured_instructions(): void
    {
        Config::set(
            'openai.instructions',
            'テスト用システムプロンプト'
        );

        Http::fake([
            'https://api.openai.com/v1/responses' =>
            Http::response([
                'output' => [
                    [
                        'type' => 'message',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => 'OK',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $service = app(OpenAIService::class);

        $service->generateReply([
            [
                'role' => 'user',
                'content' => 'PHPとは？',
            ],
        ]);

        Http::assertSent(function ($request) {
            return $request['instructions']
                === 'テスト用システムプロンプト';
        });
    }
}
