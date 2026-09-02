<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use App\Support\ReportRange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Every reporting screen is filtered by a From/To date pair now, not by a
 * "Last 7 / 30 / 90 days" dropdown.
 *
 * The dropdown could only ever hand the controller one of four numbers. Two
 * date inputs can hand it anything at all, so these pin both halves: the
 * markup offers real date fields, and the window the query string asks for is
 * clamped before it reaches the day-by-day loops that draw the charts.
 */
class ReportDateRangeTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    /** @return list<string> */
    public static function reportScreens(): array
    {
        return [
            'sales' => ['/admin/reports/sales'],
            'analytics' => ['/admin/reports/analytics'],
            'products' => ['/admin/reports/products'],
            'customers' => ['/admin/reports/customers'],
            'chat analytics' => ['/admin/chatbot/analytics'],
            // The dashboard is served at /admin itself, not /admin/dashboard.
            'dashboard' => ['/admin'],
        ];
    }

    /**
     * @dataProvider reportScreens
     */
    public function test_every_reporting_screen_offers_two_date_fields(string $uri): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')->get($uri);

        $response->assertStatus(200);
        $response->assertSee('type="date"', false);
        // The wording the owner asked us to drop, in a selector or a pill.
        $response->assertDontSee('>Last 7 days<', false);
        $response->assertDontSee('>Last 30 days<', false);
        $response->assertDontSee('>Last 90 days<', false);
    }

    /**
     * @dataProvider reportScreens
     */
    public function test_a_picked_window_is_accepted_on_every_screen(string $uri): void
    {
        $from = Carbon::today()->subDays(9)->format('Y-m-d');
        $to = Carbon::today()->format('Y-m-d');

        $this->actingAs($this->adminUser, 'admin')
            ->get($uri . '?from=' . $from . '&to=' . $to . '&start_date=' . $from . '&end_date=' . $to)
            ->assertStatus(200)
            ->assertSee($from, false)
            ->assertSee($to, false);
    }

    /**
     * The dropdown could not express a bad window; two free-text dates can, and
     * every one of these used to reach a query or a loop unguarded.
     *
     * @dataProvider reportScreens
     */
    public function test_a_nonsense_window_still_renders(string $uri): void
    {
        $nonsense = [
            'reversed' => ['from' => Carbon::today()->format('Y-m-d'), 'to' => Carbon::today()->subMonth()->format('Y-m-d')],
            'unparseable' => ['from' => 'lastweek', 'to' => '2026-13-45'],
            'since 1970' => ['from' => '1970-01-01', 'to' => Carbon::today()->format('Y-m-d')],
            'ends in the future' => ['from' => Carbon::today()->subDays(3)->format('Y-m-d'), 'to' => Carbon::today()->addYears(5)->format('Y-m-d')],
            'legacy preset' => ['period' => '90'],
            'legacy junk' => ['period' => '99999999'],
        ];

        foreach ($nonsense as $label => $query) {
            $this->actingAs($this->adminUser, 'admin')
                ->get($uri . '?' . http_build_query($query))
                ->assertStatus(200, "{$uri} fell over on a {$label} window");
        }
    }

    public function test_the_window_is_capped_so_the_day_loops_stay_bounded(): void
    {
        $range = ReportRange::custom(
            \Carbon\CarbonImmutable::parse('1970-01-01'),
            \Carbon\CarbonImmutable::today(),
        );

        $this->assertSame(ReportRange::MAX_DAYS, $range->days());
        $this->assertSame(ReportRange::MAX_DAYS, iterator_count($range->eachDay()));
    }

    public function test_a_reversed_window_is_read_the_way_it_was_meant(): void
    {
        $range = ReportRange::custom(
            \Carbon\CarbonImmutable::today(),
            \Carbon\CarbonImmutable::today()->subDays(6),
        );

        $this->assertSame(7, $range->days());
        $this->assertTrue($range->start->lessThan($range->end));
    }

    public function test_the_window_never_runs_past_today(): void
    {
        $range = ReportRange::custom(
            \Carbon\CarbonImmutable::today()->subDays(3),
            \Carbon\CarbonImmutable::today()->addYears(5),
        );

        $this->assertSame(\Carbon\CarbonImmutable::today()->format('Y-m-d'), $range->toDate());
    }

    /**
     * "vs previous period" compares against an equally long window that ends the
     * day before this one starts — the two must not share a day, or the
     * boundary day is counted on both sides of the comparison.
     */
    public function test_the_comparison_window_does_not_overlap_the_reported_one(): void
    {
        $range = ReportRange::custom(
            \Carbon\CarbonImmutable::today()->subDays(6),
            \Carbon\CarbonImmutable::today(),
        );
        $previous = $range->previous();

        $this->assertSame($range->days(), $previous->days());
        $this->assertTrue($previous->end->lessThan($range->start));
        $this->assertSame(1, (int) $previous->end->startOfDay()->diffInDays($range->start->startOfDay()));
    }

    public function test_an_export_link_carries_the_window_on_screen(): void
    {
        $from = Carbon::today()->subDays(4)->format('Y-m-d');
        $to = Carbon::today()->format('Y-m-d');

        $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/reports/products?from=' . $from . '&to=' . $to)
            ->assertStatus(200)
            ->assertSee('from=' . $from, false)
            ->assertSee('to=' . $to, false);
    }
}
