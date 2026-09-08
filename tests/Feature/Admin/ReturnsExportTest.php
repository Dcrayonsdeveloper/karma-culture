<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The Returns screen's calendar filter and its download.
 *
 * Two things here are worth more than the feature itself.
 *
 * The first is that the list and the file cannot disagree. They are built from
 * one private filtered() off one set of rules, and the test that matters most
 * below is the one that narrows the screen to a window and then checks the
 * excluded return is absent from the export as well: an admin who filters and
 * then downloads must never be handed the whole table with the filtered screen
 * still in front of them, because nothing about the file would tell them.
 *
 * The second is /admin/returns/export itself. The show route is /returns/{return}
 * with no numeric pin, so the literal "export" matched the wildcard, implicit
 * binding looked for an OrderReturn with the id "export" and the download 404d.
 * Declaration order is the fix and route:list cannot be run on the dev machine,
 * so the ordering is pinned here instead - once through the router directly, and
 * once by asking for the URL and getting a CSV back.
 *
 * Around those: the window is inclusive at both ends (a return filed at 23:55 on
 * the "to" date is inside it, which bare >= / <= comparisons only get right if
 * they are snapped to whole days); the free text a customer typed is de-fanged
 * before it reaches a spreadsheet; a filter that cannot be honoured is dropped
 * and said out loud rather than 500ing the page; and the export is inside the
 * same admin.section:orders group as the screen, so it cannot become the hole
 * through which a role reads every customer email and refund amount it is not
 * allowed to see.
 */
class ReturnsExportTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Admin',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
            'email' => 'priya@example.test',
            'phone' => '9876543210',
        ]);

        $category = Category::create([
            'name' => 'Returns Export Test',
            'slug' => 'returns-export-test',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Returnable Kurta',
            'slug' => 'returnable-kurta',
            'sku' => 'RET-KUR-1',
            'category_id' => $category->id,
            'price' => 1200,
            'mrp' => 1500,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
    }

    // ----------------------------------------------------------- the calendar

    public function test_the_date_window_narrows_the_list(): void
    {
        $this->returnFiledOn('2026-03-10 09:00', ['return_number' => 'RET-INSIDE']);
        $this->returnFiledOn('2026-01-04 09:00', ['return_number' => 'RET-OUTSIDE']);

        $this->actingAsAdmin()
            ->get('/admin/returns?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->assertSee('RET-INSIDE')
            ->assertDontSee('RET-OUTSIDE');
    }

    /**
     * Both ends are whole days.
     *
     * The from date is taken from 00:00:00 and the to date to 23:59:59, so a
     * return filed five minutes into the first day and one filed five minutes
     * before midnight on the last are both inside the window an admin picked.
     * Comparing the raw timestamps instead would silently drop nearly a whole
     * day off the end of every range.
     */
    public function test_the_window_is_inclusive_at_both_ends(): void
    {
        $this->returnFiledOn('2026-03-01 00:05', ['return_number' => 'RET-FROM-EDGE']);
        $this->returnFiledOn('2026-03-31 23:55', ['return_number' => 'RET-TO-EDGE']);
        $this->returnFiledOn('2026-02-28 23:55', ['return_number' => 'RET-DAY-BEFORE']);
        $this->returnFiledOn('2026-04-01 00:05', ['return_number' => 'RET-DAY-AFTER']);

        $this->actingAsAdmin()
            ->get('/admin/returns?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->assertSee('RET-FROM-EDGE')
            ->assertSee('RET-TO-EDGE')
            ->assertDontSee('RET-DAY-BEFORE')
            ->assertDontSee('RET-DAY-AFTER');
    }

    /**
     * The tiles are counted inside the filters, not over the whole table.
     *
     * They were six bare count() queries, so a window over last week sat under
     * six all-time figures that contradicted the rows beneath them.
     */
    public function test_the_tiles_count_only_what_the_window_shows(): void
    {
        $this->returnFiledOn('2026-03-10 09:00');
        $this->returnFiledOn('2026-03-11 09:00', ['status' => 'completed']);
        $this->returnFiledOn('2026-01-04 09:00');
        $this->returnFiledOn('2026-01-05 09:00', ['status' => 'rejected']);

        $stats = $this->actingAsAdmin()
            ->get('/admin/returns?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->viewData('stats');

        $this->assertSame(2, $stats['total'], 'The Total tile is counting outside the window.');
        $this->assertSame(1, $stats['requested']);
        $this->assertSame(1, $stats['completed']);
        $this->assertSame(0, $stats['rejected']);
    }

    /**
     * A window the admin cannot have meant is corrected, not thrown.
     *
     * $request->validate() would answer a hand-typed ?from=garbage with a
     * redirect back - to the page they came from, or on a cold link to this URL
     * again. A list screen has to render, so the unusable half is dropped and
     * the reason is printed rather than swallowed.
     */
    public function test_an_unparseable_date_still_renders_the_page(): void
    {
        $this->returnFiledOn('2026-03-10 09:00', ['return_number' => 'RET-STILL-HERE']);

        $this->actingAsAdmin()
            ->get('/admin/returns?from=garbage')
            ->assertOk()
            ->assertSee('RET-STILL-HERE')
            ->assertSee('Some filters were ignored');
    }

    /**
     * The other half of "never 500 from the address bar": status and search were
     * unvalidated, so ?status[]=x reached where() as an array and ?search[]=x
     * reached "%{$search}%" as one.
     */
    public function test_an_array_in_the_query_string_does_not_break_the_page(): void
    {
        $this->returnFiledOn('2026-03-10 09:00');

        $this->actingAsAdmin()->get('/admin/returns?status[]=requested')->assertOk();
        $this->actingAsAdmin()->get('/admin/returns?search[]=RET')->assertOk();
        // ceil($total / 0) inside the paginator.
        $this->actingAsAdmin()->get('/admin/returns?per_page=0')->assertOk();
    }

    /**
     * The calendar is the shared component, and it is a sibling of the search
     * form rather than a child of it.
     *
     * It renders its own <form>, and a form nested inside another one has its
     * fields hoisted into the outer one by the browser - the window would be
     * submitted as part of a search and the Apply button would do nothing.
     * AdminFormNestingTest sweeps every admin view for that; this pins the
     * component onto this page in the first place.
     */
    public function test_the_page_renders_the_shared_date_range_filter(): void
    {
        $html = $this->actingAsAdmin()->get('/admin/returns')->assertOk()->getContent();

        $this->assertStringContainsString('data-date-range-filter', $html);
        $this->assertStringContainsString('name="from"', $html);
        $this->assertStringContainsString('name="to"', $html);

        $searchBox = strpos($html, 'placeholder="Search returns"');
        $this->assertNotFalse($searchBox);

        $this->assertGreaterThan(
            strpos($html, '</form>', $searchBox),
            strpos($html, 'data-date-range-filter'),
            'The date range filter is inside the search form rather than beside it.'
        );
    }

    public function test_the_page_offers_both_downloads_carrying_the_filters_on_screen(): void
    {
        $html = $this->actingAsAdmin()
            ->get('/admin/returns?status=requested&from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->assertSee('Export CSV')
            ->assertSee('Export Excel')
            ->getContent();

        $this->assertMatchesRegularExpression(
            '#/admin/returns/export\?[^"]*from=2026-03-01#',
            $html,
            'The export links do not carry the window that is on screen.'
        );
        $this->assertMatchesRegularExpression(
            '#/admin/returns/export\?[^"]*format=xlsx#',
            $html,
            'There is no Export Excel link.'
        );
    }

    // ------------------------------------------------------------- the export

    public function test_the_export_downloads_a_csv_named_for_the_screen(): void
    {
        $this->returnFiledOn('2026-03-10 09:00');

        $response = $this->actingAsAdmin()->get('/admin/returns/export')->assertOk();

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertMatchesRegularExpression(
            '/returns-\d{4}-\d{2}-\d{2}-\d{6}\.csv/',
            (string) $response->headers->get('content-disposition'),
            'The filename has lost the timestamp that stops two exports overwriting each other.'
        );
    }

    public function test_the_header_row_names_every_column(): void
    {
        $rows = $this->exportRows();

        $this->assertSame([
            'Return',
            'Order',
            'Requested at',
            'Customer',
            'Email',
            'Phone',
            'Type',
            'Status',
            'Reason',
            'Description',
            'Items',
            'Units',
            'Refund amount',
            'Refund method',
            'Refund preference',
            'Refund coupon',
            'Order total',
            'Pickup partner',
            'Pickup partner ID',
            'Processed by',
            'Approved at',
            'Pickup scheduled at',
            'Picked up at',
            'Completed at',
            'Exchange order',
            'Last updated',
        ], $rows[0]);
    }

    /**
     * THE one. The list and the file are cut by the same window.
     *
     * Not "the export has a from parameter" - that it agrees with the screen the
     * admin was looking at when they pressed the button. If this ever fails, the
     * two have been allowed to grow separate query builders again.
     */
    public function test_the_export_honours_the_same_window_as_the_list(): void
    {
        $this->returnFiledOn('2026-03-10 09:00', ['return_number' => 'RET-INSIDE']);
        $this->returnFiledOn('2026-01-04 09:00', ['return_number' => 'RET-OUTSIDE']);

        $window = 'from=2026-03-01&to=2026-03-31';

        $this->actingAsAdmin()
            ->get('/admin/returns?'.$window)
            ->assertOk()
            ->assertSee('RET-INSIDE')
            ->assertDontSee('RET-OUTSIDE');

        $csv = $this->exportContent($window);

        $this->assertStringContainsString('RET-INSIDE', $csv);
        $this->assertStringNotContainsString(
            'RET-OUTSIDE',
            $csv,
            'The export ignored the window the list was filtered by.'
        );
        // Header plus exactly the one row that was on screen.
        $this->assertCount(2, $this->exportRows($window));
    }

    public function test_the_export_honours_the_status_and_search_filters_too(): void
    {
        $this->returnFiledOn('2026-03-10 09:00', ['return_number' => 'RET-DONE', 'status' => 'completed']);
        $this->returnFiledOn('2026-03-11 09:00', ['return_number' => 'RET-OPEN', 'status' => 'requested']);

        $byStatus = $this->exportContent('status=completed');
        $this->assertStringContainsString('RET-DONE', $byStatus);
        $this->assertStringNotContainsString('RET-OPEN', $byStatus);

        $bySearch = $this->exportContent('search=RET-OPEN');
        $this->assertStringContainsString('RET-OPEN', $bySearch);
        $this->assertStringNotContainsString('RET-DONE', $bySearch);
    }

    /**
     * A customer types the reason and the description. Excel and Google Sheets
     * treat a cell opening with =, +, - or @ as a formula, so without the tab
     * the trait prefixes, a return filed as =HYPERLINK("http://evil","hi") is a
     * live link in the spreadsheet of whoever opens the file.
     */
    public function test_free_text_a_customer_typed_is_de_fanged(): void
    {
        $this->returnFiledOn('2026-03-10 09:00', [
            'return_number' => 'RET-FORMULA',
            'reason' => '=HYPERLINK("http://evil","hi")',
            'description' => '+91 phone lookup',
        ]);

        $csv = $this->exportContent();

        $this->assertStringContainsString("\t=HYPERLINK", $csv, 'The formula reached the file unprefixed.');
        $this->assertStringNotContainsString('"=HYPERLINK', $csv);
        $this->assertStringContainsString("\t+91 phone lookup", $csv);

        // And the cell is still readable once the sheet has it as text.
        $row = $this->exportRowFor('RET-FORMULA');
        $this->assertNotNull($row);
        $this->assertSame("\t=HYPERLINK(\"http://evil\",\"hi\")", $row[8]);
    }

    /**
     * The columns the screen has no room for are the reason the export exists:
     * the pickup partner, who processed it, how many lines and how many units
     * came back, and the contact details for the person to ring about it.
     */
    public function test_the_export_carries_what_the_screen_cannot_show(): void
    {
        $return = $this->returnFiledOn('2026-03-10 09:00', [
            'return_number' => 'RET-FULL',
            'status' => 'completed',
            'refund_amount' => 1200.5,
            'refund_method' => 'wallet',
            'description' => 'Refund notes: settled from the wallet',
        ]);

        $partnerUser = User::factory()->create([
            'role' => 'customer',
            'first_name' => 'Ravi',
            'last_name' => 'Kumar',
        ]);
        $partner = DeliveryPartner::create([
            'user_id' => $partnerUser->id,
            'partner_id' => 'DP-0007',
            'company_name' => 'Kumar Logistics',
            'is_active' => true,
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $return->order_id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'sku' => $this->product->sku,
            'mrp' => 1500,
            'price' => 1200,
            'quantity' => 3,
            'total' => 3600,
        ]);
        ReturnItem::create([
            'return_id' => $return->id,
            'order_item_id' => $orderItem->id,
            'quantity' => 3,
        ]);

        $return->forceFill([
            'pickup_partner_id' => $partner->id,
            'processed_by' => $this->adminUser->id,
            'approved_at' => Carbon::parse('2026-03-11 10:00'),
            'pickup_scheduled_at' => Carbon::parse('2026-03-12 10:00'),
            'picked_up_at' => Carbon::parse('2026-03-13 10:00'),
            'completed_at' => Carbon::parse('2026-03-14 10:00'),
        ])->save();

        $row = $this->exportRowFor('RET-FULL');

        $this->assertNotNull($row, 'The return is missing from the export.');
        $this->assertSame('Priya Sharma', $row[3]);
        $this->assertSame('priya@example.test', $row[4]);
        // The snapshot phone, not the account's: Order::customer_phone prefers
        // what was typed at checkout, which is the number the shop dials.
        $this->assertSame('9000000000', $row[5]);
        $this->assertSame('Return', $row[6]);
        $this->assertSame('Completed', $row[7]);
        $this->assertSame('Refund notes: settled from the wallet', $row[9]);
        $this->assertSame('1', $row[10], 'Items is not the count of return lines.');
        $this->assertSame('3', $row[11], 'Units is not the sum of the returned quantities.');
        $this->assertSame('1200.50', $row[12]);
        $this->assertSame('wallet', $row[13]);
        // Preference is blank here on purpose: this return predates the
        // refund-preference field, and a column that invents 'refund' for a
        // customer who was never asked would read as a choice they made.
        $this->assertSame('', $row[14]);
        $this->assertSame('', $row[15]);
        $this->assertSame('Ravi Kumar', $row[17]);
        $this->assertSame('DP-0007', $row[18]);
        $this->assertSame('Ada Admin', $row[19]);
        $this->assertSame('2026-03-11 10:00', $row[20]);
        $this->assertSame('2026-03-12 10:00', $row[21]);
        $this->assertSame('2026-03-13 10:00', $row[22]);
        $this->assertSame('2026-03-14 10:00', $row[23]);
    }

    /**
     * The refund preference is not the refund method, and the file needs both.
     *
     * Above the order threshold the return form promises store credit and
     * offers the customer nothing else, so `coupon` in the method column can
     * mean either "they chose it" or "the shop owed it". Only the preference
     * separates the two, and an accounting export that cannot tell them apart
     * is the one an admin would have to go back to the screen to resolve. The
     * issued voucher rides along for the same reason: it is the thing the
     * money actually became.
     */
    public function test_a_store_credit_return_exports_the_preference_and_the_voucher(): void
    {
        $return = $this->returnFiledOn('2026-03-10 09:00', [
            'return_number' => 'RET-CREDIT',
            'refund_method' => 'coupon',
            'refund_preference' => OrderReturn::PREFERENCE_COUPON,
        ]);

        $row = $this->exportRowFor('RET-CREDIT');

        $this->assertNotNull($row, 'The store-credit return is missing from the export.');
        $this->assertSame('coupon', $row[13]);
        $this->assertSame('Store coupon', $row[14], 'The stated preference is not in the file.');
    }

    /**
     * A guest return has no user on either side of it - returns.user_id went
     * nullable and orders.user_id is nullable for guest checkout - so the
     * checkout snapshot is the only copy of the address left. Reading
     * order->user->email alone would export a blank contact for every one.
     */
    public function test_a_guest_return_still_exports_a_name_and_an_email(): void
    {
        $this->returnFiledOn('2026-03-10 09:00', ['return_number' => 'RET-GUEST'], guest: true);

        $row = $this->exportRowFor('RET-GUEST');

        $this->assertNotNull($row);
        $this->assertSame('Walk-in Guest', $row[3]);
        $this->assertSame('guest@example.test', $row[4]);
        $this->assertSame('9000000000', $row[5]);
    }

    /**
     * ?format is the one parameter the four exports share, and it is the trait
     * that decides - xlsx and excel both mean a spreadsheet, anything else
     * (missing, empty, csv) means CSV. Without this, ?format=xlsx quietly
     * handed back a CSV, because the older InventoryReport convention spells it
     * "excel".
     */
    public function test_asking_for_a_spreadsheet_returns_one(): void
    {
        $this->returnFiledOn('2026-03-10 09:00');

        foreach (['xlsx', 'excel'] as $spelling) {
            $response = $this->actingAsAdmin()
                ->get('/admin/returns/export?format='.$spelling)
                ->assertOk();

            $this->assertStringContainsString(
                'spreadsheetml',
                (string) $response->headers->get('content-type'),
                "?format={$spelling} did not produce a spreadsheet."
            );
            $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        }

        // Anything else is the streaming CSV, which is the one with no row cap.
        $this->assertStringContainsString(
            'text/csv',
            (string) $this->actingAsAdmin()->get('/admin/returns/export?format=csv')->headers->get('content-type')
        );
    }

    // -------------------------------------------------------------- the route

    /**
     * /returns/{return} has no numeric pin of its own on the show action's
     * left, so the literal path has to be registered first. If this fails, the
     * download is a 404 that reads like a missing route rather than a shadowed
     * one.
     */
    public function test_the_export_path_is_not_swallowed_by_the_show_wildcard(): void
    {
        $matched = Route::getRoutes()->match(Request::create('/admin/returns/export', 'GET'));

        $this->assertSame(
            'admin.returns.export',
            $matched->getName(),
            '/admin/returns/export resolves to '.($matched->getName() ?? $matched->uri()).' - the wildcard got there first.'
        );

        // And end to end: a file comes back, not a page and not a 404.
        $response = $this->actingAsAdmin()->get('/admin/returns/export')->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringNotContainsString('<html', $response->streamedContent());
    }

    // ---------------------------------------------------------- who may do it

    public function test_the_export_requires_an_authenticated_admin(): void
    {
        $this->get('/admin/returns/export')->assertRedirect(route('admin.login'));
    }

    /**
     * The export lives inside the same admin.section:orders group as the screen,
     * so it cannot be the back door through which a role that is refused the
     * page pulls every customer email and refund amount as a file.
     */
    public function test_staff_without_the_orders_section_cannot_export(): void
    {
        $staffUser = User::factory()->create(['role' => 'staff']);
        Staff::create([
            'user_id' => $staffUser->id,
            'employee_id' => 'EMP-CAT-1',
            'role' => 'warehouse',
            'is_active' => true,
            'permissions' => ['dashboard', 'catalog'],
        ]);

        $this->actingAs($staffUser, 'admin')->get('/admin/returns')->assertForbidden();
        $this->actingAs($staffUser, 'admin')->get('/admin/returns/export')->assertForbidden();
    }

    public function test_staff_granted_the_orders_section_can_export(): void
    {
        $this->returnFiledOn('2026-03-10 09:00', ['return_number' => 'RET-VISIBLE']);

        $staffUser = User::factory()->create(['role' => 'staff']);
        Staff::create([
            'user_id' => $staffUser->id,
            'employee_id' => 'EMP-ORD-1',
            'role' => 'support',
            'is_active' => true,
            'permissions' => ['dashboard', 'orders'],
        ]);

        $response = $this->actingAs($staffUser, 'admin')
            ->get('/admin/returns/export')
            ->assertOk();

        $this->assertStringContainsString('RET-VISIBLE', $response->streamedContent());
    }

    // --------------------------------------------------------------- fixtures

    private function actingAsAdmin(): self
    {
        return $this->actingAs($this->adminUser, 'admin');
    }

    /**
     * One return, filed at a given moment.
     *
     * created_at IS the request timestamp on this table and it is not fillable,
     * so it is written afterwards with the timestamps off - letting save() touch
     * updated_at would be harmless, but the export prints that column too and a
     * fixture should not decide what it says.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function returnFiledOn(string $filedAt, array $attributes = [], bool $guest = false): OrderReturn
    {
        // A guest return has no user on either side of it, which is the case
        // every customer column has to survive.
        $customer = $guest ? null : $this->customer;

        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(10)),
            'user_id' => $customer?->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'subtotal' => 1200,
            'total' => 1200,
            'currency' => 'INR',
            // What a guest checkout leaves behind, and the only address a
            // soft-deleted customer leaves behind either.
            'shipping_address_snapshot' => [
                'name' => 'Walk-in Guest',
                'email' => 'guest@example.test',
                'phone' => '9000000000',
            ],
        ]);

        $return = OrderReturn::create(array_merge([
            'return_number' => 'RET-'.strtoupper(Str::random(8)),
            'order_id' => $order->id,
            'user_id' => $customer?->id,
            'type' => 'return',
            'status' => 'requested',
            'reason' => 'Too small',
            'refund_amount' => 0,
        ], $attributes));

        $return->created_at = Carbon::parse($filedAt);
        $return->timestamps = false;
        $return->save();
        $return->timestamps = true;

        return $return->refresh();
    }

    /** The export as one string. */
    private function exportContent(string $query = ''): string
    {
        return $this->actingAsAdmin()
            ->get('/admin/returns/export'.($query === '' ? '' : '?'.$query))
            ->assertOk()
            ->streamedContent();
    }

    /**
     * The export parsed back into rows, header first.
     *
     * Through a stream rather than by splitting on newlines: the description
     * column carries the customer's own note and processRefund() appends to it
     * with a blank line, so a row can legitimately span several lines.
     *
     * @return array<int, array<int, string|null>>
     */
    private function exportRows(string $query = ''): array
    {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $this->exportContent($query));
        rewind($handle);

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * The one exported row for a return number, or null.
     *
     * @return array<int, string|null>|null
     */
    private function exportRowFor(string $returnNumber, string $query = ''): ?array
    {
        foreach (array_slice($this->exportRows($query), 1) as $row) {
            if (trim((string) $row[0]) === $returnNumber) {
                return $row;
            }
        }

        return null;
    }

    /**
     * A window that cannot be honoured is dropped whole.
     *
     * This screen validates without throwing, because a list has to render
     * whatever is in the address bar - but $validator->valid() drops only the
     * attribute that failed, and for a backwards range that is `to` alone. That
     * left `from` standing and answered with every return from that date
     * onward: a half-window nobody asked for, wider than the request, in a file
     * with nothing in it to say the upper bound had been thrown away.
     */
    public function test_a_backwards_window_is_dropped_whole_rather_than_half_applied(): void
    {
        $this->returnFiledOn('2026-01-04 09:00', ['return_number' => 'RET-EARLY']);
        $this->returnFiledOn('2026-09-20 09:00', ['return_number' => 'RET-LATE']);

        $window = 'from=2026-09-08&to=2026-09-01';

        $html = $this->actingAsAdmin()
            ->get('/admin/returns?'.$window)
            ->assertOk()
            ->getContent();

        // Nothing is hidden, and the banner says why.
        $this->assertStringContainsString('Some filters were ignored', $html);
        $this->assertStringContainsString('RET-EARLY', $html);
        $this->assertStringContainsString('RET-LATE', $html);

        $csv = $this->exportContent($window);

        $this->assertStringContainsString(
            'RET-EARLY',
            $csv,
            'The export kept half of a window the admin never asked for.'
        );
        $this->assertStringContainsString('RET-LATE', $csv);
    }

    /**
     * Submitting the search box empty is not a filter.
     *
     * The form posts ?search= with nothing in it, ConvertEmptyStringsToNull
     * turns that into a present null key, and request()->hasAny() called it a
     * filter - so a shop with no returns yet was offered a "Clear all" link and
     * told to adjust filters it had never set. The other three screens all test
     * filled(), and the controller maps the empty term to null, so the
     * indicator was disagreeing with the query that actually ran.
     */
    public function test_an_empty_search_submit_is_not_treated_as_a_filter(): void
    {
        $html = $this->actingAsAdmin()
            ->get('/admin/returns?search=')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Clear all', $html);
        $this->assertStringContainsString(
            'Return requests will appear here when customers submit them',
            $html,
            'An empty search told a brand new shop to adjust filters it never set.'
        );
    }

}
