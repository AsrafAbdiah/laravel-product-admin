<?php

use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\SettingsController;
use Illuminate\Support\Facades\Route;

// Login, logout, sign-up, password reset, email verification and two-factor
// routes come from Laravel Fortify (see config/fortify.php).

Route::redirect('/', '/dashboard');

// Signed-in, verified members of a team. What each person may do on these
// pages is decided by policies, from their role in the current team.
Route::middleware(['auth', 'verified', 'team'])->group(function () {
    Route::get('/settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Declared before the resource routes so "bulk", "export" and "trash" are not mistaken for a product id.
    Route::delete('/products/bulk', [ProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::get('/products/trash', [ProductController::class, 'trash'])->name('products.trash');
    Route::get('/products/import', [ProductController::class, 'importForm'])->name('products.import');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import.store');
    Route::get('/products/import/template', [ProductController::class, 'importTemplate'])->name('products.import.template');

    // withTrashed() lets route model binding find soft-deleted products.
    Route::patch('/products/{product}/restore', [ProductController::class, 'restore'])->withTrashed()->name('products.restore');
    Route::delete('/products/{product}/force', [ProductController::class, 'forceDestroy'])->withTrashed()->name('products.force-destroy');

    Route::resource('products', ProductController::class);

    Route::resource('categories', CategoryController::class)->except('show');
});
