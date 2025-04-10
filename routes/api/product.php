<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Product\AccessoriesController;
use App\Http\Controllers\File\ProductFileController;
use App\Http\Controllers\Product\CategoryController;
use App\Http\Controllers\Product\SupplierController;
use App\Http\Controllers\Software\SoftwareController;
use App\Http\Controllers\Staff\StaffController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/products', [ProductController::class, 'getProducts']);
    Route::post('/products/add-product', [ProductController::class, 'addProduct']);
    Route::put('/products/edit-product', [ProductController::class, 'updateProduct']);
    Route::delete('/product/delete-product/{id}', [ProductController::class, 'deleteProduct']);
    Route::get('/product/{id}', [ProductController::class, 'getProductById']);
});

Route::prefix('accessories')->group(function () {
    Route::post('/', [AccessoriesController::class, 'store']);
    Route::get('/{productId}', [AccessoriesController::class, 'index']);
    Route::get('/show/{id}', [AccessoriesController::class, 'show']);
    Route::put('/{id}', [AccessoriesController::class, 'update']);
    Route::delete('/{id}', [AccessoriesController::class, 'destroy']);
})->middleware(['auth:sanctum']);

// manage files

Route::prefix('products/{product}')->group(function () {
    // File routes
    Route::get('files', [ProductFileController::class, 'index']);
    Route::post('files', [ProductFileController::class, 'store']);
    
    // Direct file deletion
    Route::delete('files/{id}', [ProductFileController::class, 'destroy']);
})->middleware(['auth:sanctum']);


/*
GET	/items	index() (List all items)
POST	/items	store() (Create a new item)
GET	/items/{id}	show() (Retrieve a single item)
PUT/PATCH	/items/{id}	update() (Update an item)
DELETE	/items/{id}	destroy() (Delete an item)
*/

// Suppliers
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('suppliers', SupplierController::class);
});

// Categories
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategoryController::class);
});


// Software
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('softwares', SoftwareController::class);
});

// staff
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('staff', StaffController::class);
});