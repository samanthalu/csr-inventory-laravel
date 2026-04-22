<?php

use App\Http\Controllers\Hire\HireController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/hires',              [HireController::class, 'index']);
    Route::post('/hires',             [HireController::class, 'store']);
    Route::get('/hires/{id}',         [HireController::class, 'show']);
    Route::put('/hires/{id}',         [HireController::class, 'update']);
    Route::patch('/hires/{id}/return',              [HireController::class, 'return']);
    Route::patch('/hires/{hireId}/items/{itemId}/return', [HireController::class, 'returnItem']);
    Route::delete('/hires/{id}',      [HireController::class, 'destroy']);
});
