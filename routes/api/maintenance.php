<?php

use App\Http\Controllers\Maintenance\MaintenanceLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/maintenance',              [MaintenanceLogController::class, 'index']);
    Route::post('/maintenance',             [MaintenanceLogController::class, 'store']);
    Route::get('/maintenance/{id}',         [MaintenanceLogController::class, 'show']);
    Route::put('/maintenance/{id}',         [MaintenanceLogController::class, 'update']);
    Route::patch('/maintenance/{id}/complete', [MaintenanceLogController::class, 'complete']);
    Route::delete('/maintenance/{id}',      [MaintenanceLogController::class, 'destroy']);
});
