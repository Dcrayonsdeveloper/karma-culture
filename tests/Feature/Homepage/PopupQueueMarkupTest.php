<?php

namespace Tests\Feature\Homepage;

use App\Models\FlashSale;
use App\Models\Setting;
use App\Support\PopupSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The home page used to open three popups off three private timers - the flash
 * sale at 1.5s, the offer popup at 3.5s, the exit popup on intent - with nothing
 * arbitrating between them, so the offer popup painted straight over the flash
 * sale two seconds after it appeared.
 *
 * They are queued now: one on screen at a time, the next opening two seconds
 * after the previous one CLOSES, and the whole cycle stopping the moment the
 * shopper clicks a product or anything else that is not a popup's own chrome.
 *
 * The queue itself is Alpine, so its behaviour belongs to a browser rather than
 * to PHPUnit. What is testable here - and what silently breaks the queue if it
 * regresses - is the contract the markup owes it:
 *
 *   - data-kk-page on <body>, which is the only way the queue knows it is on
 *     home and may therefore cycle and honour the engagement kill switch;
 *   - data-kk-popup on each popup root, which is what stops a click on a close
 *     button being read as the shopper engaging with the page;
 *   - data-kk-popup-ignore on the floats that are not browsing signals;
 *   - and the absence of the old self-opening timer in the flash-sale component.
 *
 * Every one of those is an attribute nobody would miss by eye in a 1700-line
 * template, and losing any of them fails open: the popups go back to stacking.
 */
class PopupQueueMarkupTest extends TestCase
{
    use RefreshDatabase;

    /** Both marketing popups on, so their partials actually render. */
    private function enablePopups(): void
    {
        foreach (PopupSettings::defaults() as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => PopupSettings::groupFor($key)]
            );
        }
    }

    /** An active sale, which is the only condition that renders the flash popup. */
    private function flashSale(): void
    {
        FlashSale::create([
            'name' => 'Monsoon Drop',
            'slug' => 'monsoon-drop',
            'description' => 'Everything reduced for the weekend.',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(6),
            'is_active' => true,
        ]);
    }

    public function test_the_body_tells_the_queue_which_page_it_is_on(): void
    {
        // Home-only behaviour hangs off this: cycling, and the click that stops
        // it. Without the attribute the queue treats home like any other page -
        // no restart, and no engagement kill switch.
        $this->get('/')->assertSee('data-kk-page="home"', false);
    }

    public function test_a_non_home_page_reports_its_own_route_name(): void
    {
        // The site-wide exit popup must keep behaving exactly as it did off home,
        // which means the queue has to be able to tell it is not on home.
        $html = $this->get('/shop')->getContent();

        $this->assertStringContainsString('data-kk-page=', $html);
        $this->assertStringNotContainsString('data-kk-page="home"', $html);
    }

    public function test_each_popup_root_is_tagged_as_its_own_chrome(): void
    {
        $this->enablePopups();
        $this->flashSale();

        $html = $this->get('/')->getContent();

        // Untagged, a click on any of these popups' close buttons or backdrops
        // would match the engagement selector and stop the cycle on the popup's
        // own dismissal - the queue would run exactly once and never again.
        $this->assertStringContainsString('data-kk-popup="flash"', $html);
        $this->assertStringContainsString('data-kk-popup="offer"', $html);
        $this->assertStringContainsString('data-kk-popup="exit"', $html);
        $this->assertStringContainsString('data-kk-popup="cookie"', $html);
    }

    public function test_the_floats_that_are_not_browsing_signals_are_ignored(): void
    {
        // Back-to-top scrolls the page for the reader; dismissing a toast is
        // housekeeping. Neither means "I am shopping now".
        $this->get('/')->assertSee('data-kk-popup-ignore', false);
    }

    public function test_the_flash_sale_popup_no_longer_opens_itself(): void
    {
        $this->flashSale();

        $html = $this->get('/')->getContent();

        // The 1500ms self-open and the hand-rolled scroll lock are what made this
        // popup collide with the offer popup and fight it over document.body.
        // The queue owns the timing and x-trap.noscroll owns the lock.
        $this->assertStringNotContainsString("document.body.style.overflow = 'hidden'", $html);
        $this->assertStringContainsString('kkPopupQueue', $html);
        $this->assertStringContainsString('x-trap.noscroll="open"', $html);
    }

    public function test_the_flash_sale_popup_announces_itself_as_a_dialog(): void
    {
        $this->flashSale();

        // It was the one modal on the page with no a11y contract at all, which
        // mattered more once it started sharing a scroll trap with the others.
        $this->get('/')
            ->assertSee('aria-labelledby="flash-popup-title"', false)
            ->assertSee('id="flash-popup-title"', false);
    }
}
