<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Route::middleware(['auth:sanctum'])->post('/testme', [ProductController::class, 'store'])->name('testme');

// Route::middleware(['auth:sanctum'])->get('/getname', function (Request $request) {
//     return 'samanthalu';
// });

include 'api/auth.php';
include 'api/product.php';