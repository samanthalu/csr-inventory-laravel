<?php

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\ProductController;
    use App\Http\Controllers\User\UserController;

    Route::post('/register', [UserController::class, 'create']);
    Route::get('/get-users', [UserController::class, 'getUsers'])->middleware(['auth:sanctum']);
    Route::get('/get-user', [UserController::class, 'getUser'])->middleware(['auth:sanctum']);
    Route::post('/edit-user', [UserController::class, 'editUser'])->middleware(['auth:sanctum']);
    Route::post('/auth-user', [UserController::class, 'authUser'])->middleware(['auth:sanctum']);
    Route::get('/delete-user', [UserController::class, 'deleteUser'])->middleware(['auth:sanctum']);