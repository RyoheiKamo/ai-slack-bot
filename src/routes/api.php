<?php

use App\Http\Controllers\SlackEventController;
use App\Http\Controllers\Admin\ConversationController;
use App\Http\Controllers\Admin\SlackChannelController;
use App\Http\Controllers\Admin\SlackUserController;
use Illuminate\Support\Facades\Route;

Route::post('/slack/events', [SlackEventController::class, 'handle']);

Route::prefix('admin')->group(function () {
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::get('/slack-channels', [SlackChannelController::class, 'index']);
    Route::get('/slack-users', [SlackUserController::class, 'index']);
});
