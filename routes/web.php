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

// Products.
//
// /product/{slug} is the canonical path and the only one. It is what every link
// on the site uses, what the canonical tag emits, what the sitemap advertises
// and what the JSON endpoints hand back.
//
// The plural /products/{slug} is not registered, by decision: a path this site
// does not serve is a wrong address, and a wrong address gets the 404 page. It
// is not quietly forwarded to a page the visitor did not ask for, which hides
// the broken link from whoever wrote it and makes the 404 page a liar about
// what this site actually has.
Route::get('/product/{product:slug}', [App\Http\Controllers\ProductController::class, 'show'])->name('product.show');

// Everything else that hangs off one product. These are all named product.*
// already; three of the four answered at /products/... while the fourth and the
// page itself were at /product/..., so the paths disagreed with their own names
// and with each other. They take an id, not a slug - Product has no
// getRouteKeyName(), so route('product.guest-review', $product) has always
// generated the id, and whereNumber pins that.
Route::prefix('product/{product}')->name('product.')->whereNumber('product')->group(function () {
    Route::get('/quick-view', [App\Http\Controllers\ProductController::class, 'quickView'])->name('quick-view');

    Route::post('/guest-review', [App\Http\Controllers\GuestReviewController::class, 'store'])
        ->middleware('throttle:3,60')
        ->name('guest-review');

    Route::post('/ask-question', [App\Http\Controllers\ProductController::class, 'askQuestion'])
        ->middleware('throttle:5,60')
        ->name('ask-question');

    Route::post('/notify-back-in-stock', [App\Http\Controllers\ProductController::class, 'notifyBackInStock'])
        ->middleware('throttle:5,60')
        ->name('notify-back-in-stock');
});

// The review form's old path. Unnamed on purpose so route() only ever emits the
// new one; this is here for a product page that was already open when the
// deploy landed, whose form action is baked into HTML the shopper may be part
// way through filling in. A redirect would not help - a 301 turns their POST
// into a GET and their review would be lost.
Route::post('/products/{product}/guest-review', [App\Http\Controllers\GuestReviewController::class, 'store'])
    ->middleware('throttle:3,60');

// Categories. Same rule as products above: /category/{slug} is the one path,
// the plural is not registered and 404s.
// (There is no categories index page - the navbar shows a hover dropdown.)
Route::get('/category/{category:slug}', [App\Http\Controllers\CategoryController::class, 'show'])->name('category.show');

// Brands
Route::prefix('brands')->name('brands.')->group(function () {
    Route::get('/', [App\Http\Controllers\BrandController::class, 'index'])->name('index');
    Route::get('/{brand:slug}', [App\Http\Controllers\BrandController::class, 'show'])->name('show');
});

// Search
Route::get('/search', [App\Http\Controllers\SearchController::class, 'index'])->name('search');
Route::get('/search/suggestions', [App\Http\Controllers\SearchController::class, 'suggestions'])->name('search.suggestions');

// Special Pages
// The all-products page, served at /products. That is the address the store
// owner wants shoppers to arrive at, so it is where the page is answered rather
// than somewhere it is forwarded from - it sat at /shop, with /products first
// 404ing and then 301ing here, and both of those made a visitor who typed the
// obvious address wrong about this shop instead of the other way round.
//
// Nothing collides: the product page is at /product/{slug}, singular, and no
// /products/{slug} wildcard is registered to swallow /products/filters below.
// If one is ever added it must be declared AFTER these two literals, or it will
// answer them - the same ordering bug that once ate /cart/remove-coupon.
Route::get('/products', [App\Http\Controllers\ProductController::class, 'index'])->name('shop');

// The filter panel on its own, for the Filters drawer the header now carries on
// every page. Fetched the first time a shopper opens the drawer rather than
// rendered into every page, because building the facets is five queries and
// most visits never open it.
Route::get('/products/filters', [App\Http\Controllers\ProductController::class, 'filtersPanel'])->name('shop.filters');

// The route names stay 'shop' and 'shop.filters'. They are what ~30 call sites
// already ask for, and every one emits the new path the moment the URI changes
// here - which is why moving this page took two lines rather than thirty.
// Renaming them is a tidy-up worth doing on its own, not folded into a URL
// change that has to ship.

