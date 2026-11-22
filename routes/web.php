<?php

use App\Http\Controllers\Web\ActivityController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ExportController;
use App\Http\Controllers\Web\InvitationController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\TeamController;
use Illuminate\Support\Facades\Route;

// Login, logout, sign-up, password reset, email verification and two-factor
// routes come from Laravel Fortify (see config/fortify.php).

Route::redirect('/', '/dashboard');

// Signed-in, verified members of a team. What each person may do on these
// pages is decided by policies, from their role in the current team.
Route::middleware(['auth', 'verified', 'team'])->group(function () {
    Route::get('/settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');
    Route::get('/settings/sessions', [SettingsController::class, 'sessions'])->name('settings.sessions');
    Route::delete('/settings/sessions', [SettingsController::class, 'logoutOtherSessions'])->name('settings.sessions.destroy');
    Route::get('/settings/tokens', [SettingsController::class, 'tokens'])->name('settings.tokens');
    Route::post('/settings/tokens', [SettingsController::class, 'storeToken'])->name('settings.tokens.store');
    Route::delete('/settings/tokens/{tokenId}', [SettingsController::class, 'destroyToken'])->whereNumber('tokenId')->name('settings.tokens.destroy');

    // The current team: members, roles and invitations.
    Route::get('/team', [TeamController::class, 'show'])->name('team.show');
    Route::put('/team', [TeamController::class, 'update'])->name('team.update');
    Route::put('/team/plan', [TeamController::class, 'updatePlan'])->name('team.plan.update');
    Route::post('/team/invitations', [TeamController::class, 'invite'])->name('team.invitations.store');
    Route::delete('/team/invitations/{invitation}', [TeamController::class, 'cancelInvitation'])->name('team.invitations.destroy');
    Route::patch('/team/members/{member}', [TeamController::class, 'updateMember'])->name('team.members.update');
    Route::delete('/team/members/{member}', [TeamController::class, 'removeMember'])->name('team.members.destroy');
    Route::post('/teams/{team}/switch', [TeamController::class, 'switch'])->name('teams.switch');

    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');

    Route::get('/exports/{fileId}', [ExportController::class, 'download'])->whereUuid('fileId')->name('exports.download');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{id}', [NotificationController::class, 'show'])->whereUuid('id')->name('notifications.show');

    // Invitation links from emails.
    Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
    Route::post('/invitations/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');

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
