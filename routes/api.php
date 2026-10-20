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

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    // Declared before the resource routes so "bulk" and "export" are not mistaken for a product id.
    Route::delete('/products/bulk', [ProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');

    Route::apiResource('products', ProductController::class);
});