// /shop, /returns, /orders/{id} and /orders/{id}/track are deliberately not
// registered. /shop is where the all-products page used to be answered, before
// it moved to /products above; the other three predate /returns-policy and the
// move of order pages under /account.
//
// None is an alias, because this site does not answer a path it cannot serve by
// sending the visitor somewhere else. That was tried - /products used to 301 to
// /shop - and it put people on a page they never asked for with nothing to say
// the address they held was dead, while whoever wrote the bad link never found
// out, because the site covered for them silently and forever. It was not even
// faithful: a Laravel redirect route carries path parameters only, so
// /products?category=kurtas arrived at an unfiltered /shop, a wrong page rather
// than a slow one.
//
// So a path this site does not serve answers with the 404 page. Nothing here
// asks for one: every internal link goes through route(), and the stored links
// and page copy that named an old path are repointed by the two
// repoint_legacy_storefront_links / repoint_shop_links_at_products migrations.
Route::get('/deals', [App\Http\Controllers\DealsController::class, 'index'])->name('deals');
Route::get('/flash-sale/{flashSale:slug}', [App\Http\Controllers\FlashSaleController::class, 'show'])->name('flash-sale.show');
Route::get('/new-arrivals', [App\Http\Controllers\ProductController::class, 'newArrivals'])->name('new-arrivals');
Route::get('/bestsellers', [App\Http\Controllers\ProductController::class, 'bestsellers'])->name('bestsellers');
Route::get('/wholesale', [App\Http\Controllers\WholesaleController::class, 'index'])->name('wholesale');

// Cart
Route::prefix('cart')->name('cart.')->group(function () {
    // Named paths first. DELETE /cart/{cartItem} was declared above
    // /remove-coupon and matched it, so the request looked for a cart item
    // literally called "remove-coupon" and 404'd - the Remove button on an
    // applied coupon never worked. The whereNumber constraint stops any future
    // literal route being swallowed the same way.
    Route::get('/data', [App\Http\Controllers\CartController::class, 'data'])->name('data');
    Route::get('/recommendations', [App\Http\Controllers\CartController::class, 'recommendations'])->name('recommendations');

    // Reopening an abandoned cart from the link in a recovery email. Open to
    // guests because the whole point is a customer whose session has expired;
    // the controller re-checks ownership before it binds anything. Throttled
    // because the 64-character token in the URL is the only credential, and an
    // unthrottled route would be a free oracle for guessing one.
    //
    // /cart is already in robots.txt's Disallow list, so this path is covered
    // by it and a token cannot end up in a search index.
    Route::get('/recover/{token}', App\Http\Controllers\CartRecoveryController::class)
        ->middleware('throttle:10,1')
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('recover');

    Route::get('/', [App\Http\Controllers\CartController::class, 'index'])->name('index');

    // Putting something in a cart takes an account. The store owner asked for
    // it: a guest cart is a basket nobody can be contacted about, and the guest
    // half of the flow only ever ended at the login page anyway - checkout has
    // required an account since long before this.
    //
    // Reading stays open. The header count, the drawer and /cart all answer for
    // a guest, and answer "empty", which is the truth for one.
    //
    // The button gets there first: it sends the customer to /login?next=<page>
    // before any request is made, so this gate is the backstop for a stale tab
    // or a script - and for those it is a 401, which the front end turns into
    // the same trip to the login page.
    Route::middleware('auth')->group(function () {
        Route::post('/add', [App\Http\Controllers\CartController::class, 'add'])->name('add');
        Route::post('/apply-coupon', [App\Http\Controllers\CartController::class, 'applyCoupon'])->middleware('throttle:10,1')->name('apply-coupon');
        Route::delete('/remove-coupon', [App\Http\Controllers\CartController::class, 'removeCoupon'])->name('remove-coupon');
        Route::delete('/', [App\Http\Controllers\CartController::class, 'clear'])->name('clear');
        Route::put('/{cartItem}', [App\Http\Controllers\CartController::class, 'update'])->whereNumber('cartItem')->name('update');
        Route::delete('/{cartItem}', [App\Http\Controllers\CartController::class, 'destroy'])->whereNumber('cartItem')->name('destroy');
    });
});

// Checkout requires an account. Placing the order is the only step that is
// gated: browsing and the cart stay open to guests, and the guest cart is
// merged into the account on sign-in, so nothing picked out is lost on the way
// through the login page. The `auth` middleware records the intended URL, which
// brings the customer straight back here afterwards.
//
// success/ and failed/ stay outside the gate. Orders placed while guest
// checkout was open are owned by a session id rather than a user, and their
// confirmation page still has to open; success() checks that ownership itself.
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('/', [App\Http\Controllers\CheckoutController::class, 'index'])->name('index');
        Route::post('/process', [App\Http\Controllers\CheckoutController::class, 'process'])->middleware('throttle:10,1')->name('process');
    });

    Route::get('/success/{order}', [App\Http\Controllers\CheckoutController::class, 'success'])->name('success');
    Route::get('/failed', [App\Http\Controllers\CheckoutController::class, 'failed'])->name('failed');
});

