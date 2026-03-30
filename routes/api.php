<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Middleware\VerifyMetaWebhookSignature;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ─── Meta Webhooks (Nia AI Chatbot) ─────────────────────────────────────
Route::prefix('webhook')->middleware([VerifyMetaWebhookSignature::class, 'throttle:60,1'])->group(function () {
    Route::get('meta', [WebhookController::class, 'verify'])->name('webhook.meta.verify');
    Route::post('meta', [WebhookController::class, 'handle'])->name('webhook.meta.handle');
});

// API Version 1
Route::prefix('v1')->name('api.v1.')->group(function () {

    // Public authentication routes
    Route::prefix('auth')->name('auth.')->middleware('throttle:10,1')->group(function () {
        Route::post('register', RegisterController::class)->name('register');
        Route::post('login', LoginController::class)->name('login');
    });

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {

        // Auth routes
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('logout', LogoutController::class)->name('logout');
            Route::post('logout-all', [LogoutController::class, 'logoutAll'])->name('logout-all');
        });

        // Profile routes
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])->name('show');
            Route::put('/', [ProfileController::class, 'update'])->name('update');
            Route::put('password', [ProfileController::class, 'updatePassword'])->name('password');
            Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
        });

        // User addresses
        Route::apiResource('addresses', \App\Http\Controllers\Api\V1\User\AddressController::class);

        // Wishlist
        Route::prefix('wishlist')->name('wishlist.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\User\WishlistController::class, 'index'])->name('index');
            Route::post('{product}', [\App\Http\Controllers\Api\V1\User\WishlistController::class, 'store'])->name('store');
            Route::delete('{product}', [\App\Http\Controllers\Api\V1\User\WishlistController::class, 'destroy'])->name('destroy');
        });

        // Cart
        Route::prefix('cart')->name('cart.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Cart\CartController::class, 'index'])->name('index');
            Route::post('items', [\App\Http\Controllers\Api\V1\Cart\CartController::class, 'addItem'])->name('add');
            Route::put('items/{cartItem}', [\App\Http\Controllers\Api\V1\Cart\CartController::class, 'updateItem'])->name('update');
            Route::delete('items/{cartItem}', [\App\Http\Controllers\Api\V1\Cart\CartController::class, 'removeItem'])->name('remove');
            Route::delete('/', [\App\Http\Controllers\Api\V1\Cart\CartController::class, 'clear'])->name('clear');
        });

        // Orders
        Route::apiResource('orders', \App\Http\Controllers\Api\V1\Order\OrderController::class)->only(['index', 'show']);
        Route::post('orders/{order}/cancel', [\App\Http\Controllers\Api\V1\Order\OrderController::class, 'cancel'])->name('orders.cancel');

        // Reviews
        Route::apiResource('reviews', \App\Http\Controllers\Api\V1\Review\ReviewController::class);

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index'])->name('index');
            Route::put('{notification}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead'])->name('read');
            Route::put('read-all', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAllAsRead'])->name('read-all');
        });

        // Checkout (rate-limited to prevent abuse)
        Route::prefix('checkout')->name('checkout.')->group(function () {
            Route::post('validate', [\App\Http\Controllers\Api\V1\CheckoutController::class, 'validate'])->middleware('throttle:10,1')->name('validate');
            Route::post('/', [\App\Http\Controllers\Api\V1\CheckoutController::class, 'process'])->middleware('throttle:5,1')->name('process');
        });

        // User Preferences
        Route::prefix('preferences')->name('preferences.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\User\PreferenceController::class, 'show'])->name('show');
            Route::put('/', [\App\Http\Controllers\Api\V1\User\PreferenceController::class, 'update'])->name('update');
        });

        // Recommendations (authenticated)
        Route::get('recommendations/recently-viewed', [\App\Http\Controllers\Api\V1\RecommendationController::class, 'recentlyViewed'])->name('recommendations.recently-viewed');
        Route::get('recommendations/personalized', [\App\Http\Controllers\Api\V1\RecommendationController::class, 'personalized'])->name('recommendations.personalized');
    });

    // Public routes

    // Products
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Product\ProductController::class, 'index'])->name('index');
        Route::get('featured', [\App\Http\Controllers\Api\V1\Product\ProductController::class, 'featured'])->name('featured');
        Route::get('bestsellers', [\App\Http\Controllers\Api\V1\Product\ProductController::class, 'bestsellers'])->name('bestsellers');
        Route::get('new-arrivals', [\App\Http\Controllers\Api\V1\Product\ProductController::class, 'newArrivals'])->name('new-arrivals');
        Route::get('{product:slug}', [\App\Http\Controllers\Api\V1\Product\ProductController::class, 'show'])->name('show');
        Route::get('{product}/reviews', [\App\Http\Controllers\Api\V1\Product\ProductController::class, 'reviews'])->name('reviews');
        Route::get('{product}/questions', [\App\Http\Controllers\Api\V1\Product\ProductController::class, 'questions'])->name('questions');
    });

    // Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Category\CategoryController::class, 'index'])->name('index');
        Route::get('tree', [\App\Http\Controllers\Api\V1\Category\CategoryController::class, 'tree'])->name('tree');
        Route::get('{category:slug}', [\App\Http\Controllers\Api\V1\Category\CategoryController::class, 'show'])->name('show');
        Route::get('{category:slug}/products', [\App\Http\Controllers\Api\V1\Category\CategoryController::class, 'products'])->name('products');
    });

    // Brands
    Route::apiResource('brands', \App\Http\Controllers\Api\V1\Brand\BrandController::class)->only(['index', 'show']);

    // Search
    Route::get('search', [\App\Http\Controllers\Api\V1\Search\SearchController::class, 'search'])->name('search');
    Route::get('search/suggestions', [\App\Http\Controllers\Api\V1\Search\SearchController::class, 'suggestions'])->name('search.suggestions');

    // Sellers (public storefront)
    Route::get('sellers/{seller:slug}', [\App\Http\Controllers\Api\V1\Seller\SellerController::class, 'show'])->name('sellers.show');
    Route::get('sellers/{seller:slug}/products', [\App\Http\Controllers\Api\V1\Seller\SellerController::class, 'products'])->name('sellers.products');

    // Pages (CMS)
    Route::get('pages/{page:slug}', [\App\Http\Controllers\Api\V1\PageController::class, 'show'])->name('pages.show');

    // Settings (public)
    Route::get('settings/public', [\App\Http\Controllers\Api\V1\SettingController::class, 'public'])->name('settings.public');

    // Home (aggregated mobile endpoint)
    Route::get('home', [\App\Http\Controllers\Api\V1\HomeController::class, 'index'])->name('home');

    // Recommendations (public)
    Route::get('recommendations/popular', [\App\Http\Controllers\Api\V1\RecommendationController::class, 'popular'])->name('recommendations.popular');
    Route::get('recommendations/similar/{productId}', [\App\Http\Controllers\Api\V1\RecommendationController::class, 'similar'])->name('recommendations.similar');
});
