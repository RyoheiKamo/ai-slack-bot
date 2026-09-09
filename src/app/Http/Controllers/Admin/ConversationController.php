<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ConversationDetailResource;
use App\Http\Resources\Admin\ConversationResource;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Conversation::query();

        if ($request->filled('channel')) {
            $query->where('channel', $request->string('channel'));
        }

        if ($request->filled('thread_ts')) {
            $query->where('thread_ts', $request->string('thread_ts'));
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
            'data' => ConversationResource::collection(
                collect($conversations->items())
            ),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $conversation->load([
            'messages' => fn($query) =>
            $query->orderBy('message_created_at'),
            'latestMessage',
            'firstMessage',
        ]);

        return response()->json([
            'data' => new ConversationDetailResource($conversation),
        ]);
    }
}
