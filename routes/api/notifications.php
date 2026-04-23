<?php

use App\Http\Controllers\Notifications\AlertController;
use App\Http\Controllers\Notifications\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Alerts (live, computed)
    Route::get('/alerts', [AlertController::class, 'index']);

    // Notifications (stored in notifs table)
    Route::get('/notifications',              [NotificationController::class, 'index']);
    Route::post('/notifications',             [NotificationController::class, 'store']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/read-all',   [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{id}/read',  [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{id}',      [NotificationController::class, 'destroy']);
});
