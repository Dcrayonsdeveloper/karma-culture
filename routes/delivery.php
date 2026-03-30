<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Delivery Partner Routes
|--------------------------------------------------------------------------
*/

Route::prefix('delivery')->name('delivery.')->group(function () {
    // Guest routes
    Route::middleware(['guest:delivery', 'throttle:10,1'])->group(function () {
        Route::get('/login', [App\Http\Controllers\Delivery\Auth\LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [App\Http\Controllers\Delivery\Auth\LoginController::class, 'login']);
    });

    // Authenticated delivery partner routes
    Route::middleware(['auth:delivery', 'delivery'])->group(function () {
        Route::post('/logout', [App\Http\Controllers\Delivery\Auth\LoginController::class, 'logout'])->name('logout');

        // Documents (accessible even when unverified)
        Route::get('/documents', [App\Http\Controllers\Delivery\ProfileController::class, 'documents'])->name('documents');
        Route::post('/documents', [App\Http\Controllers\Delivery\ProfileController::class, 'uploadDocuments'])->name('documents.upload');

        // Dashboard
        Route::get('/', [App\Http\Controllers\Delivery\DashboardController::class, 'index'])->name('dashboard');

        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/{order}', [App\Http\Controllers\Delivery\OrderController::class, 'show'])->name('show');
            Route::put('/{order}/status', [App\Http\Controllers\Delivery\OrderController::class, 'updateStatus'])->name('update-status');
            Route::post('/{order}/collect-payment', [App\Http\Controllers\Delivery\OrderController::class, 'collectPayment'])->name('collect-payment');
        });

        // Returns
        Route::prefix('returns')->name('returns.')->group(function () {
            Route::get('/', [App\Http\Controllers\Delivery\ReturnController::class, 'index'])->name('index');
            Route::get('/{return}', [App\Http\Controllers\Delivery\ReturnController::class, 'show'])->name('show');
            Route::put('/{return}/status', [App\Http\Controllers\Delivery\ReturnController::class, 'updateStatus'])->name('update-status');
        });
    });
});