// PayU initiate stays outside the auth group so an unpaid guest order from
// before that change can still be taken to the gateway; ownership (user id or
// guest session) is enforced inside the controller.
Route::get('/payu/initiate/{order}', [App\Http\Controllers\PayUController::class, 'initiate'])->name('payu.initiate');

// Wishlist. The list itself lives in a browser cookie (kk_wishlist), so it
// works for a guest; the endpoints below only turn ids into product data and
// keep the signed-in server copy in step.
Route::get('/wishlist', [App\Http\Controllers\WishlistController::class, 'index'])->name('wishlist');

// The data endpoint used to sit at /wishlist-items, outside the prefix its own
// page and actions use. whereNumber on {product} is what lets /items live in
// here safely - the cart carries the same constraint after exactly that bug
// swallowed /cart/remove-coupon.
Route::prefix('wishlist')->name('wishlist.')->group(function () {
    // Product data for the favourited ids. Guest-accessible: it is what renders
    // the wishlist page and the drawer, both of which answer for a guest.
    Route::get('/items', [App\Http\Controllers\WishlistController::class, 'items'])->name('items');

    // Writing takes an account, same as the cart.
    Route::middleware('auth')->group(function () {
        Route::post('/{product}', [App\Http\Controllers\WishlistController::class, 'store'])->whereNumber('product')->name('store');
        Route::delete('/{product}', [App\Http\Controllers\WishlistController::class, 'destroy'])->whereNumber('product')->name('destroy');
    });
});

// The old path, still answering. A tab opened before this deploy is running the
// previous JS bundle and still asks for this one. A redirect would not do here:
// Laravel's redirect routes forward path parameters only and would drop the
// ?ids= list, so the shopper would be told their wishlist was empty rather than
// sent to the right place. Safe to delete once no old bundle can be in a browser.
Route::get('/wishlist-items', [App\Http\Controllers\WishlistController::class, 'items']);

// Guest Authentication Routes
//
// Throttling is per-action and applies to the POSTs only. A single shared
// `throttle:10,1` across the whole group counted page views too — and because
// the guest limiter keys on domain|ip and ignores the URI, loading the login
// page a few times locked the visitor out of registering.
Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->middleware('throttle:login');

    Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])->middleware('throttle:register');

    Route::get('/password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/password/reset/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->middleware('throttle:password-reset')->name('password.update');
});

// Authenticated User Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

    // Email Verification
    Route::get('/email/verify', [App\Http\Controllers\Auth\VerificationController::class, 'show'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [App\Http\Controllers\Auth\VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    // Throttled, because every hit sends a real message. The shop authenticates
    // to Gmail with one app password and one daily send quota, and this is the
    // only mail route a signed-in visitor can fire at will: unmetered, a script
    // posting here in a loop spends the whole quota, and then nothing else the
    // shop sends leaves the server - order confirmations and password resets
    // included. Six an hour is far more than a customer waiting on a link needs
    // and far less than a quota costs. The forgot-password form is already
    // metered this way; this route was the gap.
    Route::post('/email/resend', [App\Http\Controllers\Auth\VerificationController::class, 'resend'])
        ->middleware('throttle:6,60')
        ->name('verification.resend');

    // Account Routes
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [App\Http\Controllers\Account\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [App\Http\Controllers\Account\ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [App\Http\Controllers\Account\ProfileController::class, 'update'])->name('profile.update');
        Route::put('/password', [App\Http\Controllers\Account\ProfileController::class, 'updatePassword'])->name('password.update');

        // Addresses. except(show) because AddressController has no show() - the
        // list links straight to edit, so GET /account/addresses/{id} was only
        // ever reachable by typing it, and answered 500 rather than 404.
        Route::resource('addresses', App\Http\Controllers\Account\AddressController::class)
            ->except(['show']);

        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [App\Http\Controllers\Account\OrderController::class, 'index'])->name('index');
            Route::get('/{order}', [App\Http\Controllers\Account\OrderController::class, 'show'])->name('show');
            Route::post('/{order}/cancel', [App\Http\Controllers\Account\OrderController::class, 'cancel'])->name('cancel');
            Route::get('/{order}/invoice', [App\Http\Controllers\Account\OrderController::class, 'invoice'])->name('invoice');
            Route::get('/{order}/track', [App\Http\Controllers\Account\OrderController::class, 'track'])->name('track');
            Route::post('/{order}/reorder', [App\Http\Controllers\Account\OrderController::class, 'reorder'])->name('reorder');
        });

        // Returns. Only the four methods ReturnController defines. The full
        // resource also registered edit, update and destroy, which resolved to
        // missing methods and 500'd when hit - a customer cannot amend or
        // withdraw a return request from here, only raise and read one.
        Route::resource('returns', App\Http\Controllers\Account\ReturnController::class)
            ->only(['index', 'create', 'store', 'show']);

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
        // Clicking a notification is what marks it read, and the account page
        // had no read action of any kind: every notification a customer had
        // ever received stayed unread for good, so the read/unread styling the
        // list is built around never fired.
        Route::get('/notifications/{notification}/read', [App\Http\Controllers\Account\NotificationController::class, 'read'])->name('notifications.read');
        Route::post('/notifications/read-all', [App\Http\Controllers\Account\NotificationController::class, 'readAll'])->name('notifications.read-all');

        // Notification Preferences
        Route::get('/notification-preferences', [App\Http\Controllers\Account\NotificationPreferenceController::class, 'edit'])->name('notification-preferences');
        Route::put('/notification-preferences', [App\Http\Controllers\Account\NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');
    });
});

