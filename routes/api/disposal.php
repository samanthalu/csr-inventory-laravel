<?php

use App\Http\Controllers\Disposal\DisposalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/disposal',                    [DisposalController::class, 'index']);
    Route::post('/disposal',                   [DisposalController::class, 'store']);
    Route::get('/disposal/{id}',               [DisposalController::class, 'show']);
    Route::put('/disposal/{id}',               [DisposalController::class, 'update']);
    Route::patch('/disposal/{id}/approve',     [DisposalController::class, 'approve']);
    Route::patch('/disposal/{id}/complete',    [DisposalController::class, 'complete']);
    Route::delete('/disposal/{id}',            [DisposalController::class, 'destroy']);
});
