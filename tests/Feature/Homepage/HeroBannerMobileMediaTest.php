<?php

namespace Tests\Feature\Homepage;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A hero banner may carry its own artwork for phones.
 *
 * The desktop hero is a wide strip - 1426x370, the shape of the clip the store
 * ships with - and a phone gives the slide a 4:5 portrait box. The same file
 * shrunk into that box is barely taller than the caption drawn over it, so the
 * admin screen now takes a mobile image and a mobile clip alongside the
 * desktop pair, and the home page picks whichever belongs to the viewport.
 *
 * The point of the picking is that only one is fetched: a phone pulling down a
 * 15 MB desktop clip on its way to the portrait one would cost more than the
 * crop it was meant to fix.
 */
class HeroBannerMobileMediaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The hero markup on its own.
     *
     * The page carries the hero stylesheet inline, so every class name these
     * tests look for appears in the document whether or not a slide uses it.
     */
    private function heroMarkup(): string
    {
        $html = $this->get("/")->getContent();
        $start = strpos($html, "<section class=\"kk-hero\"");
        $end = strpos($html, "</section>", $start);

        return substr($html, $start, $end - $start);
    }

    /**
     * The one hero banner on the page.
     *
     * A data migration imports the theme's own hero clip as a banner, so a
     * fresh schema is not an empty carousel - and a second slide alongside the
     * one under test carries its own classes into every assertion here.
     */
    private function hero(array $attributes = []): Banner
    {
        Banner::where('position', 'hero')->delete();

        return Banner::create($attributes + [
            'name' => 'Campaign',
            'position' => 'hero',
            'image_url' => 'banners/desktop.jpg',
            'priority' => 1,
            'is_active' => true,
        ]);
    }

    public function test_a_banner_without_mobile_media_renders_one_frame_with_a_plain_src(): void
    {
        $this->hero();

        $html = $this->heroMarkup();

        $this->assertStringContainsString('storage/banners/desktop.jpg', $html);
        $this->assertStringNotContainsString('data-kk-src', $html);
        $this->assertStringNotContainsString('kk-hero-media--mobile', $html);
    }

    public function test_a_mobile_image_adds_a_second_frame_and_neither_carries_a_src(): void
    {
        $this->hero(['mobile_image_url' => 'banners/mobile/portrait.jpg']);

        $html = $this->heroMarkup();

        $this->assertStringContainsString('kk-hero-media--desktop', $html);
        $this->assertStringContainsString('kk-hero-media--mobile', $html);
        $this->assertStringContainsString('data-kk-for="mobile"', $html);
        $this->assertStringContainsString('storage/banners/mobile/portrait.jpg', $html);

        // Neither frame may name a source the browser would fetch on its own -
        // that is the whole point of handing the choice to the script. The
        // leading space matters: `data-kk-src="..."` ends in the same
        // characters and is exactly what should be there instead.
        $this->assertStringNotContainsString(' src="'.asset_v('storage/banners/desktop.jpg').'"', $html);
        $this->assertStringNotContainsString(' src="'.asset_v('storage/banners/mobile/portrait.jpg').'"', $html);
    }

    public function test_a_mobile_video_makes_only_the_mobile_breakpoint_video_led(): void
    {
        $this->hero([
            'video_url' => null,
            'mobile_video_url' => 'banners/mobile/video/reel.mp4',
        ]);

        $html = $this->heroMarkup();

        $this->assertStringContainsString('kk-hero-slide--video-mobile', $html);
        $this->assertStringNotContainsString('kk-hero-slide--video-desktop', $html);
        $this->assertStringContainsString('--kk-hero-ratio-mobile: auto;', $html);
        $this->assertStringNotContainsString('--kk-hero-ratio: auto;', $html);
    }

    public function test_a_desktop_video_with_no_mobile_media_is_video_led_at_both_sizes(): void
    {
        $this->hero(['image_url' => null, 'video_url' => 'banners/video/wide.mp4']);

        $html = $this->heroMarkup();

        $this->assertStringContainsString('kk-hero-slide--video-desktop', $html);
        $this->assertStringContainsString('kk-hero-slide--video-mobile', $html);
        $this->assertStringContainsString('src="'.asset_v('storage/banners/video/wide.mp4').'"', $html);
    }

    public function test_the_slide_box_matches_the_size_the_admin_screen_recommends(): void
    {
        $this->hero();

        // The stylesheet, not the markup, is what this one is about.
        $html = $this->get('/')->getContent();
        [$deskW, $deskH] = Banner::HERO_DESKTOP_SIZE;
        [$mobW, $mobH] = Banner::HERO_MOBILE_SIZE;

        $this->assertStringContainsString("var(--kk-hero-ratio, {$deskW} / {$deskH})", $html);
        $this->assertStringContainsString("var(--kk-hero-ratio-mobile, {$mobW} / {$mobH})", $html);
    }

    public function test_a_mobile_image_on_a_cdn_is_not_rewritten_as_a_storage_path(): void
    {
        $banner = $this->hero(['mobile_image_url' => 'https://cdn.example.test/portrait.jpg']);

        $this->assertSame('https://cdn.example.test/portrait.jpg', $banner->mobile_image);
    }

    public function test_a_banner_with_no_mobile_image_falls_back_to_the_desktop_still(): void
    {
        $banner = $this->hero();

        $this->assertSame($banner->image, $banner->mobile_image);
        $this->assertFalse($banner->has_mobile_media);
    }
}
