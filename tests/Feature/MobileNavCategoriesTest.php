<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The mobile drawer's "Shop by Category" heading rendered above nothing.
 *
 * The partial ran its own query for the two slugs "mens" and "womens" and
 * ignored the $navCategories the view composer already hands it, so on a store
 * whose roots are named anything else the list came back empty while the
 * heading still drew. The desktop mega menu had the identical bug and was fixed
 * in bd67a8f; this covers the mobile half and the empty-store case.
 */
class MobileNavCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $name, string $slug, array $overrides = []): Category
    {
        return Category::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'position' => 0,
        ], $overrides));
    }

    /**
     * Just the drawer's category section.
     *
     * Asserting against the whole page would pass on the homepage's own
     * category grid even with the drawer empty, which is exactly the bug.
     */
    private function drawerCategories(): string
    {
        $html = $this->get('/')->getContent();

        $start = strpos($html, 'Shop by Category');
        if ($start === false) {
            return '';
        }

        $end = strpos($html, '<!-- Account Links -->', $start);

        return $end === false ? substr($html, $start) : substr($html, $start, $end - $start);
    }

    public function test_the_drawer_lists_roots_whatever_they_are_called(): void
    {
        $apparel = $this->category('Apparel', 'apparel', ['position' => 1]);
        $this->category('Shirts', 'shirts', ['parent_id' => $apparel->id, 'position' => 1]);
        $this->category('Trousers', 'trousers', ['parent_id' => $apparel->id, 'position' => 2]);
        $this->category('Accessories', 'accessories', ['position' => 2]);

        $drawer = $this->drawerCategories();

        $this->assertNotSame('', $drawer, 'The drawer should render a "Shop by Category" section.');
        $this->assertStringContainsString('Apparel', $drawer);
        $this->assertStringContainsString('Accessories', $drawer);
        $this->assertStringContainsString('Shirts', $drawer);
        $this->assertStringContainsString('Trousers', $drawer);
        $this->assertStringContainsString('View All Apparel', $drawer);
    }

    public function test_roots_follow_the_order_admin_set(): void
    {
        $this->category('Zebra', 'zebra', ['position' => 1]);
        $this->category('Alpha', 'alpha', ['position' => 2]);

        $drawer = $this->drawerCategories();

        $this->assertLessThan(
            strpos($drawer, 'Alpha'),
            strpos($drawer, 'Zebra'),
            'Roots should follow the position column, not alphabetical order.'
        );
    }

    public function test_deactivated_categories_stay_out_of_the_drawer(): void
    {
        $apparel = $this->category('Apparel', 'apparel');
        $this->category('Retired Line', 'retired-line', [
            'parent_id' => $apparel->id,
            'is_active' => false,
        ]);
        $this->category('Hidden Root', 'hidden-root', ['is_active' => false]);

        $drawer = $this->drawerCategories();

        $this->assertStringContainsString('Apparel', $drawer);
        $this->assertStringNotContainsString('Retired Line', $drawer);
        $this->assertStringNotContainsString('Hidden Root', $drawer);
    }

    /** No categories must mean no heading, rather than a heading over nothing. */
    public function test_the_heading_is_absent_when_there_is_nothing_to_list(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Shop by Category', false);
    }
}
