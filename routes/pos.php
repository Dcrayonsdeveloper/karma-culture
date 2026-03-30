<?php

use App\Http\Controllers\Pos;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| POS Routes
|--------------------------------------------------------------------------
|
| All POS routes under /pos prefix, using web middleware group (sessions).
| Device authentication via PosAuthenticate middleware.
| Shift enforcement via PosShiftRequired middleware.
|
*/

Route::prefix('pos')->name('pos.')->group(function () {

    // ── Guest POS routes (no staff session) ──────────────────────────
    Route::middleware(['throttle:30,1'])->group(function () {
        Route::get('/login', [Pos\AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [Pos\AuthController::class, 'login']);
        Route::post('/register-device', [Pos\AuthController::class, 'registerDevice'])->name('register-device');
    });

    // ── Authenticated POS routes (staff logged in) ───────────────────
    Route::middleware(['pos.auth'])->group(function () {
        Route::post('/logout', [Pos\AuthController::class, 'logout'])->name('logout');

        // Shift management (no shift required to open one)
        Route::get('/shift/open', [Pos\ShiftController::class, 'showOpen'])->name('shift.open');
        Route::post('/shift/open', [Pos\ShiftController::class, 'open']);

        // Routes that require an open shift
        Route::middleware(['pos.shift'])->group(function () {
            // ── Main billing screen ──────────────────────────────────
            Route::get('/', [Pos\DashboardController::class, 'index'])->name('dashboard');

            // ── Products ─────────────────────────────────────────────
            Route::get('/products/search', [Pos\ProductController::class, 'search'])->name('products.search');
            Route::get('/products/barcode/{code}', [Pos\ProductController::class, 'barcodeLookup'])->name('products.barcode');
            Route::get('/products', [Pos\ProductController::class, 'index'])->name('products.index');
            Route::get('/categories', [Pos\ProductController::class, 'categories'])->name('categories');

            // ── Cart ─────────────────────────────────────────────────
            Route::post('/cart/add', [Pos\CartController::class, 'add'])->name('cart.add');
            Route::patch('/cart/{item}', [Pos\CartController::class, 'update'])->name('cart.update');
            Route::delete('/cart/{item}', [Pos\CartController::class, 'remove'])->name('cart.remove');
            Route::delete('/cart', [Pos\CartController::class, 'clear'])->name('cart.clear');
            Route::post('/cart/discount', [Pos\CartController::class, 'applyDiscount'])->name('cart.discount');
            Route::post('/cart/coupon', [Pos\CartController::class, 'applyCoupon'])->name('cart.coupon');
            Route::delete('/cart/coupon', [Pos\CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
            Route::post('/cart/customer', [Pos\CartController::class, 'attachCustomer'])->name('cart.customer');
            Route::get('/cart/data', [Pos\CartController::class, 'data'])->name('cart.data');

            // ── Sales ────────────────────────────────────────────────
            Route::post('/sale/complete', [Pos\SaleController::class, 'complete'])->name('sale.complete');
            Route::get('/sale/{sale}/receipt', [Pos\SaleController::class, 'receipt'])->name('sale.receipt');
            Route::get('/sale/{sale}/receipt-data', [Pos\SaleController::class, 'receiptData'])->name('sale.receipt-data');

            // ── Held Bills ───────────────────────────────────────────
            Route::get('/held-bills', [Pos\CartController::class, 'heldBills'])->name('held-bills');
            Route::post('/held-bills/hold', [Pos\CartController::class, 'hold'])->name('held-bills.hold');
            Route::post('/held-bills/{bill}/resume', [Pos\CartController::class, 'resume'])->name('held-bills.resume');
            Route::delete('/held-bills/{bill}', [Pos\CartController::class, 'deleteHeld'])->name('held-bills.delete');

            // ── Returns ──────────────────────────────────────────────
            Route::get('/returns', [Pos\ReturnController::class, 'index'])->name('returns');
            Route::get('/returns/find', [Pos\ReturnController::class, 'findSale'])->name('returns.find');
            Route::post('/returns', [Pos\ReturnController::class, 'process'])->name('returns.process');

            // ── Customers ────────────────────────────────────────────
            Route::get('/customers/search', [Pos\CustomerController::class, 'search'])->name('customers.search');
            Route::post('/customers', [Pos\CustomerController::class, 'store'])->name('customers.store');

            // ── Credit Notes ─────────────────────────────────────────
            Route::get('/credit-note/{code}/validate', [Pos\CreditNoteController::class, 'validate'])->name('credit-note.validate');

            // ── Manager Authorization ────────────────────────────────
            Route::post('/authorize', [Pos\AuthController::class, 'authorizeAction'])->name('authorize')->middleware('throttle:10,1');

            // ── Shift Close ──────────────────────────────────────────
            Route::get('/shift/close', [Pos\ShiftController::class, 'showClose'])->name('shift.close');
            Route::post('/shift/close', [Pos\ShiftController::class, 'close']);
            Route::get('/shift/summary', [Pos\ShiftController::class, 'summary'])->name('shift.summary');
            Route::post('/shift/cash-movement', [Pos\ShiftController::class, 'cashMovement'])->name('shift.cash-movement');

            // ── Reports (supervisor/manager only) ────────────────────
            Route::get('/reports', [Pos\ReportController::class, 'index'])->name('reports');
            Route::get('/reports/daily', [Pos\ReportController::class, 'daily'])->name('reports.daily');
            Route::get('/reports/gst', [Pos\ReportController::class, 'gst'])->name('reports.gst');
            Route::get('/reports/staff', [Pos\ReportController::class, 'staff'])->name('reports.staff');
            Route::get('/reports/top-products', [Pos\ReportController::class, 'topProducts'])->name('reports.top-products');
            Route::get('/reports/inventory-alerts', [Pos\ReportController::class, 'inventoryAlerts'])->name('reports.inventory-alerts');
            Route::get('/reports/monthly', [Pos\ReportController::class, 'monthly'])->name('reports.monthly');
            Route::get('/reports/payment-breakdown', [Pos\ReportController::class, 'paymentBreakdown'])->name('reports.payment-breakdown');
            Route::get('/reports/export', [Pos\ReportController::class, 'exportCsv'])->name('reports.export');

            // ── Audit Log ──────────────────────────────────────────────
            Route::get('/audit', [Pos\AuditController::class, 'index'])->name('audit');

            // ── Store Transfers (Phase 5) ──────────────────────────────
            Route::get('/transfers', [Pos\TransferController::class, 'index'])->name('transfers');
            Route::post('/transfers', [Pos\TransferController::class, 'store'])->name('transfers.store');
            Route::post('/transfers/{transfer}/approve', [Pos\TransferController::class, 'approve'])->name('transfers.approve');
            Route::post('/transfers/{transfer}/reject', [Pos\TransferController::class, 'reject'])->name('transfers.reject');
            Route::post('/transfers/{transfer}/complete', [Pos\TransferController::class, 'complete'])->name('transfers.complete');
        });
    });
});
