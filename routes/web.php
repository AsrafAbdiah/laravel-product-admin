<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\ProductController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/products');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Declared before the resource routes so "bulk", "export" and "trash" are not mistaken for a product id.
    Route::delete('/products/bulk', [ProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::get('/products/trash', [ProductController::class, 'trash'])->name('products.trash');

    // withTrashed() lets route model binding find soft-deleted products.
    Route::patch('/products/{product}/restore', [ProductController::class, 'restore'])->withTrashed()->name('products.restore');
    Route::delete('/products/{product}/force', [ProductController::class, 'forceDestroy'])->withTrashed()->name('products.force-destroy');

    Route::resource('products', ProductController::class);

    Route::resource('categories', CategoryController::class)->except('show');
});
