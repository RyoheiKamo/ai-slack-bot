<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SlackChannelResource;
use App\Models\SlackChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlackChannelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SlackChannel::query();

        if ($request->filled('slack_channel_id')) {
            $query->where(
                'slack_channel_id',
                $request->string('slack_channel_id')
            );
        }

        if ($request->filled('name')) {
            $query->where(
                'name',
                $request->string('name')
            );
        }

        $slackChannels = $query
            ->orderByDesc('last_used_at')
            ->paginate(20);

        return response()->json([
            'data' => SlackChannelResource::collection(
                $slackChannels->items()
            ),
            'meta' => [
                'current_page' => $slackChannels->currentPage(),
                'last_page' => $slackChannels->lastPage(),
                'per_page' => $slackChannels->perPage(),
                'total' => $slackChannels->total(),
            ],
        ]);
    }
}
