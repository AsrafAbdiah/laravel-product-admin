<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// Public: limited to 5 attempts per minute to slow down brute-force attacks.
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('api.login');

// Everything else requires a valid Sanctum token AND an admin user.
Route::middleware(['auth:sanctum', 'admin'])->name('api.')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::apiResource('categories', CategoryController::class);

    // Declared before the resource routes so "bulk", "export" and "trash" are not mistaken for a product id.
    Route::delete('/products/bulk', [ProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::get('/products/trash', [ProductController::class, 'trash'])->name('products.trash');

    // withTrashed() lets route model binding find soft-deleted products.
    Route::patch('/products/{product}/restore', [ProductController::class, 'restore'])->withTrashed()->name('products.restore');
    Route::delete('/products/{product}/force', [ProductController::class, 'forceDestroy'])->withTrashed()->name('products.force-destroy');

    Route::apiResource('products', ProductController::class);
});