// Newsletter
Route::post('/newsletter/subscribe', [App\Http\Controllers\NewsletterController::class, 'subscribe'])->middleware('throttle:5,1')->name('newsletter.subscribe');

// Recommendations (AJAX)
Route::prefix('recommendations')->name('recommendations.')->group(function () {
    Route::get('/recently-viewed', [App\Http\Controllers\Web\RecommendationController::class, 'recentlyViewed'])->name('recently-viewed');
    // whereNumber because both controller methods type-hint int $productId:
    // an unconstrained {productId} let /recommendations/similar/foo through to a
    // TypeError and a 500, where the rest of the site answers 404 for an
    // identifier that cannot exist.
    Route::get('/similar/{productId}', [App\Http\Controllers\Web\RecommendationController::class, 'similar'])->whereNumber('productId')->name('similar');
    Route::get('/bought-together/{productId}', [App\Http\Controllers\Web\RecommendationController::class, 'frequentlyBoughtTogether'])->whereNumber('productId')->name('bought-together');
    Route::get('/personalized', [App\Http\Controllers\Web\RecommendationController::class, 'personalized'])->name('personalized');
});

// AI Chatbot
// Signed-in customers only. The widget is hidden from guests, but the guard
// has to live here too: an open endpoint would let anyone spend the store's
// AI quota without ever loading the page.
Route::middleware('auth')->group(function () {
    Route::post('/chatbot/message', [App\Http\Controllers\ChatbotController::class, 'message'])->middleware('throttle:20,1')->name('chatbot.message');
    Route::post('/chatbot/product-click', [App\Http\Controllers\ChatbotController::class, 'productClick'])->middleware('throttle:60,1')->name('chatbot.product-click');

    // Reading the saved conversation. Without this the widget had no way to
    // recover a conversation after a page navigation, so it always looked
    // like the assistant had forgotten the customer.
    Route::get('/chatbot/history', [App\Http\Controllers\ChatbotController::class, 'history'])->name('chatbot.history');

    // Full-page chat — the same conversation with room for the product cards.
    Route::get('/chat', [App\Http\Controllers\ChatbotController::class, 'page'])->name('chat');
});

// Track Order (Public with order number)
Route::get('/track-order', [App\Http\Controllers\TrackOrderController::class, 'index'])->name('track-order');
Route::post('/track-order', [App\Http\Controllers\TrackOrderController::class, 'track'])->middleware('throttle:10,1')->name('track-order.track');

// Guest return requests. Reachable only after the order has been verified on
// the tracking page above, which records it in the session.
Route::get('/track-order/{order}/return', [App\Http\Controllers\GuestReturnController::class, 'create'])->name('track-order.return');
Route::post('/track-order/{order}/return', [App\Http\Controllers\GuestReturnController::class, 'store'])->middleware('throttle:5,1')->name('track-order.return.store');

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

// Admin-made collections. Kept under its own prefix so a collection can never
// collide with a category slug or one of the built-in listings.
Route::get('/collection/{collection}', [App\Http\Controllers\CollectionController::class, 'show'])->name('collection.show');

// PayU Payment Callbacks (outside auth - PayU POSTs here after payment)
Route::post('/payu/success', [App\Http\Controllers\PayUController::class, 'success'])->name('payu.success');
Route::post('/payu/failure', [App\Http\Controllers\PayUController::class, 'failure'])->name('payu.failure');

// Shiprocket Webhook (outside auth - Shiprocket POSTs tracking updates)
Route::post('/webhooks/shiprocket', [App\Http\Controllers\ShiprocketWebhookController::class, 'handle'])->name('webhooks.shiprocket');

// Load Admin Routes
require __DIR__.'/admin.php';
