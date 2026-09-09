<?php

use App\Http\Controllers\SlackEventController;
use App\Http\Controllers\Admin\ConversationController;
use Illuminate\Support\Facades\Route;

Route::post('/slack/events', [SlackEventController::class, 'handle']);

Route::prefix('admin')->group(function () {
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
});
