<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// CSRF Token Refresh (for long-lived POS sessions)
Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]))->name('csrf-token');

// Dynamic robots.txt (uses APP_URL so domain changes are automatic)
Route::get('/robots.txt', function () {
    $sitemap = url('/sitemap.xml');
    $content = "User-agent: *\n";
    $content .= "Disallow: /admin/\n";
    $content .= "Disallow: /seller/\n";
    $content .= "Disallow: /pos/\n";
    $content .= "Disallow: /cart\n";
    $content .= "Disallow: /checkout\n";
    $content .= "Disallow: /account/\n";
    $content .= "Disallow: /api/\n\n";
    $content .= "Sitemap: {$sitemap}\n";
    return response($content, 200)->header('Content-Type', 'text/plain');
});

// XML Sitemap
Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-pages.xml', [App\Http\Controllers\SitemapController::class, 'pages']);
Route::get('/sitemap-products.xml', [App\Http\Controllers\SitemapController::class, 'products']);
Route::get('/sitemap-categories.xml', [App\Http\Controllers\SitemapController::class, 'categories']);
Route::get('/sitemap-blog.xml', [App\Http\Controllers\SitemapController::class, 'blog']);

// Storefront Routes
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Products
Route::prefix('products')->name('products.')->group(function () {
    // All-products index page removed — CTAs now point to the home page.
    Route::get('/{product:slug}', [App\Http\Controllers\ProductController::class, 'show'])->name('show');
});

// Alias for product show
Route::get('/product/{product:slug}', [App\Http\Controllers\ProductController::class, 'show'])->name('product.show');

// Quick View (AJAX)
Route::get('/product/{product}/quick-view', [App\Http\Controllers\ProductController::class, 'quickView'])->name('product.quick-view');

// Guest Reviews
Route::post('/products/{product}/guest-review', [App\Http\Controllers\GuestReviewController::class, 'store'])
    ->name('product.guest-review')
    ->middleware('throttle:3,60');

// Product Questions
Route::post('/products/{product}/ask-question', [App\Http\Controllers\ProductController::class, 'askQuestion'])
    ->name('product.ask-question')
    ->middleware('throttle:5,60');

// Back in Stock Notifications
Route::post('/products/{product}/notify-back-in-stock', [App\Http\Controllers\ProductController::class, 'notifyBackInStock'])
    ->name('product.notify-back-in-stock')
    ->middleware('throttle:5,60');

// Categories
Route::prefix('categories')->name('categories.')->group(function () {
    // Categories index page removed — the navbar shows a hover dropdown instead.
    Route::get('/{category:slug}', [App\Http\Controllers\CategoryController::class, 'show'])->name('show');
});

// Alias for category show
Route::get('/category/{category:slug}', [App\Http\Controllers\CategoryController::class, 'show'])->name('category.show');

// Brands
Route::prefix('brands')->name('brands.')->group(function () {
    Route::get('/', [App\Http\Controllers\BrandController::class, 'index'])->name('index');
    Route::get('/{brand:slug}', [App\Http\Controllers\BrandController::class, 'show'])->name('show');
});

// Sellers
Route::get('/sellers/{seller:slug}', [App\Http\Controllers\SellerController::class, 'show'])->name('sellers.show');

// Search
Route::get('/search', [App\Http\Controllers\SearchController::class, 'index'])->name('search');
Route::get('/search/suggestions', [App\Http\Controllers\SearchController::class, 'suggestions'])->name('search.suggestions');

// Special Pages
Route::get('/deals', [App\Http\Controllers\DealsController::class, 'index'])->name('deals');
Route::get('/new-arrivals', [App\Http\Controllers\ProductController::class, 'newArrivals'])->name('new-arrivals');
Route::get('/bestsellers', [App\Http\Controllers\ProductController::class, 'bestsellers'])->name('bestsellers');
Route::get('/wholesale', [App\Http\Controllers\WholesaleController::class, 'index'])->name('wholesale');

// Cart
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/data', [App\Http\Controllers\CartController::class, 'data'])->name('data');
    Route::get('/', [App\Http\Controllers\CartController::class, 'index'])->name('index');
    Route::post('/add', [App\Http\Controllers\CartController::class, 'add'])->name('add');
    Route::put('/{cartItem}', [App\Http\Controllers\CartController::class, 'update'])->name('update');
    Route::delete('/{cartItem}', [App\Http\Controllers\CartController::class, 'destroy'])->name('destroy');
    Route::delete('/', [App\Http\Controllers\CartController::class, 'clear'])->name('clear');
    Route::post('/apply-coupon', [App\Http\Controllers\CartController::class, 'applyCoupon'])->middleware('throttle:10,1')->name('apply-coupon');
    Route::delete('/remove-coupon', [App\Http\Controllers\CartController::class, 'removeCoupon'])->name('remove-coupon');
    Route::get('/recommendations', [App\Http\Controllers\CartController::class, 'recommendations'])->name('recommendations');
});

