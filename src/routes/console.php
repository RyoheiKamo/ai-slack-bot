<?php

use App\Services\ChatHistoryPersistenceDispatcher;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $count = app(ChatHistoryPersistenceDispatcher::class)
        ->dispatchInactiveConversations(30);

    Log::info('Inactive chat histories dispatched', [
        'count' => $count,
    ]);
})->everyFiveMinutes();
