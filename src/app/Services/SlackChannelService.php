<?php

namespace App\Services;

use App\Models\SlackChannel;

class SlackChannelService
{
    public function findOrCreate(
        string $slackChannelId,
        ?string $name = null,
    ): SlackChannel {
        $slackChannel = SlackChannel::where(
            'slack_channel_id',
            $slackChannelId
        )->first();

        if ($slackChannel) {
            $slackChannel->update([
                'name' => $name ?? $slackChannel->name,
                'last_used_at' => now(),
            ]);

            return $slackChannel;
        }

        return SlackChannel::create([
            'slack_channel_id' => $slackChannelId,
            'name' => $name,
            'first_used_at' => now(),
            'last_used_at' => now(),
        ]);
    }
}