// Checkout — guest checkout (no authentication required).
// Order is placed without login; payment/shipping (Shiprocket) will be wired in later.
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [App\Http\Controllers\CheckoutController::class, 'index'])->name('index');
    Route::post('/process', [App\Http\Controllers\CheckoutController::class, 'process'])->middleware('throttle:10,1')->name('process');
    Route::get('/success/{order}', [App\Http\Controllers\CheckoutController::class, 'success'])->name('success');
    Route::get('/failed', [App\Http\Controllers\CheckoutController::class, 'failed'])->name('failed');
});

// Wishlist page — client-side (localStorage) wishlist, works for guests
Route::get('/wishlist', [App\Http\Controllers\WishlistController::class, 'index'])->name('wishlist');
// Product data for the favourited IDs (guest-accessible; used to render the wishlist page)
Route::get('/wishlist-items', [App\Http\Controllers\WishlistController::class, 'items'])->name('wishlist.items');

// Wishlist actions (require auth — legacy server wishlist, kept for logged-in sync if needed)
Route::middleware('auth')->prefix('wishlist')->name('wishlist.')->group(function () {
    Route::post('/{product}', [App\Http\Controllers\WishlistController::class, 'store'])->name('store');
    Route::delete('/{product}', [App\Http\Controllers\WishlistController::class, 'destroy'])->name('destroy');
});

// Guest Authentication Routes
Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
    Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);
    Route::get('/password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update');
});

// Authenticated User Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

    // Email Verification
    Route::get('/email/verify', [App\Http\Controllers\Auth\VerificationController::class, 'show'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [App\Http\Controllers\Auth\VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/resend', [App\Http\Controllers\Auth\VerificationController::class, 'resend'])->name('verification.resend');

    // PayU Payment Gateway (initiate requires auth)
    Route::get('/payu/initiate/{order}', [App\Http\Controllers\PayUController::class, 'initiate'])->name('payu.initiate');

    // Account Routes
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [App\Http\Controllers\Account\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [App\Http\Controllers\Account\ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [App\Http\Controllers\Account\ProfileController::class, 'update'])->name('profile.update');
        Route::put('/password', [App\Http\Controllers\Account\ProfileController::class, 'updatePassword'])->name('password.update');

        // Addresses
        Route::resource('addresses', App\Http\Controllers\Account\AddressController::class);

        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [App\Http\Controllers\Account\OrderController::class, 'index'])->name('index');
            Route::get('/{order}', [App\Http\Controllers\Account\OrderController::class, 'show'])->name('show');
            Route::post('/{order}/cancel', [App\Http\Controllers\Account\OrderController::class, 'cancel'])->name('cancel');
            Route::get('/{order}/invoice', [App\Http\Controllers\Account\OrderController::class, 'invoice'])->name('invoice');
            Route::get('/{order}/track', [App\Http\Controllers\Account\OrderController::class, 'track'])->name('track');
            Route::post('/{order}/reorder', [App\Http\Controllers\Account\OrderController::class, 'reorder'])->name('reorder');
        });

        // Returns
        Route::resource('returns', App\Http\Controllers\Account\ReturnController::class);

        // Reviews
        Route::get('/reviews', [App\Http\Controllers\Account\ReviewController::class, 'index'])->name('reviews');
        Route::get('/reviews/create/{product}', [App\Http\Controllers\Account\ReviewController::class, 'create'])->name('reviews.create');
        Route::post('/reviews/{product}', [App\Http\Controllers\Account\ReviewController::class, 'store'])->name('reviews.store');

        // Support Tickets
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', [App\Http\Controllers\Account\TicketController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Account\TicketController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Account\TicketController::class, 'store'])->name('store');
            Route::get('/{ticket}', [App\Http\Controllers\Account\TicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [App\Http\Controllers\Account\TicketController::class, 'reply'])->name('reply');
        });

        // Notifications
        Route::get('/notifications', [App\Http\Controllers\Account\NotificationController::class, 'index'])->name('notifications');

        // Notification Preferences
        Route::get('/notification-preferences', [App\Http\Controllers\Account\NotificationPreferenceController::class, 'edit'])->name('notification-preferences');
        Route::put('/notification-preferences', [App\Http\Controllers\Account\NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');

        // Become a Delivery Partner
        Route::get('/become-delivery-partner', [App\Http\Controllers\Account\DeliveryPartnerRegistrationController::class, 'create'])->name('become-delivery-partner');
        Route::post('/become-delivery-partner', [App\Http\Controllers\Account\DeliveryPartnerRegistrationController::class, 'store'])->name('become-delivery-partner.store');
        Route::post('/become-delivery-partner/documents', [App\Http\Controllers\Account\DeliveryPartnerRegistrationController::class, 'uploadDocuments'])->name('become-delivery-partner.documents');
    });
});

