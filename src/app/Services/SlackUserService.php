<?php

namespace App\Services;

use App\Models\SlackUser;

class SlackUserService
{
    public function findOrCreate(
        string $slackUserId,
        ?string $displayName = null,
        ?string $realName = null,
    ): SlackUser {
        $slackUser = SlackUser::where(
            'slack_user_id',
            $slackUserId
        )->first();

        if ($slackUser) {
            $slackUser->update([
                'display_name' => $displayName ?? $slackUser->display_name,
                'real_name' => $realName ?? $slackUser->real_name,
                'last_used_at' => now(),
            ]);

            return $slackUser;
        }

        return SlackUser::create([
            'slack_user_id' => $slackUserId,
            'display_name' => $displayName,
            'real_name' => $realName,
            'first_used_at' => now(),
            'last_used_at' => now(),
        ]);
    }
}
