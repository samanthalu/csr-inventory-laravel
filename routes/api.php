<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    $user = $request->user();
    $data = $user->toArray();
    // expose legacy bitfield as 'permissions' for Angular backward compat
    $data['permissions'] = $user->legacy_permissions ?? 0;
    return array_merge($data, [
        'roles'           => $user->getRoleNames(),
        'all_permissions' => $user->getAllPermissions()->pluck('name')->values(),
    ]);
});

// Route::middleware(['auth:sanctum'])->post('/testme', [ProductController::class, 'store'])->name('testme');

// Route::middleware(['auth:sanctum'])->get('/getname', function (Request $request) {
//     return 'samanthalu';
// });

include 'api/auth.php';
include 'api/roles.php';
include 'api/product.php';
include 'api/hire.php';
include 'api/maintenance.php';
include 'api/disposal.php';
include 'api/reports.php';
include 'api/notifications.php';
include 'api/audit-logs.php';
include 'api/fieldwork.php';