<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Seller Routes
|--------------------------------------------------------------------------
*/

Route::prefix('seller')->name('seller.')->group(function () {
    // Guest routes
    Route::middleware(['guest', 'throttle:10,1'])->group(function () {
        Route::get('/login', [App\Http\Controllers\Seller\Auth\LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [App\Http\Controllers\Seller\Auth\LoginController::class, 'login']);
    });

    // Authenticated seller routes
    Route::middleware(['auth', 'seller'])->group(function () {
        Route::post('/logout', [App\Http\Controllers\Seller\Auth\LoginController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/', [App\Http\Controllers\Seller\DashboardController::class, 'index'])->name('dashboard');

        // Notifications
        Route::get('/notifications', [App\Http\Controllers\Seller\NotificationController::class, 'index'])->name('notifications');

        // Help Center
        Route::get('/help', [App\Http\Controllers\Seller\HelpController::class, 'index'])->name('help');
        Route::get('/help/contact', [App\Http\Controllers\Seller\HelpController::class, 'contact'])->name('help.contact');
        Route::post('/help/contact', [App\Http\Controllers\Seller\HelpController::class, 'submitContact'])->name('help.contact.submit');

        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [App\Http\Controllers\Seller\OrderController::class, 'index'])->name('index');
            Route::get('/{order}', [App\Http\Controllers\Seller\OrderController::class, 'show'])->name('show');
            Route::put('/{order}/status', [App\Http\Controllers\Seller\OrderController::class, 'updateStatus'])->name('update-status');
        });

        // Returns
        Route::prefix('returns')->name('returns.')->group(function () {
            Route::get('/', [App\Http\Controllers\Seller\ReturnController::class, 'index'])->name('index');
            Route::get('/{return}', [App\Http\Controllers\Seller\ReturnController::class, 'show'])->name('show');
            Route::put('/{return}/status', [App\Http\Controllers\Seller\ReturnController::class, 'updateStatus'])->name('status');
        });

        // Products
        Route::resource('products', App\Http\Controllers\Seller\ProductController::class);
        Route::post('/products/{product}/duplicate', [App\Http\Controllers\Seller\ProductController::class, 'duplicate'])->name('products.duplicate');
        Route::post('/products/bulk-action', [App\Http\Controllers\Seller\ProductController::class, 'bulkAction'])->name('products.bulk-action');
        Route::post('/products/import', [App\Http\Controllers\Seller\ProductController::class, 'import'])->name('products.import');
        Route::get('/products-export', [App\Http\Controllers\Seller\ProductController::class, 'export'])->name('products.export');

        // Inventory
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', [App\Http\Controllers\Seller\InventoryController::class, 'index'])->name('index');
            Route::get('/low-stock', [App\Http\Controllers\Seller\InventoryController::class, 'lowStock'])->name('low-stock');
            Route::put('/{product}/stock', [App\Http\Controllers\Seller\InventoryController::class, 'updateStock'])->name('update-stock');
        });

        // Promotions
        Route::resource('promotions', App\Http\Controllers\Seller\PromotionController::class);

        // Coupons
        Route::resource('coupons', App\Http\Controllers\Seller\CouponController::class);

        // Reviews
        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::get('/', [App\Http\Controllers\Seller\ReviewController::class, 'index'])->name('index');
            Route::get('/{review}', [App\Http\Controllers\Seller\ReviewController::class, 'show'])->name('show');
            Route::post('/{review}/respond', [App\Http\Controllers\Seller\ReviewController::class, 'respond'])->name('respond');
        });

        // Q&A
        Route::prefix('questions')->name('questions.')->group(function () {
            Route::get('/', [App\Http\Controllers\Seller\QuestionController::class, 'index'])->name('index');
            Route::get('/{question}', [App\Http\Controllers\Seller\QuestionController::class, 'show'])->name('show');
            Route::post('/{question}/answer', [App\Http\Controllers\Seller\QuestionController::class, 'answer'])->name('answer');
        });

        // Messages
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [App\Http\Controllers\Seller\MessageController::class, 'index'])->name('index');
            Route::get('/{conversation}', [App\Http\Controllers\Seller\MessageController::class, 'show'])->name('show');
            Route::post('/{conversation}/reply', [App\Http\Controllers\Seller\MessageController::class, 'reply'])->name('reply');
        });

        // Earnings
        Route::prefix('earnings')->name('earnings.')->group(function () {
            Route::get('/', [App\Http\Controllers\Seller\EarningsController::class, 'index'])->name('index');
            Route::get('/statement/{month?}', [App\Http\Controllers\Seller\EarningsController::class, 'statement'])->name('statement');
        });

        // Payouts
        Route::prefix('payouts')->name('payouts.')->group(function () {
            Route::get('/', [App\Http\Controllers\Seller\PayoutController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Seller\PayoutController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Seller\PayoutController::class, 'store'])->name('store');
            Route::get('/{payout}', [App\Http\Controllers\Seller\PayoutController::class, 'show'])->name('show');
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/sales', [App\Http\Controllers\Seller\ReportController::class, 'sales'])->name('sales');
            Route::get('/products', [App\Http\Controllers\Seller\ReportController::class, 'products'])->name('products');
            Route::get('/traffic', [App\Http\Controllers\Seller\ReportController::class, 'traffic'])->name('traffic');
            Route::get('/export/{type}', [App\Http\Controllers\Seller\ReportController::class, 'export'])->name('export');
        });

        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [App\Http\Controllers\Seller\SettingsController::class, 'index'])->name('index');
            Route::put('/profile', [App\Http\Controllers\Seller\SettingsController::class, 'updateProfile'])->name('profile');
            Route::put('/payout', [App\Http\Controllers\Seller\SettingsController::class, 'updatePayout'])->name('payout');
            Route::put('/notifications', [App\Http\Controllers\Seller\SettingsController::class, 'updateNotifications'])->name('notifications');
        });
    });
});
