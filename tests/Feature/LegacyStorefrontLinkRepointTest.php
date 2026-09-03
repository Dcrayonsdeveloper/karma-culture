<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Removing the /products -> /shop redirect only makes the storefront honest if
 * nothing on the storefront was leaning on it. Most links are not a problem:
 * every one the code emits goes through route(). The ones that are a problem are
 * links held as data - a hero banner's target, a homepage section's button, a
 * footer menu entry - because BeautySeeder wrote '/products' into all three and
 * an admin can type it into any of them.
 *
 * Those rows would have become dead buttons the moment the redirect went. The
 * repoint_legacy_storefront_links migration rewrites them to the page they were
 * always meant to open, and this is what says it did.
 *
 * Read the /products -> /shop cases below as history, not as current addresses:
 * the all-products page has since moved to /products, and a second migration
 * carries these same rows the rest of the way. This file pins what that first
 * migration did, because it has already run on production and editing it now
 * would only make the history disagree with the database. ShopLinkRepointTest
 * pins the correction, and the end state after both.
 */
class LegacyStorefrontLinkRepointTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): object
    {
        return require database_path('migrations/2026_09_03_120000_repoint_legacy_storefront_links.php');
    }

    private function banner(string $link): int
    {
        return DB::table('banners')->insertGetId([
            'name' => 'Test banner '.$link,
            'position' => 'hero',
            'image_url' => 'https://placehold.co/1920x700',
            'link' => $link,
            'priority' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function linkOf(int $id): ?string
    {
        return DB::table('banners')->where('id', $id)->value('link');
    }

    public function test_it_repoints_every_legacy_path_a_banner_can_hold(): void
    {
        $cases = [
            '/products' => '/shop',
            '/products/' => '/shop',
            '/returns' => '/returns-policy',
            '/products/poplin-shirt' => '/product/poplin-shirt',
            '/categories/shirts' => '/category/shirts',
            '/orders/17' => '/account/orders/17',
            '/orders/17/track' => '/account/orders/17/track',
        ];

        $ids = [];
        foreach ($cases as $before => $after) {
            $ids[$before] = $this->banner($before);
        }

        $this->migration()->up();

        foreach ($cases as $before => $after) {
            $this->assertSame($after, $this->linkOf($ids[$before]), "{$before} should now point at {$after}");
        }
    }

    public function test_it_keeps_the_query_string_a_redirect_would_have_dropped(): void
    {
        // This is the case that made the redirect worse than useless: a Laravel
        // redirect route forwards path parameters only, so '/products?category=
        // shirts' arrived at a bare, unfiltered /shop. Rewriting the stored link
        // is the only way the filter survives.
        $id = $this->banner('/products?category=shirts&sort=newest');

        $this->migration()->up();

        $this->assertSame('/shop?category=shirts&sort=newest', $this->linkOf($id));
    }

    public function test_it_leaves_alone_what_is_not_ours_to_rewrite(): void
    {
        $untouched = [
            '/shop',                                  // already right
            '/product/poplin-shirt',                  // already canonical
            '/category/shirts',
            '/account/orders/17',
            '/productsomething',                      // a prefix match that is not a path boundary
            '/returns-policy',                        // the destination, not the alias
            'https://instagram.com/products',         // another site's /products
            'mailto:hello@example.com',
            '',
        ];

        $ids = [];
        foreach ($untouched as $i => $link) {
            $ids[$i] = $this->banner($link);
        }

        $this->migration()->up();

        foreach ($untouched as $i => $link) {
            $this->assertSame($link, $this->linkOf($ids[$i]), "{$link} should have been left as it was");
        }
    }

    public function test_a_null_link_survives(): void
    {
        $id = DB::table('banners')->insertGetId([
            'name' => 'No link',
            'position' => 'hero',
            'image_url' => 'https://placehold.co/1920x700',
            'link' => null,
            'priority' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->migration()->up();

        $this->assertNull($this->linkOf($id));
    }

    public function test_it_repoints_a_homepage_section_button_and_a_menu_entry(): void
    {
        $sectionId = DB::table('homepage_sections')->insertGetId([
            'key' => 'promo_banner_test',
            'title' => 'Shop the lot',
            'type' => 'cta',
            'button_text' => 'Shop Collection',
            'button_link' => '/products',
            'position' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $menuId = DB::table('navigation_menus')->insertGetId([
            'location' => 'footer_col1',
            'label' => 'All Products',
            'url' => '/products',
            'position' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->migration()->up();

        $this->assertSame('/shop', DB::table('homepage_sections')->where('id', $sectionId)->value('button_link'));
        $this->assertSame('/shop', DB::table('navigation_menus')->where('id', $menuId)->value('url'));
    }

    public function test_it_reaches_a_link_nested_in_a_sections_json_content(): void
    {
        // Section content is a JSON blob whose shape varies by section type, and
        // some of those shapes carry their own per-item link. Walked key by key
        // rather than string-replaced, so prose that happens to mention the word
        // is not touched and the JSON cannot be left unparseable.
        $id = DB::table('homepage_sections')->insertGetId([
            'key' => 'benefits_test',
            'title' => 'Why us',
            'type' => 'benefits',
            'content' => json_encode([
                'items' => [
                    ['title' => 'Everything', 'link' => '/products'],
                    ['title' => 'Shirts', 'link' => '/categories/shirts'],
                    ['title' => 'Read about our products', 'link' => '/about'],
                ],
            ]),
            'position' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->migration()->up();

        $content = json_decode(DB::table('homepage_sections')->where('id', $id)->value('content'), true);

        $this->assertSame('/shop', $content['items'][0]['link']);
        $this->assertSame('/category/shirts', $content['items'][1]['link']);
        $this->assertSame('/about', $content['items'][2]['link']);
        $this->assertSame('Read about our products', $content['items'][2]['title']);
    }

    public function test_it_repairs_a_dead_link_inside_stored_page_copy(): void
    {
        // The Terms of Service page ships with a link to /returns in its body
        // copy, so this is not hypothetical - it is the live legal page.
        $id = DB::table('pages')->insertGetId([
            'title' => 'Terms of Service',
            'slug' => 'terms-of-service-test',
            'content' => '<h2>Returns</h2><p>See our <a href="/returns">Returns &amp; Refunds policy</a>'
                .' and browse <a href="/products">everything we make</a>.</p>',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->migration()->up();

        $content = DB::table('pages')->where('id', $id)->value('content');

        $this->assertStringContainsString('href="/returns-policy"', $content);
        $this->assertStringContainsString('href="/shop"', $content);
        $this->assertStringNotContainsString('href="/returns"', $content);
        $this->assertStringNotContainsString('href="/products"', $content);

        // The copy around the links is not the migration's business.
        $this->assertStringContainsString('<h2>Returns</h2>', $content);
        $this->assertStringContainsString('Returns &amp; Refunds policy', $content);
        $this->assertStringContainsString('everything we make', $content);
    }

    public function test_it_does_not_touch_prose_or_links_that_merely_look_similar(): void
    {
        $html = '<p>Our products are great. Read the '
            .'<a href="/returns-policy">returns policy</a>, see '
            .'<a href="/shop">the shop</a>, or visit '
            .'<a href="https://instagram.com/products">us on Instagram</a>. '
            .'The word /products appears here as text, not as a link. '
            .'<img src="/storage/products/hero.jpg" alt="products"></p>';

        $id = DB::table('pages')->insertGetId([
            'title' => 'Untouched',
            'slug' => 'untouched-test',
            'content' => $html,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->migration()->up();

        $this->assertSame($html, DB::table('pages')->where('id', $id)->value('content'));
    }

    public function test_running_it_twice_changes_nothing_the_second_time(): void
    {
        $id = $this->banner('/products');

        $this->migration()->up();
        $this->assertSame('/shop', $this->linkOf($id));

        $this->migration()->up();
        $this->assertSame('/shop', $this->linkOf($id));
    }

    public function test_the_admin_does_not_teach_staff_a_path_that_404s(): void
    {
        // The banner and section forms carry an example path in their
        // placeholder, their tooltip and their validation message. Those three
        // strings are the only guidance the store owner gets about what to type
        // in a link box, and all of them said "/products" - so the admin was
        // dictating the dead link this whole change is about, and would have
        // kept refilling the rows the migration cleaned up.
        $surfaces = [
            app_path('Http/Controllers/Admin/HomepageController.php'),
            resource_path('views/admin/homepage/hero-banners.blade.php'),
            resource_path('views/admin/homepage/edit-section.blade.php'),
        ];

        $offenders = [];
        foreach ($surfaces as $file) {
            foreach (preg_split('/\R/', (string) file_get_contents($file)) as $i => $line) {
                if (! str_contains($line, 'placeholder=') && ! str_contains($line, 'title="Enter a path')
                    && ! str_contains($line, 'Enter a path such as')) {
                    continue;
                }

                if (preg_match('#/(shop|returns)(?![\w-])#', $line)) {
                    $offenders[] = basename($file).':'.($i + 1).' '.trim($line);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "The admin offers staff an example link that 404s:\n  ".implode("\n  ", $offenders)
                ."\n\nUse /products, /category/{slug} and /returns-policy - the pages that exist."
        );
    }

    public function test_no_seeded_link_points_at_a_path_that_404s(): void
    {
        // The other end of the same problem: the seeders are what write these
        // rows on a fresh install, so a legacy path left in one of them would
        // reintroduce the dead button the migration just cleaned up.
        $seeders = glob(database_path('seeders/*.php'));

        $offenders = [];
        foreach ($seeders as $file) {
            $source = file_get_contents($file);

            // Both the shapes a seeder writes a link in: a bare value for a
            // banner/button/menu column, and an href inside page copy. The
            // closing quote is part of the bare needles so that /returns-policy
            // and /product/{slug} are not read as the dead paths they extend.
            //
            // Only paths that actually 404 are listed. /categories/{slug} is
            // deliberately absent - it is still a page, just not the canonical
            // one, which is a separate question from this one.
            $needles = [
                "'/shop'", '"/shop"', "'/returns'", '"/returns"',
                'href="/shop"', "href='/shop'",
                'href="/returns"', "href='/returns'",
                'href="/orders/', "href='/orders/",
            ];

            foreach ($needles as $needle) {
                if (str_contains($source, $needle)) {
                    $offenders[] = basename($file).' contains '.$needle;
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "These seeders write a link to a path that 404s:\n  ".implode("\n  ", $offenders)
                ."\n\nUse /products and /returns-policy - the pages that exist."
        );
    }
}
