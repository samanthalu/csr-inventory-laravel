<?php

use App\Http\Controllers\Roles\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Permissions list
    Route::get('/permissions', [RoleController::class, 'permissions']);

    // Role CRUD (admin only)
    Route::get   ('/roles',          [RoleController::class, 'index']);
    Route::post  ('/roles',          [RoleController::class, 'store']);
    Route::put   ('/roles/{id}',     [RoleController::class, 'update']);
    Route::delete('/roles/{id}',     [RoleController::class, 'destroy']);

    // User role assignment
    Route::get ('/users/{id}/roles', [RoleController::class, 'userRoles']);
    Route::post('/users/{id}/roles', [RoleController::class, 'assignRoles']);
});
