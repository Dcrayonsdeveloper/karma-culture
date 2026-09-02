<?php

namespace Tests\Feature\Homepage;

use App\Models\HomepageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The About Us button's URL is admin-entered (Homepage > About Us > Button
 * Link) and is as likely to be a lookbook or a press piece as it is to be
 * /about, so it follows the same rule as a hero banner: off-site opens in its
 * own tab, our own pages navigate in place.
 */
class AboutCtaLinkTest extends TestCase
{
    use RefreshDatabase;

    private function aboutSection(?string $link): void
    {
        // The section ships with the schema, so this edits the row an admin
        // would edit rather than adding a second one the unique key rejects.
        HomepageSection::updateOrCreate(
            ['key' => 'about_us'],
            [
                'title' => 'Crafted to Last',
                'type' => 'about',
                'button_text' => 'Our Story',
                'button_link' => $link,
                'is_active' => true,
            ]
        );
    }

    private function aboutAnchor(): string
    {
        $html = $this->get('/')->getContent();

        $start = strpos($html, '<a href=');
        while ($start !== false) {
            $end = strpos($html, '>', $start);
            $tag = substr($html, $start, $end - $start + 1);

            if (str_contains($tag, 'kk-btn-brown')) {
                return $tag;
            }

            $start = strpos($html, '<a href=', $start + 1);
        }

        return '';
    }

    public function test_an_off_site_about_button_opens_in_a_new_tab(): void
    {
        $this->aboutSection('https://lookbook.example/karmaa');

        $anchor = $this->aboutAnchor();

        $this->assertStringContainsString('https://lookbook.example/karmaa', $anchor);
        $this->assertStringContainsString('target="_blank"', $anchor);
        $this->assertStringContainsString('noopener', $anchor);
        $this->assertStringContainsString('opens in a new tab', $anchor);
    }

    public function test_the_default_about_button_stays_in_this_tab(): void
    {
        // No Button Link set, so the section falls back to our own /about.
        $this->aboutSection(null);

        $anchor = $this->aboutAnchor();

        $this->assertStringContainsString(route('about'), $anchor);
        $this->assertStringNotContainsString('target=', $anchor);
        $this->assertStringNotContainsString('opens in a new tab', $anchor);
    }
}
