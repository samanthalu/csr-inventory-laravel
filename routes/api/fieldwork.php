<?php

use App\Http\Controllers\FieldWork\FieldWorkSessionController;
use App\Http\Controllers\FieldWork\ResearchAssistantController;
use App\Http\Controllers\FieldWork\RaAssetAssignmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Sessions
    Route::get('/fieldwork',            [FieldWorkSessionController::class, 'index']);
    Route::post('/fieldwork',           [FieldWorkSessionController::class, 'store']);
    Route::get('/fieldwork/{id}',       [FieldWorkSessionController::class, 'show']);
    Route::put('/fieldwork/{id}',       [FieldWorkSessionController::class, 'update']);
    Route::delete('/fieldwork/{id}',    [FieldWorkSessionController::class, 'destroy']);

    // Research assistants (scoped to session)
    Route::post('/fieldwork/{sessionId}/assistants',              [ResearchAssistantController::class, 'store']);
    Route::post('/fieldwork/{sessionId}/assistants/bulk',         [ResearchAssistantController::class, 'bulkStore']);
    Route::put('/fieldwork/{sessionId}/assistants/{id}',          [ResearchAssistantController::class, 'update']);
    Route::delete('/fieldwork/{sessionId}/assistants/{id}',       [ResearchAssistantController::class, 'destroy']);

    // Asset assignments (scoped to session)
    Route::post('/fieldwork/{sessionId}/assignments',             [RaAssetAssignmentController::class, 'store']);
    Route::patch('/fieldwork/{sessionId}/assignments/{id}/return',[RaAssetAssignmentController::class, 'return']);
    Route::delete('/fieldwork/{sessionId}/assignments/{id}',      [RaAssetAssignmentController::class, 'destroy']);
});
