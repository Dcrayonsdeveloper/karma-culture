<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\StaffController;
use App\Models\Admin;
use App\Models\Staff;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Admin > Staff: the calendar filter, the export, and the search box that never
 * worked.
 *
 * The search box is the reason this file starts where it does. index() took no
 * Request at all and read exactly one parameter, per_page - which the page
 * offers no control for - while the page offered exactly one parameter, search,
 * which the controller honoured in no way. The two lists were disjoint. Worse
 * than a missing feature, it looked like a working one: the box echoed the term
 * back into itself through value="{{ request('search') }}", so an admin who
 * searched for a colleague and got the whole table back would reasonably
 * conclude the colleague was not there.
 *
 * Everything else here guards the one property the filter and the export have
 * to share. They are built from a single filterRules() and a single filtered(),
 * so an admin who narrows the list and presses Export downloads those rows. The
 * failure this prevents is silent and it is the dangerous kind: a download that
 * arrives, opens, and is simply wider than what was asked for - this file
 * carries staff emails, phone numbers, permission arrays and last-login IPs.
 *
 * Two smaller things are pinned because they fail quietly rather than loudly. A
 * cell opening with = or + is a live formula to Excel and Google Sheets, so
 * every free-text column goes through the de-fanging helper. And /staff/export
 * is a literal path in front of a Route::resource: `show` is excluded today, so
 * nothing shadows it, but restoring show() later would break the download with
 * a 404 that reads like a missing route rather than a swallowed one.
 *
 * The window is deliberately on staff.created_at and not staff.joined_at.
 * joined_at exists, is fillable and is cast to datetime, but nothing has ever
 * written it - so it is NULL on every row the admin panel has made, and a
 * calendar bound to it would return an empty table forever. created_at is what
 * the page's "Joined" column actually prints. The export carries both.
 */
class StaffExportTest extends TestCase
{
    use RefreshDatabase;

