<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SlackUserResource;
use App\Models\SlackUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlackUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SlackUser::query()
            ->withCount('messages');

        if ($request->filled('slack_user_id')) {
            $query->where(
                'slack_user_id',
                $request->string('slack_user_id')
            );
        }

        if ($request->filled('display_name')) {
            $query->where(
                'display_name',
                $request->string('display_name')
            );
        }

        $slackUsers = $query
            ->orderByDesc('last_used_at')
            ->paginate(20);

        return response()->json([
            'data' => SlackUserResource::collection(
                $slackUsers->items()
            ),
            'meta' => [
                'current_page' => $slackUsers->currentPage(),
                'last_page' => $slackUsers->lastPage(),
                'per_page' => $slackUsers->perPage(),
                'total' => $slackUsers->total(),
            ],
        ]);
    }
}
