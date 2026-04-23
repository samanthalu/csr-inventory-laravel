<?php

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\ProductController;
    use App\Http\Controllers\User\UserController;

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/register',     [UserController::class, 'create']);
        Route::get('/get-users',     [UserController::class, 'getUsers']);
        Route::get('/get-user',      [UserController::class, 'getUser']);
        Route::post('/edit-user',    [UserController::class, 'editUser']);
        Route::get('/delete-user',   [UserController::class, 'deleteUser']);
    });

    // Re-auth popup — any authenticated user may verify their own credentials
    Route::post('/auth-user', [UserController::class, 'authUser'])->middleware(['auth:sanctum']);