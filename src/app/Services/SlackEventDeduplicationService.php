<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class SlackEventDeduplicationService
{
    private const TTL_SECONDS = 600;

    /**
     * 初回イベントならtrue、処理済みならfalse
     */
    public function acquire(string $eventId): bool
    {
        $key = "slack:event:{$eventId}";

        $result = Redis::connection()->set(
            $key,
            'processed',
            'EX',
            self::TTL_SECONDS,
            'NX'
        );

        return $result === true || $result === 'OK';
    }
}
