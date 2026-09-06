<?php

namespace Tests\Feature\Homepage;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A hero banner may carry its own artwork for phones.
 *
 * The desktop hero is a wide strip - 1426x370, the shape of the clip the store
 * ships with - and a phone gives the slide a 3:2 box. That strip cropped into
 * a 3:2 box keeps only two fifths of its width, so the admin screen takes a
 * mobile image and a mobile clip alongside the desktop pair, and the home page
 * picks whichever belongs to the viewport.
 *
 * The point of the picking is that only one is fetched: a phone pulling down a
 * 15 MB desktop clip on its way to the phone-sized one would cost more than the
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
        $html = $this->get('/')->getContent();
        $start = strpos($html, '<section class="kk-hero"');
        $end = strpos($html, '</section>', $start);

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

    public function test_two_stills_are_chosen_between_by_the_browser_not_by_a_script(): void
    {
        // Two images is the case <picture> was made for, and it beats handing
        // the choice to a script: the browser still fetches exactly one, but it
        // finds that one while parsing instead of after Alpine has run. This is
        // the page's largest paint, so the difference is the difference between
        // the hero being discoverable by the preload scanner and not.
        $this->hero(['mobile_image_url' => 'banners/mobile/portrait.jpg']);

        $html = $this->heroMarkup();

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString(
            '<source media="(max-width: 767px)" srcset="'.asset_v('storage/banners/mobile/portrait.jpg').'">',
            $html
        );
        // The desktop still is the <img>, so a browser that understands none of
        // this gets the wide artwork rather than nothing.
        $this->assertStringContainsString(' src="'.asset_v('storage/banners/desktop.jpg').'"', $html);

        // 767, not 767.98: the same integer the slide's aspect-ratio flip and
        // the video hand-over script use. A hairline range where the picture
        // and the layout disagreed would show phone artwork in a desktop-shaped
        // box.
        $this->assertStringContainsString('(max-width: 767px)', $html);

        // No script is involved in this case at all any more.
        $this->assertStringNotContainsString('data-kk-src', $html);
    }

    public function test_a_decorative_banner_is_hidden_from_screen_readers(): void
    {
        // An admin who clears the alt text is saying the artwork carries no
        // information. Reading the banner's internal name out instead - which
        // is what happened before there was a column for this - is worse than
        // saying nothing.
        $this->hero(['title' => 'Summer', 'alt_text' => '']);

        $html = $this->heroMarkup();

        $this->assertStringContainsString('alt="" aria-hidden="true"', $html);
        $this->assertStringNotContainsString('alt="Summer"', $html);
    }

    public function test_the_alt_text_the_admin_wrote_is_what_is_read_out(): void
    {
        $this->hero(['title' => 'Summer', 'alt_text' => 'Three models in linen on a beach']);

        $this->assertStringContainsString('alt="Three models in linen on a beach"', $this->heroMarkup());
    }

    public function test_a_mobile_video_gives_the_phone_a_clip_and_the_desktop_a_still(): void
    {
        $this->hero([
            'video_url' => null,
            'mobile_video_url' => 'banners/mobile/video/reel.mp4',
        ]);

        $html = $this->heroMarkup();

        // Desktop keeps the still, the phone gets the clip - handed over by the
        // script, so neither frame names a source the browser fetches by itself.
        $this->assertStringContainsString('kk-hero-media--desktop', $html);
        $this->assertStringContainsString('kk-hero-media--mobile', $html);
        $this->assertStringContainsString('data-kk-src="'.asset_v('storage/banners/mobile/video/reel.mp4').'"', $html);
        $this->assertStringContainsString('data-kk-src="'.asset_v('storage/banners/desktop.jpg').'"', $html);
    }

    public function test_a_desktop_video_with_no_mobile_media_plays_at_both_sizes(): void
    {
        $this->hero(['image_url' => null, 'video_url' => 'banners/video/wide.mp4']);

        $html = $this->heroMarkup();

        // One frame, one plain src - the preload scanner can still find it.
        $this->assertStringContainsString('src="'.asset_v('storage/banners/video/wide.mp4').'"', $html);
        $this->assertStringNotContainsString('data-kk-src', $html);
        $this->assertStringNotContainsString('kk-hero-media--mobile', $html);
    }

    /**
     * The height of the hero is the point of this one.
     *
     * A video slide used to opt out of the slide's box and take its height from
     * its own file, so the carousel lurched from a 370px strip to a 1008px clip
     * as it advanced. No slide reads its own FILE for a height any more.
     *
     * The box is still chosen per slide, but from a class this page sets and a
     * ratio app.css holds - two values, 16:9 and 3:4, both known in advance.
     * That is what separates it from the escape hatches below: a slide can be
     * one of two known shapes, never whatever shape the upload happened to be.
     */
    public function test_no_slide_sizes_itself_from_its_own_media(): void
    {
        $this->hero(['image_url' => null, 'video_url' => 'banners/video/wide.mp4']);

        $html = $this->get('/')->getContent();

        // Neither the escape hatch nor anything that used to reach for it.
        $this->assertStringNotContainsString('--kk-hero-ratio', $html);
        $this->assertStringNotContainsString('kk-hero-slide--video', $html);

        // And the slide carries no inline style of its own to size itself with.
        // (.kk-quality--plain elsewhere on this page legitimately sets
        // `aspect-ratio: auto`, so that string is not the thing to look for.)
        $this->assertStringContainsString('<div class="kk-hero-slide"', $this->heroMarkup());
    }

    /**
     * The slide box is chosen per banner, by a class, not by one ratio for the
     * whole hero.
     *
     * The ratios themselves live in app.css now (16:9, and 3:4 on phones for a
     * slide carrying `has-mobile-media`), so this page's job is only to say
     * which banners qualify. A banner with its own phone artwork gets the
     * portrait box on a phone and its own still to fill it; one without keeps
     * the wide box and is sent the desktop file, so neither is ever cropped.
     */
    public function test_a_banner_with_phone_artwork_is_marked_for_the_portrait_box(): void
    {
        $this->hero(['mobile_image_url' => 'banners/mobile/phone.jpg']);

        $this->assertStringContainsString('kk-hero-slide has-mobile-media', $this->heroMarkup());
    }

    public function test_a_banner_without_phone_artwork_keeps_the_wide_box(): void
    {
        $this->hero();

        $markup = $this->heroMarkup();

        $this->assertStringNotContainsString('has-mobile-media', $markup);
        $this->assertStringContainsString('<div class="kk-hero-slide"', $markup);
    }

    /**
     * And the choice really is per slide: a hero holding one of each marks only
     * the one that earns it. This is the half a single-banner test cannot see,
     * and the reason the box is a class rather than a value computed once for
     * the carousel.
     */
    public function test_a_mixed_hero_marks_only_the_slides_that_have_phone_artwork(): void
    {
        $this->hero(['mobile_image_url' => 'banners/mobile/phone.jpg']);
        Banner::create([
            'name' => 'Second',
            'position' => 'hero',
            'image_url' => 'banners/desktop-two.jpg',
            'priority' => 2,
            'is_active' => true,
        ]);

        $markup = $this->heroMarkup();

        $this->assertSame(1, substr_count($markup, 'has-mobile-media'));
        $this->assertStringContainsString('<div class="kk-hero-slide"', $markup);
    }

    /**
     * And the artwork fills that box rather than sitting inside it.
     *
     * The hero is the one .kk-media frame in the store that crops, and app.css's
     * `object-fit: contain` is (0,2,1) - so the rule has to reach (0,3,1) to win
     * on specificity rather than on being further down the page. Matching the
     * declaration *inside* its own block matters: `object-fit: cover` appears
     * elsewhere on this page, so asserting the bare string would stay green with
     * the hero's copy of it deleted.
     */
    public function test_the_hero_fills_its_box_instead_of_letterboxing(): void
    {
        $this->hero();

        $html = $this->get('/')->getContent();

        $this->assertMatchesRegularExpression(
            '/\.kk-media\.kk-hero-media > video:not\(\.kk-media__fill\)\s*\{[^}]*object-fit:\s*cover/s',
            $html
        );

        // The blurred stand-in for the margin went with the margin - and with it
        // a second full-size decode of the largest image on the page.
        $this->assertStringNotContainsString('kk-media__fill', $this->heroMarkup());
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
