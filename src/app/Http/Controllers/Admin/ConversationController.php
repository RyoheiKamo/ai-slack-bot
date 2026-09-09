<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Conversation::query();

        if ($request->filled('channel')) {
            $query->where(
                'channel',
                $request->string('channel')
            );
        }

        if ($request->filled('thread_ts')) {
            $query->where(
                'thread_ts',
                $request->string('thread_ts')
            );
        }

        $conversations = $query
            ->withCount('messages')
            ->with([
                'latestMessage',
                'firstMessage',
            ])
            ->withMax('messages', 'message_created_at')
            ->orderByDesc('messages_max_message_created_at')
            ->paginate(20);

        return response()->json([
            'data' => collect($conversations->items())
                ->map(function (Conversation $conversation) {
                    return [
                        'id' => $conversation->id,
                        'channel' => $conversation->channel,
                        'thread_ts' => $conversation->thread_ts,
                        'message_count' => $conversation->messages_count,
                        'latest_message' => $conversation->latestMessage?->content,
                        'started_at' => $conversation->firstMessage?->message_created_at,
                        'created_at' => $conversation->created_at,
                        'updated_at' => $conversation->updated_at,
                    ];
                }),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }
}
