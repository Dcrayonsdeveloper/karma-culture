<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Staff;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Admin > Customers: the calendar window and the export taken through it.
 *
 * CustomerController::index() had honoured ?from and ?to since it was written,
 * and the blade had never rendered a control for either - so the filter was
 * reachable only by hand-typing a URL, and the "Clear all" link offered to
 * clear filters nobody could set. Making them reachable is what these tests
 * are here to keep honest, in three ways the code cannot state for itself:
 *
 *  - The window is a whole-day range, inclusive at both ends. It is applied
 *    with bare >= / <= comparisons rather than whereDate(), because DATE() on
 *    the column throws away the only index users.created_at has - and the
 *    boundary rows are exactly what a comparison written one character wrong
 *    silently drops.
 *  - The file agrees with the list. index() and export() validate with the
 *    same filterRules() and build from the same filtered() builder, so an
 *    admin who narrows the table and presses Export cannot be handed the whole
 *    customer book. That is a property of two methods staying in step, which
 *    is precisely the kind that rots quietly, so it is pinned mechanically.
 *  - /admin/customers/export is a literal path declared in a group that also
 *    registers GET /admin/customers/{customer}. Declaration order is the only
 *    thing keeping the wildcard from swallowing it and 404ing the download
 *    while binding hunts for a User with the id "export".
 *
 * Plus the two things this particular file makes dangerous: it carries email,
 * phone, home address and last-login IP, so the section middleware is load
 * bearing; and the name in it is text a shopper typed, so a customer who
 * registers as =HYPERLINK(...) must not get that executed in the spreadsheet
 * of whoever opens the export.
 */
class CustomersExportTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    /**
     * A customer who signed up at a given moment.
     *
     * created_at is passed through the factory rather than written afterwards:
     * updateTimestamps() leaves a created_at that is already dirty alone, so
     * the row lands with the signup date the test asked for.
     */
    private function customer(string $email, string $signedUpAt, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'email' => $email,
            'created_at' => Carbon::parse($signedUpAt),
            'updated_at' => Carbon::parse($signedUpAt),
        ], $attributes));
    }

    /** @return \Illuminate\Testing\TestResponse */
    private function adminGet(string $url)
    {
        return $this->actingAs($this->adminUser, 'admin')->get($url);
    }

    /**
     * The export body, parsed back into rows.
     *
     * @return array<int, array<int, string|null>>
     */
    private function exportRows(string $query = ''): array
    {
        $response = $this->adminGet('/admin/customers/export'.$query);
        $response->assertOk();

        $rows = [];

        foreach (explode("\n", trim($response->streamedContent())) as $line) {
            $line = trim($line, "\r");

            if ($line !== '') {
                $rows[] = str_getcsv($line);
            }
        }

        return $rows;
    }

    // ---------------------------------------------------------------- the list

    public function test_the_date_window_narrows_the_list(): void
    {
        $this->customer('before@example.test', '2026-05-14 10:00:00');
        $this->customer('inside@example.test', '2026-06-15 10:00:00');
        $this->customer('after@example.test', '2026-07-14 10:00:00');

        $response = $this->adminGet('/admin/customers?from=2026-06-01&to=2026-06-30');

        $response->assertOk();
        $response->assertSee('inside@example.test');
        $response->assertDontSee('before@example.test');
        $response->assertDontSee('after@example.test');
    }

    /**
     * The boundary rows, which are the ones a half-open comparison eats.
     *
     * A customer who signed up at one minute past midnight on the from date is
     * inside the window an admin picked, and so is one who signed up at
     * 23:59 on the to date. Getting this wrong loses a whole day at each end
     * and looks like nothing at all on screen.
     */
    public function test_the_window_is_inclusive_at_both_ends(): void
    {
        $this->customer('eve@example.test', '2026-05-31 23:59:59');
        $this->customer('first-day@example.test', '2026-06-01 00:00:01');
        $this->customer('last-day@example.test', '2026-06-30 23:59:59');
        $this->customer('morning-after@example.test', '2026-07-01 00:00:01');

        $response = $this->adminGet('/admin/customers?from=2026-06-01&to=2026-06-30');

        $response->assertOk();
        $response->assertSee('first-day@example.test');
        $response->assertSee('last-day@example.test');
        $response->assertDontSee('eve@example.test');
        $response->assertDontSee('morning-after@example.test');
    }

    /**
     * The stat tiles are counted off the same filtered query as the rows.
     *
     * They used to be three unfiltered totals, so "Total customers" kept
     * reporting the whole book above a table showing a subset. Nobody could
     * see that while the date filter was unreachable.
     */
    public function test_the_tiles_count_the_same_customers_the_table_shows(): void
    {
        $this->customer('one@example.test', '2026-06-05 10:00:00');
        $this->customer('two@example.test', '2026-06-06 10:00:00');
        $this->customer('elsewhere@example.test', '2026-01-06 10:00:00');

        $html = $this->adminGet('/admin/customers?from=2026-06-01&to=2026-06-30')
            ->assertOk()
            ->getContent();

        // The tile reads "2", the number of rows underneath it - not "3", the
        // size of the whole customer book.
        $this->assertMatchesRegularExpression(
            '/Customers matching<\/p>\s*<p[^>]*>2<\/p>/',
            $html,
            'The stat tile contradicts the table it sits above.'
        );
    }

    public function test_a_backwards_window_is_rejected_with_a_reason(): void
    {
        $this->customer('someone@example.test', '2026-06-15 10:00:00');

        $this->adminGet('/admin/customers?from=2026-06-30&to=2026-06-01')
            ->assertSessionHasErrors('to');
    }

    /**
     * ?per_page reached ceil($total / $perPage) uncast and unclamped: "abc" was
     * a TypeError, 0 a DivisionByZeroError and 1000000 the whole users table in
     * one response.
     */
    public function test_per_page_is_validated_rather_than_fatal(): void
    {
        $this->customer('someone@example.test', '2026-06-15 10:00:00');

        $this->adminGet('/admin/customers?per_page=abc')->assertSessionHasErrors('per_page');
        $this->adminGet('/admin/customers?per_page=0')->assertSessionHasErrors('per_page');
        $this->adminGet('/admin/customers?per_page=1000000')->assertSessionHasErrors('per_page');
    }

    // -------------------------------------------------------------- the export

    public function test_the_export_downloads_a_csv_named_by_its_columns(): void
    {
        $this->customer('listed@example.test', '2026-06-15 10:00:00');

        $response = $this->adminGet('/admin/customers/export');

        $response->assertOk();
        $this->assertStringContainsString(
            'text/csv',
            (string) $response->headers->get('Content-Type'),
            'The export did not come back as a CSV.'
        );
        $this->assertMatchesRegularExpression(
            '/filename=.?customers-\d{4}-\d{2}-\d{2}-\d{6}\.csv/',
            (string) $response->headers->get('Content-Disposition'),
            'The filename lost the timestamp that keeps two exports on the same day from overwriting each other.'
        );

        $header = $this->exportRows()[0];

        $this->assertSame([
            'Customer ID', 'Name', 'First name', 'Last name', 'Email', 'Phone',
            'Status', 'Verified', 'Email verified at', 'Phone verified at',
            'Orders', 'Total spent', 'Average order value', 'Last order',
            'Signed up', 'Last login', 'Last login IP',
            'Addresses on file', 'City', 'State', 'Postal code', 'Country', 'Address',
        ], $header);
    }

    public function test_every_row_is_as_wide_as_the_header(): void
    {
        $this->customer('one@example.test', '2026-06-15 10:00:00');
        $this->customer('two@example.test', '2026-06-16 10:00:00');

        $rows = $this->exportRows();
        $width = count($rows[0]);

        $this->assertCount(3, $rows, 'Expected a header and two customers.');

        foreach ($rows as $index => $row) {
            $this->assertCount($width, $row, "Row {$index} is a different width from the header.");
        }
    }

    /**
     * THE ONE THAT MATTERS: the file is the list.
     *
     * index() and export() share filterRules() and filtered(). If they ever
     * drift, an admin who narrows the table to a month and presses Export
     * downloads every customer the shop has ever had, and nothing on screen
     * says so.
     */
    public function test_the_export_honours_the_same_window_as_the_list(): void
    {
        $this->customer('inside@example.test', '2026-06-15 10:00:00');
        $this->customer('outside@example.test', '2026-07-15 10:00:00');

        $body = $this->adminGet('/admin/customers/export?from=2026-06-01&to=2026-06-30')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('inside@example.test', $body);
        $this->assertStringNotContainsString(
            'outside@example.test',
            $body,
            'The export ignored the date window the list was taken with.'
        );
    }

    public function test_the_export_honours_the_search_and_status_filters_too(): void
    {
        $this->customer('active-one@example.test', '2026-06-15 10:00:00', [
            'first_name' => 'Priya',
            'is_active' => true,
        ]);
        $this->customer('deactivated@example.test', '2026-06-16 10:00:00', [
            'first_name' => 'Priya',
            'is_active' => false,
        ]);
        $this->customer('unrelated@example.test', '2026-06-17 10:00:00', [
            'first_name' => 'Rahul',
            'is_active' => true,
        ]);

        $body = $this->adminGet('/admin/customers/export?search=priya&status=active')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('active-one@example.test', $body);
        $this->assertStringNotContainsString('deactivated@example.test', $body);
        $this->assertStringNotContainsString('unrelated@example.test', $body);
    }

    public function test_the_export_carries_the_address_the_page_never_showed(): void
    {
        $customer = $this->customer('shipped@example.test', '2026-06-15 10:00:00', [
            'first_name' => 'Meera',
            'last_name' => 'Nair',
            'phone' => '9876500011',
        ]);

        UserAddress::create([
            'user_id' => $customer->id,
            'first_name' => 'Meera',
            'last_name' => 'Nair',
            'phone' => '9876500011',
            'address_line_1' => '12 Residency Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'postal_code' => '560025',
            'country' => 'IN',
            'is_default' => true,
        ]);

        $rows = $this->exportRows();
        $header = $rows[0];
        $row = array_combine($header, $rows[1]);

        $this->assertSame('Meera Nair', $row['Name']);
        $this->assertSame('shipped@example.test', $row['Email']);
        $this->assertSame('9876500011', $row['Phone']);
        $this->assertSame('Bengaluru', $row['City']);
        $this->assertSame('Karnataka', $row['State']);
        $this->assertSame('560025', $row['Postal code']);
        $this->assertSame('1', $row['Addresses on file']);
        $this->assertStringContainsString('12 Residency Road', $row['Address']);
        $this->assertSame('2026-06-15 10:00', $row['Signed up']);
    }

    /**
     * A shopper picks their own name, and Excel and Google Sheets both treat a
     * leading =, + or - as a formula. Without the de-fang, opening the export
     * runs whatever the shopper typed - on the xlsx path too, because
     * PhpSpreadsheet stores a leading "=" as a live formula.
     */
    public function test_a_formula_a_customer_typed_is_neutralised(): void
    {
        $this->customer('sneaky@example.test', '2026-06-15 10:00:00', [
            'first_name' => '=HYPERLINK("http://evil","hi")',
            'last_name' => 'Sharma',
            'phone' => '+919876500022',
        ]);

        $body = $this->exportBody();

        // The tab prefix is what makes the cell text. It is invisible in the
        // sheet and it is the whole defence.
        $this->assertStringContainsString("\t=hyperlink", $body);
        $this->assertStringContainsString("\t+919876500022", $body);

        // And nothing anywhere in the file opens a field with a live formula.
        $this->assertDoesNotMatchRegularExpression(
            '/(^|[,"])=hyperlink/mi',
            $body,
            'A customer-supplied formula reached the file undefanged.'
        );
    }

    public function test_the_excel_format_comes_back_as_a_spreadsheet(): void
    {
        $this->customer('sheet@example.test', '2026-06-15 10:00:00');

        $response = $this->adminGet('/admin/customers/export?format=xlsx');

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString(
            '.xlsx',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_an_unknown_format_is_refused_rather_than_guessed(): void
    {
        $this->adminGet('/admin/customers/export?format=pdf')->assertSessionHasErrors('format');
    }

    // --------------------------------------------------------------- the route

    /**
     * Route::resource('customers', ...)->except(['create','store','destroy'])
     * does NOT drop `show`, so GET /admin/customers/{customer} is registered.
     * Declared after it, the literal export path is shadowed: binding looks for
     * a User with the id "export" and 404s the download, which reads like a
     * missing route rather than a shadowed one.
     */
    public function test_the_export_path_is_not_swallowed_by_the_customer_wildcard(): void
    {
        $matched = app('router')->getRoutes()
            ->match(Request::create('/admin/customers/export', 'GET'));

        $this->assertSame('admin.customers.export', $matched->getName());

        // And functionally: a shadowed route comes back 404, not a CSV.
        $response = $this->adminGet('/admin/customers/export');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    // ------------------------------------------------------------ the guarding

    /**
     * Warehouse staff hold dashboard, catalog and orders for fulfilment. That
     * must not carry a file of every customer's email, phone, home address and
     * last-login IP with it.
     */
    public function test_staff_without_the_customers_section_cannot_export(): void
    {
        $staffUser = User::factory()->create(['role' => 'staff']);
        Staff::create([
            'user_id' => $staffUser->id,
            'employee_id' => 'EMP-WH-9',
            'role' => 'warehouse',
            'is_active' => true,
            'permissions' => ['dashboard', 'catalog', 'orders'],
        ]);

        $this->actingAs($staffUser, 'admin')->get('/admin/customers')->assertForbidden();
        $this->actingAs($staffUser, 'admin')->get('/admin/customers/export')->assertForbidden();
    }

    public function test_staff_granted_the_customers_section_can_export(): void
    {
        $this->customer('visible@example.test', '2026-06-15 10:00:00');

        $staffUser = User::factory()->create(['role' => 'staff']);
        Staff::create([
            'user_id' => $staffUser->id,
            'employee_id' => 'EMP-SUP-9',
            'role' => 'support',
            'is_active' => true,
            'permissions' => ['dashboard', 'customers'],
        ]);

        $this->actingAs($staffUser, 'admin')->get('/admin/customers')->assertOk();

        $response = $this->actingAs($staffUser, 'admin')->get('/admin/customers/export');
        $response->assertOk();
        $this->assertStringContainsString('visible@example.test', $response->streamedContent());
    }

    public function test_a_signed_out_visitor_cannot_export(): void
    {
        $this->get('/admin/customers/export')->assertRedirect(route('admin.login'));
    }

    private function exportBody(string $query = ''): string
    {
        return $this->adminGet('/admin/customers/export'.$query)
            ->assertOk()
            ->streamedContent();
    }

    /**
     * The first tile counts the filter it claims to be counting.
     *
     * It relabels itself "Customers matching" the moment any filter is set, but
     * the number behind it comes off filtered(applyStatus: false) - so with
     * ?status=inactive it reported the whole window above a table showing only
     * the deactivated accounts. The same number drives the xlsx row-cap
     * warning, which is why it could also promise a truncated spreadsheet to an
     * admin whose slice was forty rows long.
     */
    public function test_the_first_tile_counts_the_status_filter_it_claims_to_match(): void
    {
        $this->customer('live-one@example.test', '2026-06-05 10:00:00');
        $this->customer('live-two@example.test', '2026-06-06 10:00:00');
        $this->customer('switched-off@example.test', '2026-06-07 10:00:00', ['is_active' => false]);

        $html = $this->adminGet('/admin/customers?status=inactive')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/Customers matching<\/p>\s*<p[^>]*>1<\/p>/',
            $html,
            'The tile reports the whole window while claiming to report the match.'
        );
    }

}
