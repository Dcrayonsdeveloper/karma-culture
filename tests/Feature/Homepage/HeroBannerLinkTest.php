<?php

namespace Tests\Feature\Homepage;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A hero banner's Link URL usually points off-site - a campaign page, a
 * lookbook, a clip - and the slide-wide anchor navigated in place, so
 * following one closed the storefront on whoever clicked it.
 *
 * An off-site slide opens in a tab of its own now. rel="noopener" comes with
 * it: without it the opened page gets a window.opener handle back onto the
 * store. A banner pointing back into the store keeps navigating in place -
 * a second copy of the storefront is not what "New In" means.
 */
class HeroBannerLinkTest extends TestCase
{
    use RefreshDatabase;

    private function banner(string $link): void
    {
        Banner::create([
            'name' => 'Campaign',
            'title' => 'Test Heading',
            'button_text' => 'Test Btn',
            'position' => 'hero',
            'image_url' => 'https://example.test/banner.jpg',
            'link' => $link,
            'priority' => 1,
            'is_active' => true,
        ]);
    }

    private function heroAnchor(): string
    {
        $html = $this->get('/')->getContent();

        $start = strpos($html, '<a href=');
        while ($start !== false) {
            $end = strpos($html, '>', $start);
            $tag = substr($html, $start, $end - $start + 1);

            if (str_contains($tag, 'kk-hero-link')) {
                return $tag;
            }

            $start = strpos($html, '<a href=', $start + 1);
        }

        return '';
    }

    public function test_a_hero_banner_opens_an_off_site_link_in_a_new_tab(): void
    {
        $this->banner('https://www.youtube.com/watch?v=abc123');

        $anchor = $this->heroAnchor();

        $this->assertStringContainsString('https://www.youtube.com/watch?v=abc123', $anchor);
        $this->assertStringContainsString('target="_blank"', $anchor);
        $this->assertStringContainsString('noopener', $anchor);
        $this->assertStringContainsString('opens in a new tab', $anchor);
    }

    public function test_a_hero_banner_pointing_into_the_store_stays_in_this_tab(): void
    {
        $this->banner('/collections/new-in');

        $anchor = $this->heroAnchor();

        $this->assertStringContainsString('/collections/new-in', $anchor);
        $this->assertStringNotContainsString('target=', $anchor);
        $this->assertStringNotContainsString('noopener', $anchor);
        // The announcement belongs to the tab that actually opens, so it must
        // not be read out over a link that navigates in place.
        $this->assertStringNotContainsString('opens in a new tab', $anchor);
    }
}
