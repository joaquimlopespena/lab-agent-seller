<?php

use App\Http\Controllers\DashboardController;
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
        Route::inertia('integrations', 'admin/integrations/index')
            ->name('integrations.index');
        Route::inertia('synced-data', 'admin/synced-data/index')
            ->name('synced-data.index');
        Route::inertia('conversations', 'admin/conversations/index')
            ->name('conversations.index');
    });
});

require __DIR__.'/settings.php';
