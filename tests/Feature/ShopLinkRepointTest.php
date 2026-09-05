<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The all-products page moved to /products, and the stored links have to follow.
 *
 * A banner target, a homepage section button and a footer menu entry are data,
 * not code, so nothing about moving a route updates them. One migration ago they
 * were pointed at /shop; /shop is the dead path now, so pointing them there is
 * the same broken button, one address along.
 *
 * The first migration is deliberately left as it was rather than edited: it has
 * already run on production, where editing it would change nothing and only make
 * the history disagree with what actually happened. This is the correction, in
 * the direction the page really went, and the end-state test at the bottom is
 * the one that matters - whichever address a row started at, it finishes at the
 * page that exists.
 */
class ShopLinkRepointTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): object
    {
        return require database_path('migrations/2026_09_03_140000_repoint_shop_links_at_products.php');
    }

    private function previousMigration(): object
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

    public function test_it_moves_a_shop_link_to_products(): void
    {
        $id = $this->banner('/shop');

        $this->migration()->up();

        $this->assertSame('/products', $this->linkOf($id));
    }

    public function test_it_keeps_the_filter_on_a_filtered_shop_link(): void
    {
        // A banner pointed at a pre-filtered listing is the whole reason these
        // rows are worth repairing rather than blanking.
        $id = $this->banner('/shop?category=shirts&sort=newest');

        $this->migration()->up();

        $this->assertSame('/products?category=shirts&sort=newest', $this->linkOf($id));
    }

    public function test_it_leaves_alone_what_is_not_ours_to_rewrite(): void
    {
        $untouched = [
            '/products',                        // already right
            '/product/poplin-shirt',
            '/category/shirts',
            '/shopping-guide',                  // starts the same, is not the same path
            '/account/orders/17',
            'https://instagram.com/shop',       // another site's /shop
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

    public function test_it_repoints_a_section_button_a_menu_entry_and_a_nested_json_link(): void
    {
        $sectionId = DB::table('homepage_sections')->insertGetId([
            'key' => 'promo_banner_test',
            'title' => 'Shop the lot',
            'type' => 'cta',
            'button_text' => 'Shop Collection',
            'button_link' => '/shop',
            'content' => json_encode([
                'items' => [
                    ['title' => 'Everything', 'link' => '/shop'],
                    ['title' => 'Come and shop with us', 'link' => '/about'],
                ],
            ]),
            'position' => 3,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $menuId = DB::table('navigation_menus')->insertGetId([
            'location' => 'footer_col1',
            'label' => 'All Products',
            'url' => '/shop',
            'position' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->migration()->up();

        $this->assertSame('/products', DB::table('homepage_sections')->where('id', $sectionId)->value('button_link'));
        $this->assertSame('/products', DB::table('navigation_menus')->where('id', $menuId)->value('url'));

        $content = json_decode(DB::table('homepage_sections')->where('id', $sectionId)->value('content'), true);

        $this->assertSame('/products', $content['items'][0]['link']);
        $this->assertSame('/about', $content['items'][1]['link']);
        $this->assertSame('Come and shop with us', $content['items'][1]['title']);
    }

    public function test_it_repairs_an_href_in_stored_page_copy_without_touching_the_prose(): void
    {
        $id = DB::table('pages')->insertGetId([
            'title' => 'Help',
            'slug' => 'help-test',
            'content' => '<p>Browse <a href="/shop">the shop</a>. You can shop any time, '
                .'and our <a href="/shopping-guide">shopping guide</a> explains how.</p>',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->migration()->up();

        $content = DB::table('pages')->where('id', $id)->value('content');

        $this->assertStringContainsString('href="/products"', $content);
        $this->assertStringContainsString('href="/shopping-guide"', $content);
        $this->assertStringNotContainsString('href="/shop"', $content);
        $this->assertStringContainsString('You can shop any time', $content);
    }

    public function test_running_it_twice_changes_nothing_the_second_time(): void
    {
        $id = $this->banner('/shop');

        $this->migration()->up();
        $this->assertSame('/products', $this->linkOf($id));

        $this->migration()->up();
        $this->assertSame('/products', $this->linkOf($id));
    }

    public function test_whichever_address_a_row_started_at_it_ends_at_the_page_that_exists(): void
    {
        // The end state, which is the only thing a shopper experiences. A row
        // written before any of this said /products; production's rows were
        // moved to /shop by the first migration; either way both migrations run
        // in order and both have to finish somewhere that answers 200.
        $started = [
            '/products' => '/products',
            '/shop' => '/products',
            '/shop/' => '/products',
            '/products?category=shirts' => '/products?category=shirts',
            '/shop?category=shirts' => '/products?category=shirts',
        ];

        $ids = [];
        foreach ($started as $before => $after) {
            $ids[$before] = $this->banner($before);
        }

        $this->previousMigration()->up();
        $this->migration()->up();

        foreach ($started as $before => $after) {
            $this->assertSame($after, $this->linkOf($ids[$before]), "{$before} should finish at {$after}");
        }
    }
}
