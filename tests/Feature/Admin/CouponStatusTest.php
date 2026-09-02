<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin discounts index decided "is this coupon live?" twice, and the two
 * answers came from different code.
 *
 * The row badge came from Coupon::isValid(), which weighs is_active, starts_at,
 * expires_at and the usage cap. The tab filter was separate SQL that weighed
 * only is_active and expires_at. So a coupon redeemed to its limit, or one that
 * had not started yet, was listed under "Active" while its own badge in that
 * same row read "Inactive" - and a coupon that was merely switched off had no
 * tab at all, so it could not be filtered to.
 *
 * Both sides now come from Coupon::status() and ->statusIs(), written to mirror
 * each other. These tests hold them together.
 */
class CouponStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'role'       => 'admin',
        ]);

        Admin::create([
            'user_id'   => $this->adminUser->id,
            'role'      => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function coupon(string $code, array $attrs = []): Coupon
    {
        return Coupon::create(array_merge([
            'code'             => $code,
            'name'             => $code.' discount',
            'type'             => 'percentage',
            'value'            => 10,
            'min_order_amount' => 0,
            'usage_per_user'   => 1,
            'is_active'        => true,
            'auto_apply'       => false,
        ], $attrs));
    }

    /** One coupon in each of the five states, plus the awkward overlaps. */
    private function seedEveryState(): void
    {
        $this->coupon('LIVE');
        $this->coupon('LIVESTARTED', ['starts_at' => now()->subDay(), 'expires_at' => now()->addMonth()]);
        $this->coupon('SCHEDULED', ['starts_at' => now()->addWeek(), 'expires_at' => now()->addMonth()]);
        $this->coupon('EXPIRED', ['starts_at' => now()->subMonth(), 'expires_at' => now()->subDay()]);
        $this->coupon('USEDUP', ['usage_limit' => 5, 'times_used' => 5]);
        $this->coupon('DISABLED', ['is_active' => false]);

        // Overlaps: each must report the state that outranks the other.
        $this->coupon('EXPIREDOFF', ['is_active' => false, 'expires_at' => now()->subDay()]);
        $this->coupon('USEDUPOFF', ['is_active' => false, 'usage_limit' => 2, 'times_used' => 7]);
    }

    public function test_every_status_tab_lists_only_coupons_whose_badge_matches_that_tab(): void
    {
        $this->seedEveryState();

        // The invariant the page was breaking: the SQL behind a tab and the PHP
        // behind a row badge must classify every coupon the same way.
        foreach (array_keys(Coupon::STATUSES) as $status) {
            foreach (Coupon::statusIs($status)->get() as $coupon) {
                $this->assertSame(
                    $status,
                    $coupon->status(),
                    "Coupon {$coupon->code} is listed under the {$status} tab but its badge reads {$coupon->status()}."
                );
            }
        }
    }

    public function test_the_five_statuses_partition_the_table(): void
    {
        $this->seedEveryState();

        $sum = collect(array_keys(Coupon::STATUSES))
            ->sum(fn ($status) => Coupon::statusIs($status)->count());

        // No coupon may fall between two tabs, and none may appear in two.
        $this->assertSame(Coupon::count(), $sum);
    }

    public function test_a_coupon_that_hit_its_usage_cap_is_not_listed_as_active(): void
    {
        $this->seedEveryState();

        $codes = Coupon::statusIs(Coupon::STATUS_ACTIVE)->pluck('code')->all();

        // The reported symptom: USEDUP used to sit in the Active tab.
        $this->assertNotContains('USEDUP', $codes);
        $this->assertNotContains('SCHEDULED', $codes);
        $this->assertEqualsCanonicalizing(['LIVE', 'LIVESTARTED'], $codes);
    }

    public function test_an_expired_coupon_reaches_the_expired_tab_even_when_switched_off(): void
    {
        $this->seedEveryState();

        $codes = Coupon::statusIs(Coupon::STATUS_EXPIRED)->pluck('code')->all();

        // "Coupons not going into expired": a coupon past its date is expired,
        // whatever the is_active switch says.
        $this->assertEqualsCanonicalizing(['EXPIRED', 'EXPIREDOFF'], $codes);
    }

    public function test_a_switched_off_coupon_is_reachable_from_a_tab(): void
    {
        $this->seedEveryState();

        // There was no tab for this state at all, so these coupons showed up
        // only under "All" and could not be filtered to.
        $this->assertEqualsCanonicalizing(
            ['DISABLED'],
            Coupon::statusIs(Coupon::STATUS_DISABLED)->pluck('code')->all()
        );
    }

    public function test_a_coupon_expiring_on_this_exact_second_counts_as_expired(): void
    {
        $this->travelTo(now());
        $this->coupon('BOUNDARY', ['expires_at' => now()]);

        // The old filter used expires_at < now() for expired and > now() for
        // active, so this row belonged to neither tab.
        $this->assertSame(Coupon::STATUS_EXPIRED, Coupon::firstWhere('code', 'BOUNDARY')->status());
        $this->assertSame(1, Coupon::statusIs(Coupon::STATUS_EXPIRED)->count());
        $this->assertSame(0, Coupon::statusIs(Coupon::STATUS_ACTIVE)->count());
    }

    public function test_is_valid_still_means_exactly_the_active_status(): void
    {
        $this->seedEveryState();

        foreach (Coupon::all() as $coupon) {
            $this->assertSame(
                $coupon->status() === Coupon::STATUS_ACTIVE,
                $coupon->isValid(),
                "isValid() disagrees with status() for {$coupon->code}."
            );
        }
    }

    public function test_the_index_renders_a_tab_per_status_with_counts(): void
    {
        $this->seedEveryState();

        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.coupons.index'))
            ->assertOk()
            ->getContent();

        foreach (Coupon::STATUSES as $key => $label) {
            $this->assertStringContainsString($label, $html);
            $this->assertStringContainsString('status='.$key, $html);
        }
    }

    public function test_each_tab_renders_and_shows_only_its_own_coupons(): void
    {
        $this->seedEveryState();

        foreach (array_keys(Coupon::STATUSES) as $status) {
            $html = $this->actingAs($this->adminUser, 'admin')
                ->get(route('admin.coupons.index', ['status' => $status]))
                ->assertOk()
                ->getContent();

            foreach (Coupon::all() as $coupon) {
                if ($coupon->status() === $status) {
                    $this->assertStringContainsString('>'.$coupon->code.'<', $html);
                } else {
                    $this->assertStringNotContainsString('>'.$coupon->code.'<', $html);
                }
            }
        }
    }

    public function test_tab_counts_narrow_with_the_search_and_still_add_up(): void
    {
        $this->seedEveryState();
        $this->coupon('OTHERLIVE');

        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.coupons.index', ['search' => 'OFF']))
            ->assertOk()
            ->getContent();

        // A count that ignored the search would promise rows the tab cannot
        // show. EXPIREDOFF and USEDUPOFF are the only matches, and they sit in
        // two different tabs.
        preg_match_all('/>\s*([A-Z][a-z]+(?: up)?)\s*<span style="font-size: 11px;[^>]*>(\d+)</s', $html, $m);
        $counts = array_combine($m[1], array_map('intval', $m[2]));

        $this->assertSame(2, $counts['All']);
        $this->assertSame(1, $counts['Used up']);
        $this->assertSame(1, $counts['Expired']);
        $this->assertSame(0, $counts['Active']);

        $this->assertSame(
            $counts['All'],
            $counts['Active'] + $counts['Scheduled'] + $counts['Expired'] + $counts['Used up'] + $counts['Disabled']
        );
    }

    public function test_an_unknown_status_is_rejected_rather_than_ignored(): void
    {
        // 'inactive' was a valid filter key before the statuses were named for
        // the reason a coupon is not running.
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.coupons.index', ['status' => 'inactive']))
            ->assertSessionHasErrors('status');
    }

    public function test_the_edit_header_of_a_ticked_but_unstarted_coupon_reads_scheduled(): void
    {
        // The reported bug, exactly: Active is ticked, the badge said Inactive.
        $coupon = $this->coupon('VVK', [
            'is_active'  => true,
            'starts_at'  => now()->addMinutes(2),
            'expires_at' => now()->addMinutes(4),
        ]);

        $html = $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.coupons.edit', $coupon))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Scheduled', $html);
        $this->assertStringNotContainsString('>Inactive<', $html);
    }
}