// Seller Registration (Guest)
Route::get('/sell', [App\Http\Controllers\Seller\RegistrationController::class, 'index'])->name('seller.register');
Route::post('/sell/register', [App\Http\Controllers\Seller\RegistrationController::class, 'store'])->name('seller.register.store');

// Newsletter
Route::post('/newsletter/subscribe', [App\Http\Controllers\NewsletterController::class, 'subscribe'])->middleware('throttle:5,1')->name('newsletter.subscribe');

// Recommendations (AJAX)
Route::prefix('recommendations')->name('recommendations.')->group(function () {
    Route::get('/recently-viewed', [App\Http\Controllers\Web\RecommendationController::class, 'recentlyViewed'])->name('recently-viewed');
    Route::get('/similar/{productId}', [App\Http\Controllers\Web\RecommendationController::class, 'similar'])->name('similar');
    Route::get('/bought-together/{productId}', [App\Http\Controllers\Web\RecommendationController::class, 'frequentlyBoughtTogether'])->name('bought-together');
    Route::get('/personalized', [App\Http\Controllers\Web\RecommendationController::class, 'personalized'])->name('personalized');
});

// AI Chatbot
Route::post('/chatbot/message', [App\Http\Controllers\ChatbotController::class, 'message'])->middleware('throttle:20,1')->name('chatbot.message');

// Track Order (Public with order number)
Route::get('/track-order', [App\Http\Controllers\TrackOrderController::class, 'index'])->name('track-order');
Route::post('/track-order', [App\Http\Controllers\TrackOrderController::class, 'track'])->name('track-order.track');

// Static/CMS Pages
Route::get('/about', [App\Http\Controllers\PageController::class, 'about'])->name('about');
Route::get('/contact', [App\Http\Controllers\PageController::class, 'contact'])->name('contact');
Route::post('/contact', [App\Http\Controllers\PageController::class, 'sendContact'])->middleware('throttle:5,1')->name('contact.send');
Route::get('/faq', [App\Http\Controllers\PageController::class, 'faq'])->name('faq');
Route::get('/blog', [App\Http\Controllers\PageController::class, 'blog'])->name('blog');
Route::get('/blog/{slug}', [App\Http\Controllers\PageController::class, 'blogShow'])->name('blog.show');
Route::get('/careers', [App\Http\Controllers\PageController::class, 'careers'])->name('careers');
Route::get('/help', [App\Http\Controllers\PageController::class, 'help'])->name('help');
Route::get('/returns-policy', [App\Http\Controllers\PageController::class, 'returns'])->name('returns');
Route::get('/shipping', [App\Http\Controllers\PageController::class, 'shipping'])->name('shipping');
Route::get('/size-guide', [App\Http\Controllers\PageController::class, 'sizeGuide'])->name('size-guide');
Route::get('/privacy-policy', [App\Http\Controllers\PageController::class, 'privacy'])->name('privacy');
Route::get('/terms-of-service', [App\Http\Controllers\PageController::class, 'terms'])->name('terms');
Route::get('/cookie-policy', [App\Http\Controllers\PageController::class, 'cookiePolicy'])->name('cookie-policy');
Route::get('/gdpr', [App\Http\Controllers\PageController::class, 'gdpr'])->name('gdpr');
Route::get('/page/{page:slug}', [App\Http\Controllers\PageController::class, 'show'])->name('page.show');

// PayU Payment Callbacks (outside auth — PayU POSTs here after payment)
Route::post('/payu/success', [App\Http\Controllers\PayUController::class, 'success'])->name('payu.success');
Route::post('/payu/failure', [App\Http\Controllers\PayUController::class, 'failure'])->name('payu.failure');

// Shiprocket Webhook (outside auth — Shiprocket POSTs tracking updates)
Route::post('/webhooks/shiprocket', [App\Http\Controllers\ShiprocketWebhookController::class, 'handle'])->name('webhooks.shiprocket');

// Load Admin Routes
require __DIR__.'/admin.php';

// Load Seller Routes
require __DIR__.'/seller.php';

// Load Delivery Partner Routes
require __DIR__.'/delivery.php';

// Load POS Routes
require __DIR__.'/pos.php';
