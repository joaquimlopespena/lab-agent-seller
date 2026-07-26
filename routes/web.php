<?php

use App\Http\Controllers\Admin\PipedriveConnectionController;
use App\Http\Controllers\Admin\StartPipedriveSyncController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PipedriveOAuthController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('customer')->name('customer.')->group(function () {
    Route::inertia('access', 'customer/access/request')
        ->name('access.request');
    Route::inertia('companies', 'customer/companies/index')
        ->name('companies.index');
    Route::inertia('chat', 'customer/chat/index')
        ->name('chat.index');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('integrations', PipedriveConnectionController::class)
            ->name('integrations.index');
        Route::post('integrations/pipedrive/synchronize', StartPipedriveSyncController::class)
            ->name('integrations.pipedrive.synchronize');
        Route::inertia('synced-data', 'admin/synced-data/index')
            ->name('synced-data.index');
        Route::inertia('conversations', 'admin/conversations/index')
            ->name('conversations.index');
    });

    Route::get('oauth/pipedrive', [PipedriveOAuthController::class, 'start'])
        ->name('oauth.pipedrive.start');
    Route::get('oauth/pipedrive/callback', [PipedriveOAuthController::class, 'callback'])
        ->name('oauth.pipedrive.callback');
});

require __DIR__.'/settings.php';
