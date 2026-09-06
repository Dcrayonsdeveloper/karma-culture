<?php

namespace Tests\Unit\Support;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The contract for is_offsite_url(), which decides whether an admin-entered
 * link gets target="_blank".
 *
 * Both directions matter. Miss an off-site URL and following a banner closes
 * the storefront on the shopper; call a URL of our own off-site and clicking
 * "New In" leaves them with two copies of the store open.
 *
 * No RefreshDatabase: the helper reads config and the request host, nothing
 * else, and the suite must stay runnable without a database.
 */
class OffsiteUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://karmaakulture.test']);
    }

    public static function offsite(): array
    {
        return [
            'another site' => ['https://www.youtube.com/watch?v=abc123'],
            'no www' => ['https://youtube.com/'],
            'plain http' => ['http://lookbook.example/spring'],
            'protocol-relative' => ['//instagram.com/karmaakulture'],
            'scheme in caps' => ['HTTPS://Example.COM/x'],
            'subdomain of ours' => ['https://shop.karmaakulture.test/x'],
            'padded by the admin' => ['  https://example.com/promo  '],
        ];
    }

    public static function onsite(): array
    {
        return [
            'absolute path' => ['/collections/new-in'],
            'bare path' => ['new-in'],
            'query only' => ['?sort=newest'],
            'fragment only' => ['#about'],
            'empty' => [''],
            'blank' => ['   '],
            'null' => [null],
            'our own domain' => ['https://karmaakulture.test/new-in'],
            'our own domain with www' => ['https://www.karmaakulture.test/new-in'],
            'our own domain, other scheme' => ['http://karmaakulture.test/new-in'],
            // A new tab for one of these would be an empty tab: the browser
            // hands the URL to another app and never paints a page.
            'mailto' => ['mailto:hello@karmaakulture.test'],
            'tel' => ['tel:+919000000000'],
            'whatsapp app link' => ['whatsapp://send?phone=919000000000'],
        ];
    }

    #[Test]
    #[DataProvider('offsite')]
    public function test_a_url_that_leaves_the_store_is_off_site(?string $url): void
    {
        $this->assertTrue(is_offsite_url($url), $url . ' should be off-site');
    }

    #[Test]
    #[DataProvider('onsite')]
    public function test_a_url_that_stays_on_the_store_is_not_off_site(?string $url): void
    {
        $this->assertFalse(is_offsite_url($url), $url . ' should not be off-site');
    }

    #[Test]
    public function test_the_domain_serving_the_request_counts_as_ours(): void
    {
        // The live site answers on a host APP_URL does not name, and its own
        // links must not open a second tab because of that mismatch.
        config(['app.url' => 'https://karmaakulture.test']);

        $this->app->instance('request', Request::create('https://shop.example.org/new-in'));

        $this->assertFalse(is_offsite_url('https://shop.example.org/new-in'));
        $this->assertFalse(is_offsite_url('https://karmaakulture.test/new-in'));
        $this->assertTrue(is_offsite_url('https://www.youtube.com/'));
    }
}
