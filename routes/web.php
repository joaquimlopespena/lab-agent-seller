<?php

use App\Http\Controllers\Admin\PipedriveConnectionController;
use App\Http\Controllers\Admin\StartPipedriveSyncController;
use App\Http\Controllers\Admin\SyncedDataController;
use App\Http\Controllers\Customer\ConsumeCustomerMagicLinkController;
use App\Http\Controllers\Customer\CustomerAccessConfirmationController;
use App\Http\Controllers\Customer\CustomerCompanyController;
use App\Http\Controllers\Customer\CustomerMagicLinkController;
use App\Http\Controllers\Customer\EndCustomerSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PipedriveOAuthController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('access', [CustomerMagicLinkController::class, 'create'])
        ->name('access.request');
    Route::post('access', [CustomerMagicLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('access.store');
    Route::get('access/confirmation', CustomerAccessConfirmationController::class)
        ->middleware('customer.auth')
        ->name('access.confirmation');
    Route::get('access/{token}', ConsumeCustomerMagicLinkController::class)
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('access.consume');
    Route::middleware('customer.auth')->group(function () {
        Route::delete('session', EndCustomerSessionController::class)
            ->name('session.destroy');
        Route::get('companies', [CustomerCompanyController::class, 'index'])
            ->name('companies.index');
        Route::post('companies', [CustomerCompanyController::class, 'store'])
            ->name('companies.store');
        Route::middleware('customer.tenant')->group(function () {
            Route::inertia('chat', 'customer/chat/index')
                ->name('chat.index');
        });
    });
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('integrations', PipedriveConnectionController::class)
            ->name('integrations.index');
        Route::post('integrations/pipedrive/synchronize', StartPipedriveSyncController::class)
            ->name('integrations.pipedrive.synchronize');
        Route::get('synced-data/{category?}', [SyncedDataController::class, 'index'])
            ->where('category', '[a-z-]+')
            ->name('synced-data.index');
        Route::get('synced-data/{category}/{record}', [SyncedDataController::class, 'show'])
            ->where(['category' => '[a-z-]+', 'record' => '[0-9]+'])
            ->name('synced-data.show');
        Route::inertia('conversations', 'admin/conversations/index')
            ->name('conversations.index');
    });

    Route::get('oauth/pipedrive', [PipedriveOAuthController::class, 'start'])
        ->name('oauth.pipedrive.start');
    Route::get('oauth/pipedrive/callback', [PipedriveOAuthController::class, 'callback'])
        ->name('oauth.pipedrive.callback');
});

require __DIR__.'/settings.php';
