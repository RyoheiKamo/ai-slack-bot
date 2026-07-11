<?php

namespace App\Services;

use Illuminate\Http\Request;

class SlackService
{
    public function handleEvent(Request $request)
    {
        return response()->json([
            'ok' => true,
        ]);
    }
}
