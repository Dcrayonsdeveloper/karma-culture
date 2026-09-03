<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Undoing a filter has to take the shopper back to the top of the wider list.
 *
 * Removing a chip always WIDENS the result set, so keeping ?page across it
 * drops the shopper into the middle of a list they have not seen the start of.
 * The multi-value chips already dropped it; the one-value chips - category,
 * price, rating, In Stock, On Sale - went through fullUrlWithoutQuery(), which
 * keeps every other parameter including page. Since the chips row is shared by
 * every listing page, that was one bug on six screens.
 */
class ListingChipsTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Shirts',
            'slug' => 'shirts',
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Poplin Shirt',
            'slug' => 'poplin-shirt',
            'sku' => 'POPLIN',
            'price' => 500,
            'mrp' => 900,
            'cost_price' => 200,
            'stock_quantity' => 10,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    /** Every chip's href, taken off the Active Filters row. */
    private function chipTargets(string $html): array
    {
        $row = strstr($html, 'Active Filters:');
        $this->assertNotFalse($row, 'No Active Filters row on the page.');
        $row = substr($row, 0, strpos($row, '</div>'));

        preg_match_all('/<a\s+href="([^"]+)"/', $row, $m);

        return array_map('html_entity_decode', $m[1]);
    }

    public static function oneValueFilters(): array
    {
        return [
            'category' => ['category=shirts'],
            'price' => ['min_price=1&max_price=99999'],
            'rating' => ['rating=3'],
            'in stock' => ['in_stock=1'],
            'on sale' => ['on_sale=1'],
        ];
    }

    #[DataProvider('oneValueFilters')]
    public function test_removing_a_chip_restarts_at_page_one(string $query): void
    {
        $targets = $this->chipTargets(
            $this->get('/products?'.$query.'&page=3')->assertOk()->getContent()
        );

        $this->assertNotEmpty($targets, 'No chips rendered for '.$query);

        foreach ($targets as $target) {
            $this->assertStringNotContainsString(
                'page=3',
                $target,
                'A chip removal kept the shopper on page 3 of a list that just got bigger: '.$target
            );
        }
    }

    public function test_removing_a_chip_leaves_the_other_filters_alone(): void
    {
        // Dropping page must not turn into dropping everything.
        $targets = $this->chipTargets(
            $this->get('/products?rating=3&in_stock=1&page=2')->assertOk()->getContent()
        );

        $kept = array_filter($targets, fn ($t) => str_contains($t, 'in_stock') && ! str_contains($t, 'rating'));

        $this->assertNotEmpty($kept, 'Removing the rating chip should keep In Stock: '.implode(' | ', $targets));
    }

    public function test_a_sold_out_product_offers_its_own_shelf_not_the_front_page(): void
    {
        // "Browse similar pieces" on a sold-out product page went to route('home'),
        // where the nearest similar pieces demonstrably are not.
        $product = Product::create([
            'name' => 'Sold Out Shirt',
            'slug' => 'sold-out-shirt',
            'sku' => 'SOLDOUT',
            'price' => 500,
            'mrp' => 900,
            'cost_price' => 200,
            'stock_quantity' => 0,
            'stock_status' => 'out_of_stock',
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $html = $this->get('/product/'.$product->slug)->assertOk()->getContent();

        $ok = preg_match('/<a\s+href="([^"]+)"[^>]*>\s*Browse similar pieces/', $html, $m);
        $this->assertSame(1, $ok, 'No "Browse similar pieces" link on the sold-out product page.');

        $this->assertSame(route('category.show', $this->category), html_entity_decode($m[1]));
    }
}