    /** The window every date test cuts on. */
    private const WINDOW_FROM = '2026-03-01';
    private const WINDOW_TO = '2026-03-31';

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);

        // The admin guard wants both halves: the users row carries the role and
        // the admins row carries the section permissions.
        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function actingAsAdmin(): self
    {
        $this->actingAs($this->adminUser, 'admin');

        return $this;
    }

    /**
     * One staff member, with its login.
     *
     * created_at is assigned after the insert rather than passed to create():
     * it is not in $fillable, so a mass-assigned value is dropped and every row
     * would silently land on now() - which would make every date assertion in
     * this file pass for the wrong reason.
     *
     * @param  array<string, mixed>  $staffAttributes
     * @param  array<string, mixed>  $userAttributes
     */
    private function staffMember(
        string $employeeId,
        array $staffAttributes = [],
        array $userAttributes = [],
        ?string $createdAt = null,
    ): Staff {
        $user = User::factory()->create(array_merge([
            'role' => 'staff',
        ], $userAttributes));

        $staff = Staff::create(array_merge([
            'user_id' => $user->id,
            'employee_id' => $employeeId,
            'role' => 'cashier',
            'is_active' => true,
        ], $staffAttributes));

        if ($createdAt !== null) {
            $staff->created_at = Carbon::parse($createdAt);
            $staff->save();
        }

        return $staff->fresh();
    }

    /**
     * The four rows every window test needs: one either side of the window and
     * one sitting exactly on each of its two edges.
     *
     * @return array<string, Staff>
     */
    private function seedAroundTheWindow(): array
    {
        return [
            'before' => $this->staffMember('EMP-BEFORE', createdAt: '2026-02-28 23:59:59'),
            'onFrom' => $this->staffMember('EMP-ONFROM', createdAt: self::WINDOW_FROM.' 00:00:00'),
            'inside' => $this->staffMember('EMP-INSIDE', createdAt: '2026-03-15 12:00:00'),
            'onTo' => $this->staffMember('EMP-ONTO', createdAt: self::WINDOW_TO.' 23:59:59'),
            'after' => $this->staffMember('EMP-AFTER', createdAt: '2026-04-01 00:00:00'),
        ];
    }

    /** The employee ids the list is currently showing, in order. */
    private function listedEmployeeIds(string $query = ''): array
    {
        $paginator = $this->actingAsAdmin()
            ->get('/admin/staff'.$query)
            ->assertOk()
            ->viewData('staff');

        return collect($paginator->items())->pluck('employee_id')->all();
    }

    /**
     * The export, parsed. Split on newlines the way SalesExportTest does - none
     * of the seventeen columns can carry one.
     *
     * @return array<int, array<int, string>>
     */
    private function exportRows(string $query = ''): array
    {
        $response = $this->actingAsAdmin()->get('/admin/staff/export'.$query);
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

    // ---------------------------------------------------------------------
    // The dead search box
    // ---------------------------------------------------------------------

    public function test_the_search_box_narrows_the_list_instead_of_being_discarded(): void
    {
        $this->staffMember('EMP-0001', userAttributes: [
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
            'email' => 'priya@karmaa.test',
        ]);
        $this->staffMember('EMP-0002', userAttributes: [
            'first_name' => 'Rahul',
            'last_name' => 'Menon',
            'email' => 'rahul@karmaa.test',
        ]);

        $this->assertSame(['EMP-0001'], $this->listedEmployeeIds('?search=Priya'),
            'The search term was accepted by the form and discarded by the controller.');
    }

    public function test_the_search_matches_an_email_and_an_employee_id_too(): void
    {
        $this->staffMember('EMP-0001', userAttributes: ['email' => 'priya@karmaa.test']);
        $this->staffMember('EMP-9042', userAttributes: ['email' => 'rahul@karmaa.test']);

        $this->assertSame(['EMP-9042'], $this->listedEmployeeIds('?search=rahul@karmaa.test'));
        $this->assertSame(['EMP-9042'], $this->listedEmployeeIds('?search=9042'));
    }

    /**
     * Neither half alone matches a full name, so the CONCAT arm is what makes
     * the obvious search - typing what the Name column shows - work at all.
     */
    public function test_the_search_matches_a_full_name_across_both_halves(): void
    {
        $this->staffMember('EMP-0001', userAttributes: [
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
        ]);
        $this->staffMember('EMP-0002', userAttributes: [
            'first_name' => 'Rahul',
            'last_name' => 'Menon',
        ]);

        $this->assertSame(['EMP-0001'], $this->listedEmployeeIds('?search=Priya+Sharma'));
    }

    public function test_a_like_wildcard_in_the_search_is_escaped(): void
    {
        $this->staffMember('EMP-0001');
        $this->staffMember('EMP-0002');

        // Unescaped, "%" is "match everything" rather than a character nobody
        // has in their name.
        $this->assertSame([], $this->listedEmployeeIds('?search=%25'));
    }

    // ---------------------------------------------------------------------
    // The calendar window
    // ---------------------------------------------------------------------

    public function test_the_date_window_narrows_the_list(): void
    {
        $this->seedAroundTheWindow();

        $listed = $this->listedEmployeeIds('?from='.self::WINDOW_FROM.'&to='.self::WINDOW_TO);

        $this->assertContains('EMP-INSIDE', $listed);
        $this->assertNotContains('EMP-BEFORE', $listed, 'A row before the window survived the filter.');
        $this->assertNotContains('EMP-AFTER', $listed, 'A row after the window survived the filter.');
    }

    /**
     * The edges are where a window is got wrong. A bare >= from and <= to on a
     * datetime column would drop everything on the closing day after midnight,
     * so the ends are start-of-day and end-of-day, not the dates themselves.
     */
    public function test_the_date_window_is_inclusive_at_both_ends(): void
    {
        $this->seedAroundTheWindow();

        $listed = $this->listedEmployeeIds('?from='.self::WINDOW_FROM.'&to='.self::WINDOW_TO);

        $this->assertContains('EMP-ONFROM', $listed, 'A row created on the from date was excluded.');
        $this->assertContains('EMP-ONTO', $listed, 'A row created late on the to date was excluded.');
    }

    public function test_one_open_end_still_cuts(): void
    {
        $this->seedAroundTheWindow();

        $this->assertNotContains('EMP-BEFORE', $this->listedEmployeeIds('?from='.self::WINDOW_FROM));
        $this->assertNotContains('EMP-AFTER', $this->listedEmployeeIds('?to='.self::WINDOW_TO));
    }

    public function test_no_window_shows_the_whole_table(): void
    {
        $this->seedAroundTheWindow();

        $this->assertCount(5, $this->listedEmployeeIds(),
            'An empty calendar has to mean the whole table, not a default window.');
    }

    public function test_an_unparseable_date_is_refused_rather_than_five_hundreding_the_page(): void
    {
        $this->staffMember('EMP-0001');

        $this->actingAsAdmin()
            ->get('/admin/staff?from=not-a-date')
            ->assertSessionHasErrors('from');
    }

    public function test_a_backwards_window_is_refused_before_it_empties_the_table(): void
    {
        $this->actingAsAdmin()
            ->get('/admin/staff?from='.self::WINDOW_TO.'&to='.self::WINDOW_FROM)
            ->assertSessionHasErrors('to');
    }

    public function test_per_page_is_clamped(): void
    {
        $this->actingAsAdmin()->get('/admin/staff?per_page=50')->assertOk();

        // Was read straight off the query string with no rule and no ceiling,
        // so ?per_page=9999999 paginated the whole table in one request.
        $this->actingAsAdmin()
            ->get('/admin/staff?per_page=9999999')
            ->assertSessionHasErrors('per_page');
    }

    // ---------------------------------------------------------------------
    // The role and status filters
    // ---------------------------------------------------------------------

    public function test_the_role_and_status_filters_narrow_the_list(): void
    {
        $this->staffMember('EMP-MGR', ['role' => 'manager', 'is_active' => true]);
        $this->staffMember('EMP-WH', ['role' => 'warehouse', 'is_active' => false]);

        $this->assertSame(['EMP-MGR'], $this->listedEmployeeIds('?role=manager'));
        $this->assertSame(['EMP-WH'], $this->listedEmployeeIds('?status=inactive'));
    }

    public function test_an_unknown_role_is_refused_rather_than_ignored(): void
    {
        $this->actingAsAdmin()
            ->get('/admin/staff?role=director')
            ->assertSessionHasErrors('role');
    }

    // ---------------------------------------------------------------------
    // The export
    // ---------------------------------------------------------------------

    public function test_the_export_downloads_a_csv_naming_every_column(): void
    {
        $this->staffMember('EMP-0001');

        $response = $this->actingAsAdmin()->get('/admin/staff/export');
        $response->assertOk();
        // Not assertSame: Symfony's Response::prepare() appends "; charset=UTF-8"
        // to any text/* type on the way out.
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertMatchesRegularExpression(
            '/filename=.?staff-\d{4}-\d{2}-\d{2}-\d{6}\.csv/',
            (string) $response->headers->get('Content-Disposition'),
            'The seconds in the filename are what stop two exports on one day overwriting each other.'
        );

        $this->assertSame([
            'Staff ID', 'Employee ID', 'User ID', 'Name', 'Email', 'Phone', 'Role', 'Status',
            'Login enabled', 'Store', 'Store code', 'Commission rate (%)', 'Permissions',
            'Joined at', 'Created (Joined)', 'Last login', 'Last login IP',
        ], $this->exportRows()[0]);
    }

    public function test_the_export_carries_the_row_the_page_shows(): void
    {
        $store = Store::create(['name' => 'Kochi Flagship', 'code' => 'KOCHI-1', 'is_active' => true]);

        $this->staffMember(
            'EMP-0007',
            [
                'role' => 'manager',
                'store_id' => $store->id,
                'commission_rate' => 2.5,
                'permissions' => ['dashboard', 'orders'],
                'joined_at' => '2026-03-02 09:00:00',
            ],
            [
                'first_name' => 'Priya',
                'last_name' => 'Sharma',
                'email' => 'priya@karmaa.test',
                'phone' => '9876500123',
                'is_active' => false,
                'last_login_at' => '2026-03-20 08:30:00',
                'last_login_ip' => '203.0.113.9',
            ],
            createdAt: '2026-03-01 10:00:00',
        );

        $row = $this->exportRows()[1];

        $this->assertSame('EMP-0007', $row[1]);
        $this->assertSame('Priya Sharma', $row[3]);
        $this->assertSame('priya@karmaa.test', $row[4]);
        $this->assertSame('9876500123', $row[5]);
        $this->assertSame('Manager', $row[6]);
        $this->assertSame('Active', $row[7]);
        // The second, independent switch: the staff row reads Active while the
        // login itself is disabled, and the page shows only the first of them.
        $this->assertSame('No', $row[8]);
        $this->assertSame('Kochi Flagship', $row[9]);
        $this->assertSame('KOCHI-1', $row[10]);
        // The decimal:2 cast hands back a string; without the (float) hop this
        // is where it would show.
        $this->assertSame('2.50', $row[11]);
        $this->assertSame('dashboard, orders', $row[12]);
    }

    /**
     * The brief asks for both dates because they disagree. The page's "Joined"
     * column prints created_at; joined_at is a separate nullable column nothing
     * writes, so it is blank on every row the panel has ever created.
     */
    public function test_the_export_carries_both_joined_at_and_created_at(): void
    {
        $this->staffMember('EMP-SEEDED', ['joined_at' => '2026-03-02 09:00:00'], createdAt: '2026-03-01 10:00:00');
        $this->staffMember('EMP-PANEL', createdAt: '2026-03-03 10:00:00');

        $rows = [];

        foreach ($this->exportRows() as $row) {
            $rows[$row[1]] = $row;
        }

        $this->assertSame('2026-03-02 09:00', $rows['EMP-SEEDED'][13]);
        $this->assertSame('2026-03-01 10:00', $rows['EMP-SEEDED'][14]);

        // The bug, stated as an assertion: created through the panel, joined_at
        // is never written and only created_at says when this person started.
        $this->assertSame('', $rows['EMP-PANEL'][13]);
        $this->assertSame('2026-03-03 10:00', $rows['EMP-PANEL'][14]);
    }

    /**
     * THE ONE THAT MATTERS. An admin who narrows the list and presses Export
     * must not silently download the whole staff table.
     */
    public function test_the_export_honours_the_same_date_window_as_the_list(): void
    {
        $this->seedAroundTheWindow();

        $csv = $this->actingAsAdmin()
            ->get('/admin/staff/export?from='.self::WINDOW_FROM.'&to='.self::WINDOW_TO)
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('EMP-INSIDE', $csv);
        $this->assertStringContainsString('EMP-ONFROM', $csv, 'The export excluded the from-date edge the list includes.');
        $this->assertStringContainsString('EMP-ONTO', $csv, 'The export excluded the to-date edge the list includes.');
        $this->assertStringNotContainsString('EMP-BEFORE', $csv, 'The export ignored the window the list was taken with.');
        $this->assertStringNotContainsString('EMP-AFTER', $csv, 'The export ignored the window the list was taken with.');
    }

    public function test_the_export_honours_the_search_role_and_status_filters(): void
    {
        $this->staffMember('EMP-KEPT', ['role' => 'manager', 'is_active' => true], [
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
        ]);
        $this->staffMember('EMP-NO-SEARCH', ['role' => 'manager', 'is_active' => true], [
            'first_name' => 'Rahul',
            'last_name' => 'Menon',
        ]);
        $this->staffMember('EMP-NO-ROLE', ['role' => 'warehouse', 'is_active' => true], [
            'first_name' => 'Priya',
            'last_name' => 'Nair',
        ]);
        $this->staffMember('EMP-NO-STATUS', ['role' => 'manager', 'is_active' => false], [
            'first_name' => 'Priya',
            'last_name' => 'Iyer',
        ]);

        $csv = $this->actingAsAdmin()
            ->get('/admin/staff/export?search=Priya&role=manager&status=active')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('EMP-KEPT', $csv);
        $this->assertStringNotContainsString('EMP-NO-SEARCH', $csv);
        $this->assertStringNotContainsString('EMP-NO-ROLE', $csv);
        $this->assertStringNotContainsString('EMP-NO-STATUS', $csv);
    }

    /**
     * A cell opening with =, +, - or @ is a formula, not text, to Excel and to
     * Google Sheets - so a hand-entered employee id or a store named by someone
     * else runs in the spreadsheet of whoever opens the file. A leading tab
     * makes it text and is invisible in the sheet.
     */
    public function test_a_formula_in_a_free_text_cell_is_de_fanged(): void
    {
        $store = Store::create([
            'name' => '+Sneaky Store',
            'code' => 'ST-EVIL',
            'is_active' => true,
        ]);

        $this->staffMember('=EMP-EVIL', ['store_id' => $store->id]);

        $csv = $this->actingAsAdmin()->get('/admin/staff/export')->assertOk()->streamedContent();

        $this->assertStringContainsString("\t=EMP-EVIL", $csv, 'The employee id went out as a live formula.');
        $this->assertStringNotContainsString(',=EMP-EVIL', $csv);
        $this->assertStringContainsString("\t+Sneaky Store", $csv, 'The store name went out as a live formula.');
    }

    public function test_the_export_can_be_taken_as_a_spreadsheet(): void
    {
        $this->staffMember('EMP-0001');

        // Both spellings, because InventoryReportController already branches on
        // 'excel' while naming its file .xlsx - accepting the two is what lets
        // this screen put xlsx on the button without breaking that convention.
        foreach (['xlsx', 'excel'] as $format) {
            $response = $this->actingAsAdmin()->get('/admin/staff/export?format='.$format);
            $response->assertOk();

            $this->assertStringContainsString(
                'spreadsheetml.sheet',
                (string) $response->headers->get('Content-Type'),
                "?format={$format} fell through to a CSV."
            );
            $this->assertStringContainsString('.xlsx', (string) $response->headers->get('Content-Disposition'));
        }
    }

    public function test_an_unknown_format_is_refused_rather_than_guessed_at(): void
    {
        $this->actingAsAdmin()
            ->get('/admin/staff/export?format=pdf')
            ->assertSessionHasErrors('format');
    }

    // ---------------------------------------------------------------------
    // Routing and access
    // ---------------------------------------------------------------------

    /**
     * /staff/export is a literal path in front of Route::resource('staff').
     * `show` is excluded today, so there is no /staff/{staff} to swallow it -
     * but restoring show() later would break the download with a 404 that reads
     * like a missing route rather than a shadowed one, and route-model binding
     * would be off hunting for a Staff with the id "export".
     */
    public function test_the_export_path_is_not_swallowed_by_the_staff_resource_routes(): void
    {
        $route = Route::getRoutes()->match(Request::create('/admin/staff/export', 'GET'));

        $this->assertSame('admin.staff.export', $route->getName());
        $this->assertSame(StaffController::class.'@export', $route->getActionName());

        // And end to end: a CSV, not a rendered page and not a 404.
        $this->staffMember('EMP-0001');
        $response = $this->actingAsAdmin()->get('/admin/staff/export');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    /**
     * The export sits inside the section group, not merely beside it. This file
     * carries emails, phones, permission arrays and last-login IPs, and
     * CheckAdminSection is method-agnostic, so group membership is the whole
     * guard - a route declared one line outside the group would hand the lot to
     * anyone who can reach the admin panel at all.
     */
    public function test_the_export_route_is_inside_the_staff_section_group(): void
    {
        $route = Route::getRoutes()->match(Request::create('/admin/staff/export', 'GET'));

        $this->assertContains('admin.section:staff', $route->gatherMiddleware());
    }

    public function test_staff_without_the_section_are_refused_the_page_and_the_export(): void
    {
        $warehouse = User::factory()->create(['role' => 'staff']);
        Staff::create([
            'user_id' => $warehouse->id,
            'employee_id' => 'EMP-WH-1',
            'role' => 'warehouse',
            'is_active' => true,
            // Warehouse staff hold `orders` for fulfilment. That must not carry
            // the whole staff directory with it.
            'permissions' => ['dashboard', 'catalog', 'orders'],
        ]);

        $this->actingAs($warehouse, 'admin')->get('/admin/staff')->assertForbidden();
        $this->actingAs($warehouse, 'admin')->get('/admin/staff/export')->assertForbidden();
    }

    public function test_staff_granted_the_section_can_take_the_export(): void
    {
        $manager = User::factory()->create(['role' => 'staff']);
        Staff::create([
            'user_id' => $manager->id,
            'employee_id' => 'EMP-MGR-1',
            'role' => 'manager',
            'is_active' => true,
            'permissions' => ['dashboard', 'staff'],
        ]);

        $response = $this->actingAs($manager, 'admin')->get('/admin/staff/export');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    // ---------------------------------------------------------------------
    // The page itself
    // ---------------------------------------------------------------------

    public function test_the_page_offers_the_calendar_and_both_export_links(): void
    {
        $html = $this->actingAsAdmin()->get('/admin/staff')->assertOk()->getContent();

        $this->assertStringContainsString('Export CSV', $html);
        $this->assertStringContainsString('Export Excel', $html);
        $this->assertStringContainsString('name="from"', $html);
        $this->assertStringContainsString('name="to"', $html);
    }

    /**
     * The export links carry whatever is currently narrowing the table, or the
     * download is the whole directory however the page was filtered.
     */
    public function test_the_export_links_carry_the_filters_on_screen(): void
    {
        $html = $this->actingAsAdmin()
            ->get('/admin/staff?from='.self::WINDOW_FROM.'&to='.self::WINDOW_TO.'&role=manager')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('from='.self::WINDOW_FROM, $html);
        $this->assertStringContainsString('role=manager', $html);
    }

    /**
     * Clear all and the empty-state copy both used to test the search term
     * alone, so a date-filtered empty table told the admin to add their first
     * staff member and offered no way back.
     */
    public function test_a_date_filtered_empty_table_offers_a_way_out(): void
    {
        $this->staffMember('EMP-0001', createdAt: '2026-01-05 10:00:00');

        $html = $this->actingAsAdmin()
            ->get('/admin/staff?from='.self::WINDOW_FROM.'&to='.self::WINDOW_TO)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Clear all', $html);
        $this->assertStringContainsString('Try adjusting your filters', $html);
        $this->assertStringNotContainsString('Staff members will appear here once added', $html);
    }
}
