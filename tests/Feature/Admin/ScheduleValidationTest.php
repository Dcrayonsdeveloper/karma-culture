<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Banner;
use App\Models\Coupon;
use App\Models\FlashSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * The schedule fields on the coupon, flash sale and banner forms.
 *
 * Two rules, applied wherever a start and an end are entered together: the end
 * must be later than the start, and neither may be set in the past. The third
 * is the escape hatch that keeps the first two usable - a date the row already
 * holds stays acceptable, so a coupon that started last week can still be
 * edited without its schedule being dragged forward.
 */
class ScheduleValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'first_name' => 'Schedule',
            'last_name' => 'Admin',
            'role' => 'admin',
        ]);

        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function admin(): self
    {
        return $this->actingAs($this->adminUser, 'admin');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function couponPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'SCHED10',
            'name' => 'Scheduled coupon',
            'type' => 'percentage',
            'value' => 10,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function flashSalePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Scheduled sale',
            'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bannerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Scheduled banner',
            'position' => 'hero',
            'image' => UploadedFile::fake()->image('banner.jpg'),
        ], $overrides);
    }

    private function runningCoupon(string $code): Coupon
    {
        return Coupon::create([
            'code' => $code,
            'name' => 'Already running',
            'type' => 'percentage',
            'value' => 15,
            'starts_at' => now()->subWeek(),
            'expires_at' => now()->addWeek(),
            'min_order_amount' => 0,
            'usage_per_user' => 1,
        ]);
    }

    public function test_coupon_expiry_before_start_is_rejected(): void
    {
        $response = $this->admin()->post('/admin/coupons', $this->couponPayload([
            'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'expires_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]));

        $response->assertSessionHasErrors('expires_at');
        $this->assertDatabaseCount('coupons', 0);
    }

    public function test_coupon_expiry_equal_to_start_is_rejected(): void
    {
        $moment = now()->addDays(3)->format('Y-m-d\TH:i');

        $this->admin()
            ->post('/admin/coupons', $this->couponPayload([
                'starts_at' => $moment,
                'expires_at' => $moment,
            ]))
            ->assertSessionHasErrors('expires_at');
    }

    public function test_coupon_start_in_the_past_is_rejected(): void
    {
        $response = $this->admin()->post('/admin/coupons', $this->couponPayload([
            'starts_at' => now()->subDay()->format('Y-m-d\TH:i'),
            'expires_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]));

        $response->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('coupons', 0);
    }

    public function test_coupon_expiry_in_the_past_is_rejected_even_without_a_start(): void
    {
        $this->admin()
            ->post('/admin/coupons', $this->couponPayload([
                'expires_at' => now()->subHour()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('expires_at');
    }

    public function test_coupon_with_a_future_window_is_accepted(): void
    {
        $this->admin()
            ->post('/admin/coupons', $this->couponPayload([
                'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'expires_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('coupons', ['code' => 'SCHED10']);
    }

    public function test_a_start_of_this_very_minute_is_accepted(): void
    {
        // The floor is the current minute, not the current second: a form
        // submitted thirty seconds after the time was picked is not late.
        $this->admin()
            ->post('/admin/coupons', $this->couponPayload([
                'starts_at' => now()->format('Y-m-d\TH:i'),
                'expires_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_editing_a_coupon_that_already_started_keeps_its_start_date(): void
    {
        $coupon = $this->runningCoupon('RUNNING');

        $response = $this->admin()->put("/admin/coupons/{$coupon->id}", [
            'code' => 'RUNNING',
            'name' => 'Renamed while running',
            'type' => 'percentage',
            'value' => 15,
            'starts_at' => $coupon->starts_at->format('Y-m-d\TH:i'),
            'expires_at' => $coupon->expires_at->format('Y-m-d\TH:i'),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Renamed while running', $coupon->fresh()->name);
    }

    public function test_moving_a_started_coupon_further_into_the_past_is_rejected(): void
    {
        $coupon = $this->runningCoupon('RUNNING2');

        $this->admin()
            ->put("/admin/coupons/{$coupon->id}", [
                'code' => 'RUNNING2',
                'name' => 'Already running',
                'type' => 'percentage',
                'value' => 15,
                'starts_at' => now()->subMonth()->format('Y-m-d\TH:i'),
                'expires_at' => $coupon->expires_at->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('starts_at');
    }

    public function test_flash_sale_rejects_an_end_before_its_start(): void
    {
        $this->admin()
            ->post('/admin/flash-sales', $this->flashSalePayload([
                'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('ends_at');
    }

    public function test_flash_sale_rejects_a_start_in_the_past(): void
    {
        $this->admin()
            ->post('/admin/flash-sales', $this->flashSalePayload([
                'starts_at' => now()->subHour()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('starts_at');
    }

    public function test_flash_sale_with_a_future_window_is_accepted(): void
    {
        $this->admin()
            ->post('/admin/flash-sales', $this->flashSalePayload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('flash_sales', ['name' => 'Scheduled sale']);
    }

    public function test_editing_a_running_flash_sale_keeps_its_start_time(): void
    {
        $sale = FlashSale::create([
            'name' => 'Live sale',
            'slug' => 'live-sale',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $this->admin()
            ->put("/admin/flash-sales/{$sale->id}", [
                'name' => 'Live sale renamed',
                'starts_at' => $sale->starts_at->format('Y-m-d\TH:i'),
                'ends_at' => $sale->ends_at->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Live sale renamed', $sale->fresh()->name);
    }

    public function test_banner_rejects_an_end_before_its_start(): void
    {
        $this->admin()
            ->post('/admin/banners', $this->bannerPayload([
                'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('ends_at');
    }

    public function test_banner_rejects_a_start_in_the_past(): void
    {
        $this->admin()
            ->post('/admin/banners', $this->bannerPayload([
                'starts_at' => now()->subDay()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasErrors('starts_at');
    }

    public function test_banner_schedule_is_actually_saved(): void
    {
        // The columns were missing from $fillable, so these two dates used to be
        // validated and then silently dropped on the way to the insert.
        $startsAt = now()->addDay()->startOfMinute();

        $this->admin()
            ->post('/admin/banners', $this->bannerPayload([
                'starts_at' => $startsAt->format('Y-m-d\TH:i'),
                'ends_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            ]))
            ->assertSessionHasNoErrors();

        $banner = Banner::firstWhere('name', 'Scheduled banner');

        $this->assertNotNull($banner->starts_at);
        $this->assertTrue($startsAt->equalTo($banner->starts_at));
    }

    public function test_a_banner_whose_window_has_not_opened_is_not_shown(): void
    {
        Banner::create([
            'name' => 'Future banner',
            'position' => 'hero',
            'image_url' => 'banners/future.jpg',
            'is_active' => true,
            'priority' => 1,
            'starts_at' => now()->addWeek(),
        ]);

        Banner::create([
            'name' => 'Live banner',
            'position' => 'hero',
            'image_url' => 'banners/live.jpg',
            'is_active' => true,
            'priority' => 2,
        ]);

        $names = Banner::where('position', 'hero')->scheduled()->pluck('name');

        $this->assertContains('Live banner', $names);
        $this->assertNotContains('Future banner', $names);
    }

    public function test_dashboard_survives_a_junk_date_range(): void
    {
        // Carbon::parse() used to get this raw, which was a 500 rather than a
        // filter the admin could correct.
        $this->admin()
            ->get('/admin?start_date=lastweek&end_date=whenever')
            ->assertSessionHasErrors(['start_date', 'end_date']);
    }

    public function test_dashboard_rejects_an_end_date_before_the_start_date(): void
    {
        $this->admin()
            ->get('/admin?start_date=2026-03-10&end_date=2026-03-01')
            ->assertSessionHasErrors('end_date');
    }
}
