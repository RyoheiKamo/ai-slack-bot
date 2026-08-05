<?php

namespace App\Services;

class SlackSignatureService
{
    public function verify(
        ?string $timestamp,
        ?string $signature,
        string $body
    ): bool {

        if (!$timestamp || !$signature) {
            return false;
        }

        /*
         * リプレイ攻撃防止
         */
        if (abs(time() - (int)$timestamp) > 60 * 5) {
            return false;
        }

        $baseString = sprintf(
            'v0:%s:%s',
            $timestamp,
            $body
        );

        $expected = 'v0=' . hash_hmac(
            'sha256',
            $baseString,
            config('services.slack.signing_secret')
        );

        return hash_equals(
            $expected,
            $signature
        );
    }
}
