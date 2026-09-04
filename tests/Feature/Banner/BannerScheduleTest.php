<?php

namespace Tests\Feature\Banner;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A banner is on the site only while its window says so - and the admin screen
 * always says which.
 *
 * Scheduling was on this table once and was taken off again, because it gave a
 * banner a second, invisible way to be off: the Active switch read Active while
 * the storefront showed nothing, and no screen reconciled the two. The window is
 * back, so the thing that must not come back with it is that silence. Every
 * surface reads one scope, and every admin screen prints the resulting state.
 */
class BannerScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function banner(array $attributes = []): Banner
    {
        // A data migration imports the theme's own hero clip, so a fresh schema
        // is not an empty carousel; clearing it keeps each case to one slide.
        Banner::where('position', 'hero')->delete();

        return Banner::create($attributes + [
            'name' => 'Campaign',
            'position' => 'hero',
            'image_url' => 'banners/desktop.jpg',
            'priority' => 1,
            'is_active' => true,
        ]);
    }

    public function test_a_banner_with_no_window_is_live(): void
    {
        $banner = $this->banner();

        $this->assertSame('live', $banner->state);
        $this->assertTrue($banner->is_visible);
        $this->assertSame(1, Banner::visible()->count());
    }

    public function test_a_banner_that_has_not_started_is_scheduled_not_live(): void
    {
        $banner = $this->banner(['starts_at' => now()->addWeek()]);

        $this->assertSame('scheduled', $banner->state);
        $this->assertFalse($banner->is_visible);
        $this->assertSame(0, Banner::visible()->count());
        $this->get('/')->assertOk()->assertDontSee('storage/banners/desktop.jpg');
    }

    public function test_a_banner_that_has_ended_is_expired_not_live(): void
    {
        $banner = $this->banner(['starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()]);

        $this->assertSame('expired', $banner->state);
        $this->assertSame(0, Banner::visible()->count());
        $this->get('/')->assertOk()->assertDontSee('storage/banners/desktop.jpg');
    }

    public function test_a_banner_inside_its_window_is_live(): void
    {
        $this->banner(['starts_at' => now()->subDay(), 'ends_at' => now()->addWeek()]);

        $this->assertSame(1, Banner::visible()->count());
        $this->get('/')->assertOk()->assertSee('storage/banners/desktop.jpg', false);
    }

    public function test_the_switch_still_wins_over_the_window(): void
    {
        // Inside its window but switched off. "Hidden" has to beat "Live", or
        // an admin who turns a banner off during a campaign is told it is on.
        $banner = $this->banner([
            'is_active' => false,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
        ]);

        $this->assertSame('hidden', $banner->state);
        $this->assertSame(0, Banner::visible()->count());
    }

    public function test_the_state_label_says_why_a_banner_is_not_on_the_site(): void
    {
        // The sentence is the whole point: "Active" beside "not on the site" is
        // what got scheduling removed from this table the first time.
        $scheduled = $this->banner(['starts_at' => now()->addWeek()]);
        $this->assertStringStartsWith('Scheduled for', $scheduled->state_label);

        $expired = $this->banner(['starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()]);
        $this->assertStringStartsWith('Ended', $expired->state_label);

        $this->assertSame('Live', $this->banner()->state_label);
    }

    public function test_a_banner_carrying_no_artwork_is_not_drawn_as_an_empty_slide(): void
    {
        // Nothing stops an admin saving a banner and never adding the file, and
        // an empty frame across the top of the home page is worse than no hero.
        $this->banner(['image_url' => null]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-kk-src', $html);
        // The theme's own clip is what a store with no usable banner falls back
        // to, so the box never collapses.
        $this->assertStringContainsString('karmaa-kulture-web-banner-v3.mp4', $html);
    }

    public function test_deleting_a_banner_keeps_the_row_so_it_can_come_back(): void
    {
        $banner = $this->banner();
        $banner->delete();

        $this->assertSoftDeleted('banners', ['id' => $banner->id]);
        $this->assertSame(0, Banner::visible()->count());
        $this->assertNotNull(Banner::withTrashed()->find($banner->id));
    }
}
