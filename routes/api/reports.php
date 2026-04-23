<?php

use App\Http\Controllers\Reports\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('reports')->group(function () {
    Route::get('/summary',     [ReportController::class, 'summary']);
    Route::get('/assets',      [ReportController::class, 'assets']);
    Route::get('/hire',        [ReportController::class, 'hire']);
    Route::get('/maintenance', [ReportController::class, 'maintenance']);
    Route::get('/disposal',    [ReportController::class, 'disposal']);
});
