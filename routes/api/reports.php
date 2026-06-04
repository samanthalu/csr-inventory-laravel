<?php

use App\Http\Controllers\Reports\ReportController;
use Illuminate\Support\Facades\Route;

// Dashboard data — decoupled from the Reports module so the main dashboard
// (/dashboard) and products dashboard (/products/manage) don't require view_reports.
Route::middleware(['auth:sanctum'])->prefix('reports')->group(function () {
    // Main dashboard overview — any authenticated user
    Route::get('/summary', [ReportController::class, 'summary']);
    // Products dashboard asset stats — products viewers or reports viewers
    Route::get('/assets', [ReportController::class, 'assets'])
        ->middleware('permission:view_products|view_reports');
});

// Reports module proper — requires the Reports permission
Route::middleware(['auth:sanctum', 'permission:view_reports'])->prefix('reports')->group(function () {
    Route::get('/products',    [ReportController::class, 'products']);
    Route::get('/hire',        [ReportController::class, 'hire']);
    Route::get('/maintenance', [ReportController::class, 'maintenance']);
    Route::get('/disposal',    [ReportController::class, 'disposal']);
});
