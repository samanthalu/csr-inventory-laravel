<?php

use App\Http\Controllers\AuditLog\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/audit-logs',     [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{id}',[AuditLogController::class, 'show']);
});
