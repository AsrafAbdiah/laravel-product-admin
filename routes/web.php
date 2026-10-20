<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Web\ProductController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/products');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Declared before the resource routes so "bulk" and "export" are not mistaken for a product id.
    Route::delete('/products/bulk', [ProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');

    Route::resource('products', ProductController::class);
});
