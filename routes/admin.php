<?php

use App\Http\Controllers\Admin\AbandonedCartController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeValueController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\FlashSaleController;
use App\Http\Controllers\Admin\FraudController;
use App\Http\Controllers\Admin\HomepageController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InventoryLocationController;
use App\Http\Controllers\Admin\InventoryLocationStockController;
use App\Http\Controllers\Admin\InventoryReportController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductAplusImageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingRateController;
use App\Http\Controllers\Admin\ShippingZoneController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\TaxRateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest routes
    Route::middleware(['guest:admin', 'throttle:10,1'])->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);
    });

    // Authenticated admin routes
    Route::middleware(['auth:admin', 'admin', 'admin.audit'])->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        // POST because it mutates, which also puts it in front of LogAdminActions.
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

        // AJAX search endpoints (used by multiple features)
        Route::get('/search/products', [SearchController::class, 'products'])->name('search.products');

        // Orders
        Route::middleware('admin.section:orders')->group(function () {
            Route::prefix('orders')->name('orders.')->group(function () {
                Route::get('/', [OrderController::class, 'index'])->name('index');
                Route::get('/{order}', [OrderController::class, 'show'])->name('show');
                Route::put('/{order}/status', [OrderController::class, 'updateStatus'])->name('status');
                Route::put('/{order}/payment', [OrderController::class, 'recordPayment'])->name('payment');
                Route::post('/{order}/ship', [OrderController::class, 'ship'])->name('ship');
                Route::get('/{order}/invoice', [OrderController::class, 'invoice'])->name('invoice');
                Route::get('/{order}/packing-slip', [OrderController::class, 'packingSlip'])->name('packing-slip');
                Route::post('/{order}/assign-partner', [OrderController::class, 'assignPartner'])->name('assign-partner');
                Route::put('/{order}/expected-delivery', [OrderController::class, 'setExpectedDelivery'])->name('expected-delivery');
                Route::post('/{order}/shiprocket/push', [OrderController::class, 'pushToShiprocket'])->name('shiprocket.push');
                Route::post('/{order}/shiprocket/sync', [OrderController::class, 'syncShiprocketTracking'])->name('shiprocket.sync');
                Route::post('/{order}/shiprocket/cancel', [OrderController::class, 'cancelShiprocket'])->name('shiprocket.cancel');
            });

            // Returns
            Route::prefix('returns')->name('returns.')->group(function () {
                Route::get('/', [ReturnController::class, 'index'])->name('index');
                Route::get('/{return}', [ReturnController::class, 'show'])->name('show');
                Route::put('/{return}/status', [ReturnController::class, 'updateStatus'])->name('status');
                Route::post('/{return}/refund', [ReturnController::class, 'processRefund'])->name('refund');
                Route::post('/{return}/assign-partner', [ReturnController::class, 'assignPartner'])->name('assign-partner');
            });

            // Chat analytics
            Route::get('chatbot/analytics', [App\Http\Controllers\Admin\ChatbotAnalyticsController::class, 'index'])->name('chatbot.analytics');
            Route::get('chatbot/leads', [App\Http\Controllers\Admin\ChatbotAnalyticsController::class, 'leads'])->name('chatbot.leads');
            Route::get('chatbot/conversations/{conversation}', [App\Http\Controllers\Admin\ChatbotAnalyticsController::class, 'show'])->name('chatbot.conversation');
            Route::put('chatbot/conversations/{conversation}/lead-status', [App\Http\Controllers\Admin\ChatbotAnalyticsController::class, 'updateLeadStatus'])->name('chatbot.lead-status');
        });

        // Abandoned carts
        //
        // Its own section rather than a corner of `orders`: the screen exposes
        // customer email and phone and can send mail on the store's behalf, so
        // it has to be grantable to a recovery desk without also handing over
        // every order, and withheld from warehouse staff who hold `orders` for
        // fulfilment. Admins are unaffected either way - isAdmin() short-circuits
        // every section check.
        Route::middleware('admin.section:abandoned_carts')->group(function () {
            Route::prefix('abandoned-carts')->name('abandoned-carts.')->group(function () {
                // Literal paths before {abandonedCart}, and the wildcard pinned
                // to digits. Declaring them the other way round is how
                // /cart/remove-coupon got swallowed once already.
                Route::get('/', [AbandonedCartController::class, 'index'])->name('index');
                Route::get('/export', [AbandonedCartController::class, 'export'])->name('export');
                Route::get('/settings', [AbandonedCartController::class, 'settings'])->name('settings');
                Route::put('/settings', [AbandonedCartController::class, 'updateSettings'])->name('settings.update');
                Route::post('/scan', [AbandonedCartController::class, 'scan'])->name('scan');
                Route::post('/bulk-action', [AbandonedCartController::class, 'bulkAction'])->name('bulk-action');

                Route::get('/{abandonedCart}', [AbandonedCartController::class, 'show'])->whereNumber('abandonedCart')->name('show');
                Route::post('/{abandonedCart}/remind', [AbandonedCartController::class, 'remind'])->whereNumber('abandonedCart')->name('remind');
                Route::post('/{abandonedCart}/contacted', [AbandonedCartController::class, 'markContacted'])->whereNumber('abandonedCart')->name('contacted');
                Route::post('/{abandonedCart}/recovered', [AbandonedCartController::class, 'markRecovered'])->whereNumber('abandonedCart')->name('recovered');
                Route::post('/{abandonedCart}/archive', [AbandonedCartController::class, 'archive'])->whereNumber('abandonedCart')->name('archive');
            });
        });

        // Catalog
        Route::middleware('admin.section:catalog')->group(function () {
            // Products (export/import before resource to avoid route conflict)
            Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
            Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
            Route::resource('products', ProductController::class);
            Route::put('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
            Route::put('/products/{product}/toggle-featured', [ProductController::class, 'toggleFeatured'])->name('products.toggle-featured');
            Route::post('/products/{product}/images/reorder', [ProductController::class, 'reorderImages'])->name('products.images.reorder');
            // A+ Content (Amazon-style banner images)
            Route::post('/products/{product}/aplus', [ProductAplusImageController::class, 'store'])->name('products.aplus.store');
            Route::post('/products/{product}/aplus/reorder', [ProductAplusImageController::class, 'reorder'])->name('products.aplus.reorder');
            Route::patch('/products/aplus/{aplusImage}', [ProductAplusImageController::class, 'update'])->name('products.aplus.update');
            Route::delete('/products/aplus/{aplusImage}', [ProductAplusImageController::class, 'destroy'])->name('products.aplus.destroy');
            Route::post('/products/bulk-action', [ProductController::class, 'bulkAction'])->name('products.bulk-action');

            // Categories
            Route::resource('categories', CategoryController::class);
            Route::put('/categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
            Route::post('/categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');

            // Brands
            Route::resource('brands', BrandController::class)->except(['show']);

            // Attributes
            Route::resource('attributes', AttributeController::class)->except(['show']);
            Route::resource('attributes.values', AttributeValueController::class)->shallow()->except(['index', 'show']);

            // Inventory
            Route::prefix('inventory')->name('inventory.')->group(function () {
                Route::get('/', [InventoryController::class, 'index'])->name('index');
                Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('low-stock');
                Route::get('/out-of-stock', [InventoryController::class, 'outOfStock'])->name('out-of-stock');
                Route::put('/{product}/stock', [InventoryController::class, 'updateStock'])->name('update-stock');
                Route::get('/movements', [InventoryController::class, 'movements'])->name('movements');
                Route::resource('locations', InventoryLocationController::class);

                // What each location stocks
                Route::post('/locations/{location}/stock', [InventoryLocationStockController::class, 'store'])->name('locations.stock.store');
                Route::put('/locations/{location}/stock/{stock}', [InventoryLocationStockController::class, 'update'])->name('locations.stock.update');
                Route::delete('/locations/{location}/stock/{stock}', [InventoryLocationStockController::class, 'destroy'])->name('locations.stock.destroy');
            });
        });

        // Customers
        Route::middleware('admin.section:customers')->group(function () {
            Route::resource('customers', CustomerController::class)->except(['create', 'store', 'destroy']);
            Route::put('/customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
            Route::get('/customers/{customer}/orders', [CustomerController::class, 'orders'])->name('customers.orders');
        });

        // Staff (admin-only)
        Route::middleware('admin.section:staff')->group(function () {
            Route::resource('staff', StaffController::class)->except(['show']);
        });

        // Marketing
        Route::middleware('admin.section:marketing')->group(function () {
            Route::resource('coupons', CouponController::class)->except(['show']);
            Route::resource('flash-sales', FlashSaleController::class)->except(['show']);
            Route::resource('banners', BannerController::class)->except(['show']);
            Route::post('/banners/reorder', [BannerController::class, 'reorder'])->name('banners.reorder');

            // Newsletter
            Route::prefix('newsletter')->name('newsletter.')->group(function () {
                Route::get('/', [NewsletterController::class, 'index'])->name('index');
                Route::delete('/{newsletter}', [NewsletterController::class, 'destroy'])->name('destroy');
                Route::put('/{newsletter}/toggle-status', [NewsletterController::class, 'toggleStatus'])->name('toggle-status');
                Route::post('/bulk-action', [NewsletterController::class, 'bulkAction'])->name('bulk-action');
                Route::get('/export', [NewsletterController::class, 'export'])->name('export');
            });
        });

        // Content
        Route::middleware('admin.section:content')->group(function () {
            // Collections: hand-picked product groups with their own page and
            // an optional header link. Filed under Content next to Pages, which
            // is the other thing here that puts something in the navigation.
            Route::resource('collections', CollectionController::class)
                ->parameters(['collections' => 'collection'])
                ->except(['show']);

            Route::resource('pages', PageController::class)->except(['show']);
            Route::put('/pages/{page}/toggle-status', [PageController::class, 'toggleStatus'])->name('pages.toggle-status');

            Route::resource('blog-posts', BlogPostController::class)->except(['show']);
            Route::put('/blog-posts/{blogPost}/toggle-status', [BlogPostController::class, 'toggleStatus'])->name('blog-posts.toggle-status');

            Route::prefix('reviews')->name('reviews.')->group(function () {
                Route::get('/', [ReviewController::class, 'index'])->name('index');
                Route::get('/pending', [ReviewController::class, 'pending'])->name('pending');
                Route::get('/{review}', [ReviewController::class, 'show'])->name('show');
                Route::post('/{review}/approve', [ReviewController::class, 'approve'])->name('approve');
                Route::post('/{review}/reject', [ReviewController::class, 'reject'])->name('reject');
                Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
            });
        });

        // Support Tickets
        Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
            Route::get('/', [SupportTicketController::class, 'index'])->name('index');
            Route::get('/{supportTicket}', [SupportTicketController::class, 'show'])->name('show');
            Route::post('/{supportTicket}/reply', [SupportTicketController::class, 'reply'])->name('reply');
            Route::put('/{supportTicket}/status', [SupportTicketController::class, 'updateStatus'])->name('status');
            Route::delete('/{supportTicket}', [SupportTicketController::class, 'destroy'])->name('destroy');
        });

        // Enquiries
        Route::prefix('enquiries')->name('enquiries.')->group(function () {
            Route::get('/', [EnquiryController::class, 'index'])->name('index');
            Route::get('/{enquiry}', [EnquiryController::class, 'show'])->name('show');
            Route::post('/{enquiry}/reply', [EnquiryController::class, 'reply'])->name('reply');
            Route::put('/{enquiry}/toggle-read', [EnquiryController::class, 'toggleRead'])->name('toggle-read');
            Route::put('/{enquiry}/status', [EnquiryController::class, 'updateStatus'])->name('status');
            Route::delete('/{enquiry}', [EnquiryController::class, 'destroy'])->name('destroy');
        });

        // Reports
        Route::middleware('admin.section:reports')->group(function () {
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
                Route::get('/analytics', [ReportController::class, 'analytics'])->name('analytics');
                Route::get('/products', [ReportController::class, 'products'])->name('products');
                Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
                Route::get('/inventory', [InventoryReportController::class, 'index'])->name('inventory');
                Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
                Route::get('/export-excel/{type}', [ReportController::class, 'exportExcel'])->name('export-excel');
            });
        });

        // Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Fraud Review
        Route::prefix('fraud')->name('fraud.')->group(function () {
            Route::get('/', [FraudController::class, 'index'])->name('index');
            Route::get('/{fraudLog}', [FraudController::class, 'show'])->name('show');
            Route::put('/{fraudLog}/review', [FraudController::class, 'review'])->name('review');
        });

        // Settings (admin-only)
        Route::middleware('admin.section:settings')->group(function () {
            Route::prefix('settings')->name('settings.')->group(function () {
                Route::get('/general', [SettingController::class, 'general'])->name('general');
                Route::put('/general', [SettingController::class, 'updateGeneral'])->name('general.update');

                Route::get('/shipping', [SettingController::class, 'shipping'])->name('shipping');
                Route::put('/shipping', [SettingController::class, 'updateShipping'])->name('shipping.update');

                Route::get('/tax', [SettingController::class, 'tax'])->name('tax');
                Route::put('/tax', [SettingController::class, 'updateTax'])->name('tax.update');

                Route::get('/seo', [SettingController::class, 'seo'])->name('seo');
                Route::put('/seo', [SettingController::class, 'updateSeo'])->name('seo.update');

                Route::get('/product-card', [SettingController::class, 'productCard'])->name('product-card');
                Route::put('/product-card', [SettingController::class, 'updateProductCard'])->name('product-card.update');

                Route::get('/popups', [SettingController::class, 'popups'])->name('popups');
                Route::put('/popups', [SettingController::class, 'updatePopups'])->name('popups.update');

                // Tax Rates
                Route::resource('tax-rates', TaxRateController::class)->except(['show']);

                // Shipping Zones
                Route::resource('shipping-zones', ShippingZoneController::class)->except(['show']);
                // Only the five methods ShippingRateController actually defines.
                // The full resource also registered index and show, which
                // resolved to missing methods and 500'd when hit.
                Route::resource('shipping-zones.rates', ShippingRateController::class)
                    ->shallow()
                    ->only(['create', 'store', 'edit', 'update', 'destroy']);

                // Currencies
                Route::resource('currencies', CurrencyController::class)->except(['show']);

                // Roles & Permissions
                Route::resource('roles', RoleController::class)->except(['show']);
            });

            // Stores (POS). except('show') because StoreController has no
            // show() - the row click and the action link both go to edit, so
            // the only way to reach GET /admin/stores/{id} was by typing it,
            // and it answered with a 500 rather than a 404.
            Route::resource('stores', StoreController::class)->except(['show']);
        });

        // Storefront / Homepage Manager
        Route::middleware('admin.section:storefront')->group(function () {
            Route::prefix('homepage')->name('homepage.')->group(function () {
                Route::get('/', [HomepageController::class, 'index'])->name('index');

                // Site Settings
                Route::get('/site-settings', [HomepageController::class, 'siteSettings'])->name('site-settings');
                Route::put('/site-settings', [HomepageController::class, 'updateSiteSettings'])->name('site-settings.update');

                // Hero Banners
                Route::get('/hero-banners', [HomepageController::class, 'heroBanners'])->name('hero-banners');
                Route::post('/hero-banners', [HomepageController::class, 'storeHeroBanner'])->name('hero-banners.store');
                Route::put('/hero-banners/{banner}', [HomepageController::class, 'updateHeroBanner'])->name('hero-banners.update');
                Route::put('/hero-banners/{banner}/toggle', [HomepageController::class, 'toggleHeroBanner'])->name('hero-banners.toggle');
                Route::delete('/hero-banners/{banner}', [HomepageController::class, 'deleteHeroBanner'])->name('hero-banners.destroy');
                Route::post('/hero-banners/reorder', [HomepageController::class, 'reorderHeroBanners'])->name('hero-banners.reorder');

                // Sections
                Route::get('/sections', [HomepageController::class, 'sections'])->name('sections');
                Route::get('/sections/{section}', [HomepageController::class, 'editSection'])->name('sections.edit');
                Route::put('/sections/{section}', [HomepageController::class, 'updateSection'])->name('sections.update');
                Route::put('/sections/{section}/toggle', [HomepageController::class, 'toggleSection'])->name('sections.toggle');

                // Shop It Your Way filter items
                Route::get('/shop-filters', [HomepageController::class, 'shopFilters'])->name('shop-filters');
                Route::post('/shop-filters', [HomepageController::class, 'storeShopFilter'])->name('shop-filters.store');
                Route::put('/shop-filters/{shopFilter}', [HomepageController::class, 'updateShopFilter'])->name('shop-filters.update');
                Route::put('/shop-filters/{shopFilter}/toggle', [HomepageController::class, 'toggleShopFilter'])->name('shop-filters.toggle');
                Route::put('/shop-filters/{shopFilter}/move', [HomepageController::class, 'moveShopFilter'])->name('shop-filters.move');
                Route::delete('/shop-filters/{shopFilter}', [HomepageController::class, 'deleteShopFilter'])->name('shop-filters.destroy');

                // About Us reels - the clip strip under "Crafted to Last"
                Route::get('/about-reels', [HomepageController::class, 'aboutReels'])->name('about-reels');
                Route::post('/about-reels', [HomepageController::class, 'storeAboutReel'])->name('about-reels.store');
                Route::put('/about-reels/{aboutReel}', [HomepageController::class, 'updateAboutReel'])->name('about-reels.update');
                Route::put('/about-reels/{aboutReel}/toggle', [HomepageController::class, 'toggleAboutReel'])->name('about-reels.toggle');
                Route::put('/about-reels/{aboutReel}/move', [HomepageController::class, 'moveAboutReel'])->name('about-reels.move');
                Route::delete('/about-reels/{aboutReel}', [HomepageController::class, 'deleteAboutReel'])->name('about-reels.destroy');

                // Our Qualities cards
                Route::get('/qualities', [HomepageController::class, 'qualities'])->name('qualities');
                Route::post('/qualities', [HomepageController::class, 'storeQuality'])->name('qualities.store');
                Route::put('/qualities/{quality}', [HomepageController::class, 'updateQuality'])->name('qualities.update');
                Route::put('/qualities/{quality}/toggle', [HomepageController::class, 'toggleQuality'])->name('qualities.toggle');
                Route::put('/qualities/{quality}/move', [HomepageController::class, 'moveQuality'])->name('qualities.move');
                Route::delete('/qualities/{quality}', [HomepageController::class, 'deleteQuality'])->name('qualities.destroy');

                // Navigation
                Route::get('/navigation', [HomepageController::class, 'navigation'])->name('navigation');
                Route::post('/navigation', [HomepageController::class, 'storeNavItem'])->name('navigation.store');
                Route::put('/navigation/{menu}', [HomepageController::class, 'updateNavItem'])->name('navigation.update');
                Route::put('/navigation/{menu}/toggle', [HomepageController::class, 'toggleNavItem'])->name('navigation.toggle');
                Route::delete('/navigation/{menu}', [HomepageController::class, 'deleteNavItem'])->name('navigation.destroy');
            });
        });
    });
});
