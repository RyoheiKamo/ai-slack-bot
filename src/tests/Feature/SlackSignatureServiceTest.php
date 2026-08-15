<?php

namespace Tests\Feature;

use App\Services\SlackSignatureService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SlackSignatureServiceTest extends TestCase
{
    private SlackSignatureService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set(
            'services.slack.signing_secret',
            'test-signing-secret'
        );

        $this->service = app(SlackSignatureService::class);
    }

    public function test_verify_returns_true_for_valid_signature(): void
    {
        $timestamp = (string) time();
        $body = '{"type":"event_callback"}';

        $signature = $this->createSignature(
            $timestamp,
            $body
        );

        $this->assertTrue(
            $this->service->verify(
                $timestamp,
                $signature,
                $body
            )
        );
    }

    public function test_verify_returns_false_for_invalid_signature(): void
    {
        $timestamp = (string) time();
        $body = '{"type":"event_callback"}';

        $this->assertFalse(
            $this->service->verify(
                $timestamp,
                'v0=invalid-signature',
                $body
            )
        );
    }

    public function test_verify_returns_false_for_old_timestamp(): void
    {
        $timestamp = (string) (time() - 301);
        $body = '{"type":"event_callback"}';

        $signature = $this->createSignature(
            $timestamp,
            $body
        );

        $this->assertFalse(
            $this->service->verify(
                $timestamp,
                $signature,
                $body
            )
        );
    }

    public function test_verify_returns_false_when_timestamp_is_missing(): void
    {
        $this->assertFalse(
            $this->service->verify(
                null,
                'v0=test',
                '{}'
            )
        );
    }

    public function test_verify_returns_false_when_signature_is_missing(): void
    {
        $this->assertFalse(
            $this->service->verify(
                (string) time(),
                null,
                '{}'
            )
        );
    }

    private function createSignature(
        string $timestamp,
        string $body
    ): string {
        $baseString = sprintf(
            'v0:%s:%s',
            $timestamp,
            $body
        );

        return 'v0=' . hash_hmac(
            'sha256',
            $baseString,
            'test-signing-secret'
        );
    }
}
