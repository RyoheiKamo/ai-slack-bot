<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SlackService
{
    public function __construct(
        private readonly SlackSignatureService $signatureService,
        private readonly SlackEventService $eventService
    ) {}

    public function handleEvent(Request $request): Response
    {
        if (! $this->signatureService->verify(
            $request->header('X-Slack-Request-Timestamp'),
            $request->header('X-Slack-Signature'),
            $request->getContent()
        )) {
            abort(401);
        }

        $payload = $request->all();

        if (($payload['type'] ?? null) === 'url_verification') {
            return response(
                $payload['challenge'] ?? '',
                200
            );
        }

        if (($payload['type'] ?? null) !== 'event_callback') {
            return $this->successResponse();
        }

        $this->eventService->handle($payload);

        return $this->successResponse();
    }

    private function successResponse(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }
}
