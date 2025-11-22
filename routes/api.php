<?php

use App\Http\Controllers\Auth\CallbackController;
use App\Http\Controllers\Auth\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    // OAuth callback route
    Route::get('/callback', CallbackController::class)->name('auth.callback');

    // Webhook route
    Route::post('/webhook', WebhookController::class)->name('auth.webhook');
});
