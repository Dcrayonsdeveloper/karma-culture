<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The Orders screen's calendar window and its download.
 *
 * The wiring for a date filter was already in OrderController::index() - it
 * read ?date_from and ?date_to and cut the list on them - and the page rendered
 * no control for either. The same was true of ?payment_status. Both were listed
 * in the blade's "Clear all" check, so the screen offered to clear filters it
 * had no way to set: the feature had been half-built and then forgotten, and
 * the only way to use it was to type the URL by hand.
 *
 * What these pin, in order of how badly each would hurt:
 *
 *  - The export and the list come off ONE builder and ONE rule set. An admin
 *    who narrows to a week and presses Export must not receive the whole table;
 *    a report that quietly holds more than it says it does is the one failure
 *    here that gets acted on rather than noticed.
 *  - The window is inclusive at both ends. Bare >= startOfDay / <= endOfDay
 *    replaced whereDate() so the index on orders.created_at can be used, and
 *    the easy way to get that wrong is to compare against midnight and lose
 *    every order placed on the closing day.
 *  - /admin/orders/export is not swallowed by /admin/orders/{order}. An
 *    unconstrained wildcard matches the literal "export", and route-model
 *    binding then 404s the download - a failure that reads like a missing route
 *    rather than a shadowed one.
 *  - A customer-supplied cell that opens with "=" is text, not a formula. The
 *    customer name, the shipping address, the coupon code and the carrier are
 *    all typed by someone outside this building.
 *  - The export lives inside admin.section:orders, so it can never hand order
 *    data - names, phone numbers, full addresses - to a role that cannot open
 *    the list it came from.
 */
class OrdersExportTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Order',
            'last_name' => 'Admin',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    /**
     * An order placed on $placedOn.
     *
     * created_at is written after the fact through a query with the timestamps
     * turned off: Eloquent stamps it on insert, and Builder::update() would
     * stamp updated_at on the way back out.
     */
    private function orderPlacedOn(string $placedOn, string $number, array $attributes = []): Order
    {
        $order = Order::create(array_merge([
            'order_number' => $number,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 1000,
            'source' => 'web',
        ], $attributes));

        $when = \Illuminate\Support\Carbon::parse($placedOn);

        Order::withoutTimestamps(
            fn () => $order->newQuery()->where('id', $order->id)->update(['created_at' => $when])
        );

        return $order->fresh();
    }

    private function actingAsAdmin(): self
    {
        $this->actingAs($this->adminUser, 'admin');

        return $this;
    }

    private function csvFor(string $query = ''): string
    {
        $response = $this->actingAsAdmin()->get('/admin/orders/export'.$query);
        $response->assertOk();

        return $response->streamedContent();
    }

    // ------------------------------------------------------------- the filter

    public function test_the_calendar_window_narrows_the_list(): void
    {
        $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-INSIDE-1');
        $this->orderPlacedOn('2026-02-01 09:00:00', 'ORD-BEFORE-1');
        $this->orderPlacedOn('2026-04-20 09:00:00', 'ORD-AFTER-1');

        $html = $this->actingAsAdmin()
            ->get('/admin/orders?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('ORD-INSIDE-1', $html, 'An order inside the picked window is missing from the list.');
        $this->assertStringNotContainsString('ORD-BEFORE-1', $html, 'An order before the picked window is still listed.');
        $this->assertStringNotContainsString('ORD-AFTER-1', $html, 'An order after the picked window is still listed.');
    }

    /**
     * Both edges, and both edges late in the day.
     *
     * 23:50 on the closing date is the case whereDate() got right by accident
     * and a naive `<= $to` gets wrong: comparing against the bare date is
     * comparing against midnight, which throws away everything sold that day.
     */
    public function test_the_window_is_inclusive_at_both_ends(): void
    {
        $this->orderPlacedOn('2026-03-01 00:05:00', 'ORD-EDGE-FROM');
        $this->orderPlacedOn('2026-03-31 23:50:00', 'ORD-EDGE-TO');
        $this->orderPlacedOn('2026-02-28 23:50:00', 'ORD-JUST-BEFORE');

        $html = $this->actingAsAdmin()
            ->get('/admin/orders?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('ORD-EDGE-FROM', $html, 'An order placed on the from date was excluded.');
        $this->assertStringContainsString('ORD-EDGE-TO', $html, 'An order placed late on the to date was excluded - the window closes at midnight, not at the end of the day.');
        $this->assertStringNotContainsString('ORD-JUST-BEFORE', $html);
    }

    /**
     * The older parameter names still work. Something may still link with them,
     * and validate() returns only the keys it has rules for - so dropping them
     * from the rule set would not merely stop validating them, it would strip
     * them and the link would silently stop filtering.
     */
    public function test_the_older_date_from_and_date_to_aliases_still_filter(): void
    {
        $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-ALIAS-IN');
        $this->orderPlacedOn('2026-05-10 09:00:00', 'ORD-ALIAS-OUT');

        $html = $this->actingAsAdmin()
            ->get('/admin/orders?date_from=2026-03-01&date_to=2026-03-31')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('ORD-ALIAS-IN', $html);
        $this->assertStringNotContainsString('ORD-ALIAS-OUT', $html);
    }

    /**
     * The canonical name wins over the alias.
     *
     * The date component carries every other parameter on the URL through as a
     * hidden input, a stale ?date_from included, so picking a new window on
     * such a URL submits both. If the alias won, applying a range would appear
     * to do nothing.
     */
    public function test_the_canonical_from_beats_a_stale_date_from(): void
    {
        $this->orderPlacedOn('2026-06-10 09:00:00', 'ORD-NEW-WINDOW');
        $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-OLD-WINDOW');

        $html = $this->actingAsAdmin()
            ->get('/admin/orders?from=2026-06-01&to=2026-06-30&date_from=2026-03-01&date_to=2026-03-31')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('ORD-NEW-WINDOW', $html, 'The stale date_from alias won over the window that was just picked.');
        $this->assertStringNotContainsString('ORD-OLD-WINDOW', $html);
    }

    /** A page size of zero used to reach paginate(0) and divide by it. */
    public function test_a_zero_page_size_is_rejected_rather_than_dividing_by_zero(): void
    {
        $this->actingAsAdmin()
            ->get('/admin/orders?per_page=0')
            ->assertRedirect();
    }

    public function test_the_page_offers_the_calendar_and_both_export_links(): void
    {
        $html = $this->actingAsAdmin()->get('/admin/orders')->assertOk()->getContent();

        $this->assertStringContainsString('Export CSV', $html);
        $this->assertStringContainsString('Export Excel', $html);
        $this->assertStringContainsString(route('admin.orders.export'), $html);
        $this->assertStringContainsString('data-date-range-filter', $html, 'The date range component is not on the page.');
        $this->assertStringContainsString('name="payment_status"', $html, 'The payment filter the controller honours still has no control.');

        // The date component renders its own <form>. Nested, the browser hoists
        // its fields into the search form and the calendar silently stops
        // submitting - so it has to be a sibling on the rendered page too, not
        // only in the source AdminFormNestingTest sweeps.
        $this->assertSame(0, $this->nestedFormCount($html), 'The orders page nests a <form> inside another form.');
    }

    // ------------------------------------------------------------- the export

    public function test_the_export_downloads_a_csv_named_for_the_screen(): void
    {
        $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-CSV-1');

        $response = $this->actingAsAdmin()->get('/admin/orders/export');
        $response->assertOk();

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment;', $disposition);
        $this->assertStringContainsString('orders-', $disposition);
        $this->assertStringContainsString('.csv', $disposition);

        $this->assertStringContainsString('ORD-CSV-1', $response->streamedContent());
    }

    public function test_the_header_row_names_every_column_the_rows_carry(): void
    {
        // The escape argument spelled out: PHP 8.4 deprecates leaning on its
        // default, and the default is the one that is going to change.
        $header = str_getcsv(strtok($this->csvFor(), "\n"), ',', '"', '\\');

        $this->assertSame([
            'Order', 'Placed on', 'Customer', 'Email', 'Phone',
            'Fulfilment status', 'Payment status', 'Payment method',
            'Subtotal', 'Discount', 'Tax', 'Shipping', 'Total', 'Paid', 'Balance due', 'Currency',
            'Items', 'Units', 'Coupon', 'Source',
            'Shipping address', 'City', 'State', 'Postcode', 'Country',
            'Carrier', 'Tracking number', 'Delivery partner',
            'Confirmed at', 'Packed at', 'Shipped at', 'Out for delivery at',
            'Delivered at', 'Cancelled at', 'Expected delivery', 'Last updated',
        ], $header, 'The header row drifted from the cells exportRow() writes.');
    }

    public function test_a_row_carries_the_money_and_the_contact_details_the_table_hides(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'first_name' => 'Meera',
            'last_name' => 'Nair',
            'email' => 'meera@example.test',
        ]);

        $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-DETAIL-1', [
            'user_id' => $customer->id,
            'subtotal' => 2000,
            'discount' => 150,
            'tax' => 90,
            'shipping_cost' => 60,
            'total' => 2000,
            'paid_amount' => 500,
            'shipping_address_snapshot' => [
                'name' => 'Meera Nair',
                'phone' => '9876500011',
                'address_line_1' => '12 Residency Road',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'postal_code' => '560025',
                'country' => 'IN',
            ],
        ]);

        $csv = $this->csvFor();

        $this->assertStringContainsString('meera@example.test', $csv);
        $this->assertStringContainsString('9876500011', $csv);
        $this->assertStringContainsString('Bengaluru', $csv);
        $this->assertStringContainsString('560025', $csv);
        // Balance due, an accessor with no column behind it: total - paid.
        $this->assertStringContainsString('1500.00', $csv);
        // A bare number, so the column can be summed. @price would make it
        // "₹2,000.00" and the sheet would read it as text.
        $this->assertStringContainsString('2000.00', $csv);
        $this->assertStringNotContainsString('₹', $csv);
    }

    /**
     * THE ONE THAT MATTERS. index() and export() share filterRules() and
     * filtered(); if they ever stop sharing them, an admin downloads more than
     * the list they were looking at and has no way to tell.
     */
    public function test_the_export_carries_the_same_filters_as_the_list(): void
    {
        $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-EXPORT-IN');
        $this->orderPlacedOn('2026-01-05 09:00:00', 'ORD-EXPORT-OUT');

        $csv = $this->csvFor('?from=2026-03-01&to=2026-03-31');

        $this->assertStringContainsString('ORD-EXPORT-IN', $csv);
        $this->assertStringNotContainsString(
            'ORD-EXPORT-OUT',
            $csv,
            'The export ignored the date window the list was taken with.'
        );
    }

    public function test_the_export_honours_the_status_and_payment_filters_too(): void
    {
        $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-KEEP-1', [
            'status' => 'delivered',
            'payment_status' => 'paid',
        ]);
        $this->orderPlacedOn('2026-03-11 09:00:00', 'ORD-DROP-STATUS', [
            'status' => 'cancelled',
            'payment_status' => 'paid',
        ]);
        $this->orderPlacedOn('2026-03-12 09:00:00', 'ORD-DROP-PAYMENT', [
            'status' => 'delivered',
            'payment_status' => 'pending',
        ]);

        $csv = $this->csvFor('?status=delivered&payment_status=paid');

        $this->assertStringContainsString('ORD-KEEP-1', $csv);
        $this->assertStringNotContainsString('ORD-DROP-STATUS', $csv);
        $this->assertStringNotContainsString('ORD-DROP-PAYMENT', $csv);
    }

    /**
     * A customer who names themselves =HYPERLINK(...) must not get that run in
     * the spreadsheet of whoever opens the export. A leading tab makes the cell
     * text; it is invisible in the sheet.
     */
    public function test_a_formula_in_a_customer_supplied_cell_is_de_fanged(): void
    {
        $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-INJECT-1', [
            // A guest order, so customer_name falls back to the snapshot the
            // buyer typed at checkout.
            'user_id' => null,
            'shipping_address_snapshot' => [
                'name' => '=HYPERLINK("http://evil.example","hi")',
                'city' => '+1234',
                'address_line_1' => '9 Formula Lane',
            ],
        ]);

        $csv = $this->csvFor();

        $this->assertStringContainsString("\t=HYPERLINK", $csv, 'The customer name was written as a live formula.');
        $this->assertStringContainsString("\t+1234", $csv, 'A city opening with + was written as a live formula.');
        // fputcsv quotes a field holding commas or quotes, so an un-de-fanged
        // cell would appear as `"=HYPERLINK` - with nothing between.
        $this->assertStringNotContainsString('"=HYPERLINK', $csv);
        $this->assertStringNotContainsString(',+1234,', $csv);
    }

    /**
     * The newest shipment, not the oldest. show() reads shipments->first()
     * while updateStatus() and ship() write through shipments()->latest(), and
     * an order that was re-shipped would export the dead AWB if the export had
     * copied show().
     */
    public function test_the_export_carries_the_newest_shipment_not_the_first(): void
    {
        $order = $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-SHIP-1');

        OrderShipment::create([
            'order_id' => $order->id,
            'carrier' => 'FirstCourier',
            'tracking_number' => 'AWB-DEAD-1',
            'status' => 'failed',
        ]);

        OrderShipment::create([
            'order_id' => $order->id,
            'carrier' => 'SecondCourier',
            'tracking_number' => 'AWB-LIVE-2',
            'status' => 'in_transit',
        ]);

        $csv = $this->csvFor();

        $this->assertStringContainsString('AWB-LIVE-2', $csv);
        $this->assertStringNotContainsString('AWB-DEAD-1', $csv, 'The export picked the oldest shipment, so a re-shipped order carries a dead tracking number.');
    }

    public function test_format_xlsx_returns_a_spreadsheet_and_csv_stays_the_default(): void
    {
        $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-FORMAT-1');

        $xlsx = $this->actingAsAdmin()->get('/admin/orders/export?format=xlsx');
        $xlsx->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $xlsx->headers->get('Content-Type')
        );
        $this->assertStringContainsString('.xlsx', (string) $xlsx->headers->get('Content-Disposition'));

        // "excel" is the third accepted spelling: InventoryReportController
        // branches on it while naming its file .xlsx, so both have to work or
        // the four new screens could not standardise on ?format=xlsx.
        $this->actingAsAdmin()->get('/admin/orders/export?format=excel')->assertOk();

        // Anything else, the empty string and a missing value included, is a
        // CSV - which is why the plain Export CSV link carries no ?format.
        $csv = $this->actingAsAdmin()->get('/admin/orders/export?format=csv');
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('Content-Type'));
    }

    public function test_an_unknown_format_is_refused_rather_than_guessed(): void
    {
        $this->actingAsAdmin()
            ->get('/admin/orders/export?format=pdf')
            ->assertRedirect();
    }

    // -------------------------------------------------------------- the route

    /**
     * /admin/orders/export must resolve to the export, not to show() with an
     * {order} of "export". The wildcard is pinned to digits and the literal is
     * declared first; either alone would do, and both together mean a reshuffle
     * cannot quietly bring the 404 back.
     */
    public function test_the_export_path_is_not_swallowed_by_the_order_wildcard(): void
    {
        $matched = Route::getRoutes()->match(Request::create('/admin/orders/export', 'GET'));

        $this->assertSame('admin.orders.export', $matched->getName());

        $order = $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-WILDCARD-1');

        // And the wildcard still answers for a real order.
        $this->assertSame(
            'admin.orders.show',
            Route::getRoutes()->match(Request::create('/admin/orders/'.$order->id, 'GET'))->getName()
        );
    }

    // ----------------------------------------------------------- the guarding

    public function test_the_export_requires_an_authenticated_admin(): void
    {
        $this->get('/admin/orders/export')->assertRedirect(route('admin.login'));
    }

    public function test_staff_without_the_orders_section_cannot_export(): void
    {
        $staffUser = User::factory()->create(['role' => 'staff']);
        Staff::create([
            'user_id' => $staffUser->id,
            'employee_id' => 'EMP-ORD-DENY',
            'role' => 'support',
            'is_active' => true,
            'permissions' => ['dashboard', 'catalog'],
        ]);

        // The export is gated exactly as the page is - it is inside the same
        // admin.section:orders group - so a role that cannot read the list
        // cannot download it either.
        $this->actingAs($staffUser, 'admin')->get('/admin/orders')->assertForbidden();
        $this->actingAs($staffUser, 'admin')->get('/admin/orders/export')->assertForbidden();
    }

    public function test_staff_granted_the_orders_section_can_export(): void
    {
        $staffUser = User::factory()->create(['role' => 'staff']);
        Staff::create([
            'user_id' => $staffUser->id,
            'employee_id' => 'EMP-ORD-ALLOW',
            'role' => 'warehouse',
            'is_active' => true,
            'permissions' => ['dashboard', 'orders'],
        ]);

        $this->actingAs($staffUser, 'admin')->get('/admin/orders/export')->assertOk();
    }

    private function nestedFormCount(string $html): int
    {
        $depth = 0;
        $nested = 0;

        preg_match_all('/<form\b|<\/form\s*>/', $html, $tags);

        foreach ($tags[0] as $tag) {
            if ($tag[1] === '/') {
                $depth = max(0, $depth - 1);
            } else {
                if ($depth >= 1) {
                    $nested++;
                }
                $depth++;
            }
        }

        return $nested;
    }

    // ------------------------------------------------- list / export parity

    /**
     * Clearing the calendar on a legacy ?date_from bookmark clears it for the
     * download too.
     *
     * The href used to be built from the raw query string, and
     * http_build_query() - which is what route() builds a link with - drops a
     * null. Applying an empty calendar over such a bookmark submits
     * ?from=&to=&date_from=..&date_to=.., the page reads the present-but-empty
     * pair as "no window" and shows the whole table, and the empty pair then
     * vanished out of the export link, leaving the stale aliases to filter the
     * file. The admin downloaded strictly fewer rows than the table they took
     * it from, with nothing on screen or in the file to say so.
     */
    public function test_clearing_the_calendar_over_a_legacy_link_clears_it_for_the_download_too(): void
    {
        $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-LEGACY-MARCH');
        $this->orderPlacedOn('2026-06-10 09:00:00', 'ORD-LEGACY-JUNE');

        $html = $this->actingAsAdmin()
            ->get('/admin/orders?from=&to=&date_from=2026-03-01&date_to=2026-03-31')
            ->assertOk()
            ->getContent();

        // The page dropped the window: present-but-empty beats the alias.
        $this->assertStringContainsString('ORD-LEGACY-MARCH', $html);
        $this->assertStringContainsString('ORD-LEGACY-JUNE', $html);

        // And the link on that page has to agree with it.
        $csv = $this->followExportLink($html);

        $this->assertStringContainsString('ORD-LEGACY-MARCH', $csv);
        $this->assertStringContainsString(
            'ORD-LEGACY-JUNE',
            $csv,
            'The export link kept a window the page had just cleared.'
        );
    }

    /**
     * A window that reads backwards across the two pairs is corrected the same
     * way for the file as for the table.
     *
     * ?from=..&date_to=.. satisfies both rule sets - neither pair is reversed
     * on its own - so applyDateWindow()'s swap is what catches it, and the list
     * renders the corrected range. The export link therefore has to carry the
     * corrected range under the canonical names: handed the raw pair it would
     * have been refused by after_or_equal and redirected, so following an
     * export link from a page that rendered would have produced no file at all.
     */
    public function test_a_cross_pair_backwards_window_downloads_what_the_list_showed(): void
    {
        $this->orderPlacedOn('2026-04-10 09:00:00', 'ORD-XPAIR-IN');
        $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-XPAIR-BEFORE');
        $this->orderPlacedOn('2026-06-10 09:00:00', 'ORD-XPAIR-AFTER');

        $html = $this->actingAsAdmin()
            ->get('/admin/orders?from=2026-05-01&date_to=2026-03-31')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('ORD-XPAIR-IN', $html);
        $this->assertStringNotContainsString('ORD-XPAIR-BEFORE', $html);
        $this->assertStringNotContainsString('ORD-XPAIR-AFTER', $html);

        $csv = $this->followExportLink($html);

        $this->assertStringContainsString('ORD-XPAIR-IN', $csv);
        $this->assertStringNotContainsString('ORD-XPAIR-BEFORE', $csv);
        $this->assertStringNotContainsString('ORD-XPAIR-AFTER', $csv);
    }

    /**
     * The payment method is customer input, so it is de-fanged like the rest.
     *
     * Order::getPaymentMethodAttribute() reads metadata['payment_method'], and
     * the API checkout validates that as a bare required string - no enum. The
     * cell was written as strtoupper($order->payment_method) with no csvCell(),
     * and a case fold neutralises nothing: Excel function names and URL schemes
     * are case-insensitive, and =CMD|'/C CALC'!A1 is already uppercase.
     */
    public function test_a_formula_in_the_payment_method_is_de_fanged(): void
    {
        $this->orderPlacedOn('2026-03-10 09:00:00', 'ORD-PAYFORMULA', [
            'metadata' => ['payment_method' => '=HYPERLINK("http://evil.test","click")'],
        ]);

        $csv = $this->csvFor();

        $this->assertStringContainsString(
            "\t=HYPERLINK",
            $csv,
            'The payment method cell is a live formula in the spreadsheet.'
        );
        $this->assertStringNotContainsString('"=HYPERLINK', $csv);
    }

    /**
     * "Reset" beside the calendar resets the calendar, not the screen.
     *
     * The component pointed it at the bare action URL, which is correct on the
     * report screens it was written for - the range is the only filter there -
     * and wrong here: it sits inside the date row, reads as "reset the dates",
     * and threw away the status tab and the search term as well, next to a
     * "Clear all" link that already resolved to exactly that URL.
     */
    public function test_the_calendar_reset_link_keeps_the_tab_and_the_search(): void
    {
        $html = $this->actingAsAdmin()
            ->get('/admin/orders?status=shipped&search=KK-1001&from=2026-01-01&to=2026-01-31')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '#href="[^"]*status=shipped[^"]*"[^>]*>Reset<#',
            $html,
            'Reset throws away the tab and the search along with the dates.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '#href="[^"]*from=2026-01-01[^"]*"[^>]*>Reset<#',
            $html,
            'Reset carries the window it is supposed to clear.'
        );
    }

    /**
     * Follow the page's own "Export CSV" link, rather than guessing the URL.
     *
     * That is the whole point of these two: the divergence was never in
     * export(), it was in what the page put on the href.
     */
    private function followExportLink(string $html): string
    {
        preg_match('#href="([^"]*/admin/orders/export[^"]*)"#', $html, $match);
        $this->assertNotEmpty($match, 'The page carries no export link.');

        $href = html_entity_decode($match[1], ENT_QUOTES);
        $parts = parse_url($href);
        $uri = $parts['path'].(isset($parts['query']) ? '?'.$parts['query'] : '');

        $response = $this->actingAsAdmin()->get($uri);
        $response->assertOk();

        return $response->streamedContent();
    }

}
