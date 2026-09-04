<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Banner\BannerController;
use App\Http\Controllers\Api\V1\Brand\BrandController;
use App\Http\Controllers\Api\V1\Cart\CartController;
use App\Http\Controllers\Api\V1\Category\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Order\OrderController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\Product\ProductController;
use App\Http\Controllers\Api\V1\RecommendationController;
use App\Http\Controllers\Api\V1\Review\ReviewController;
use App\Http\Controllers\Api\V1\Search\SearchController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\User\AddressController;
use App\Http\Controllers\Api\V1\User\PreferenceController;
use App\Http\Controllers\Api\V1\User\WishlistController;
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
Route::prefix('webhook')->middleware([VerifyMetaWebhookSignature::class, 'throttle:60,1,meta-webhook'])->group(function () {
    Route::get('meta', [WebhookController::class, 'verify'])->name('webhook.meta.verify');
    Route::post('meta', [WebhookController::class, 'handle'])->name('webhook.meta.handle');
});

// API Version 1
Route::prefix('v1')->name('api.v1.')->group(function () {

    // Public authentication routes
    Route::prefix('auth')->name('auth.')->middleware('throttle:10,1,api-auth')->group(function () {
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
        Route::apiResource('addresses', AddressController::class);

        // Wishlist
        Route::prefix('wishlist')->name('wishlist.')->group(function () {
            Route::get('/', [WishlistController::class, 'index'])->name('index');
            Route::post('{product}', [WishlistController::class, 'store'])->name('store');
            Route::delete('{product}', [WishlistController::class, 'destroy'])->name('destroy');
        });

        // Cart
        Route::prefix('cart')->name('cart.')->group(function () {
            Route::get('/', [CartController::class, 'index'])->name('index');
            Route::post('items', [CartController::class, 'addItem'])->name('add');
            Route::put('items/{cartItem}', [CartController::class, 'updateItem'])->name('update');
            Route::delete('items/{cartItem}', [CartController::class, 'removeItem'])->name('remove');
            Route::delete('/', [CartController::class, 'clear'])->name('clear');
        });

        // Orders
        Route::apiResource('orders', OrderController::class)->only(['index', 'show']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

        // Reviews
        Route::apiResource('reviews', ReviewController::class);

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::put('{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
            Route::put('read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        });

        // Checkout (rate-limited to prevent abuse)
        Route::prefix('checkout')->name('checkout.')->group(function () {
            Route::post('validate', [CheckoutController::class, 'validate'])->middleware('throttle:10,1,api-checkout-validate')->name('validate');
            Route::post('/', [CheckoutController::class, 'process'])->middleware('throttle:5,1,api-checkout')->name('process');
        });

        // User Preferences
        Route::prefix('preferences')->name('preferences.')->group(function () {
            Route::get('/', [PreferenceController::class, 'show'])->name('show');
            Route::put('/', [PreferenceController::class, 'update'])->name('update');
        });

        // Recommendations (authenticated)
        Route::get('recommendations/recently-viewed', [RecommendationController::class, 'recentlyViewed'])->name('recommendations.recently-viewed');
        Route::get('recommendations/personalized', [RecommendationController::class, 'personalized'])->name('recommendations.personalized');
    });

    // Public routes

    // Products
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('featured', [ProductController::class, 'featured'])->name('featured');
        Route::get('bestsellers', [ProductController::class, 'bestsellers'])->name('bestsellers');
        Route::get('new-arrivals', [ProductController::class, 'newArrivals'])->name('new-arrivals');
        Route::get('{product:slug}', [ProductController::class, 'show'])->name('show');
        Route::get('{product}/reviews', [ProductController::class, 'reviews'])->name('reviews');
        Route::get('{product}/questions', [ProductController::class, 'questions'])->name('questions');
    });

    // Categories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('tree', [CategoryController::class, 'tree'])->name('tree');
        Route::get('{category:slug}', [CategoryController::class, 'show'])->name('show');
        Route::get('{category:slug}/products', [CategoryController::class, 'products'])->name('products');
    });

    // Brands
    Route::apiResource('brands', BrandController::class)->only(['index', 'show']);

    // Banners (homepage/storefront artwork)
    // Only what a shopper should be seeing right now: the controller reads the
    // model's `visible` scope, so an inactive, not-yet-started or finished
    // banner is never handed to a client that would happily cache it.
    Route::get('banners', [BannerController::class, 'index'])->name('banners.index');

    // Search
    Route::get('search', [SearchController::class, 'search'])->name('search');
    Route::get('search/suggestions', [SearchController::class, 'suggestions'])->name('search.suggestions');

    // Pages (CMS)
    Route::get('pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');

    // Settings (public)
    Route::get('settings/public', [SettingController::class, 'public'])->name('settings.public');

    // Home (aggregated mobile endpoint)
    Route::get('home', [HomeController::class, 'index'])->name('home');

    // Recommendations (public)
    Route::get('recommendations/popular', [RecommendationController::class, 'popular'])->name('recommendations.popular');
    // whereNumber: the controller type-hints int $productId, so a non-numeric
    // id reached a TypeError and answered 500 instead of 404.
    Route::get('recommendations/similar/{productId}', [RecommendationController::class, 'similar'])->whereNumber('productId')->name('recommendations.similar');
});
