<?php

use App\Http\Controllers\SlackEventController;
use Illuminate\Support\Facades\Route;

Route::post('/slack/events', [SlackEventController::class, 'handle']);
