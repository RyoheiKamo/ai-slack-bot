<?php

namespace App\Http\Controllers;

use App\Services\SlackService;
use Illuminate\Http\Request;

class SlackEventController extends Controller
{
    public function __construct(
        private readonly SlackService $slackService
    ) {}

    public function handle(Request $request)
    {
        return $this->slackService->handleEvent($request);
    }
}
