<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A sold-out product belongs at the end of a row, not second in a row of four.
 *
 * The reported bug: "Trending Now" put a sold-out shirt in slot two, so a
 * quarter of the homepage's most valuable strip was a card nobody could buy.
 * Every list built its own ORDER BY and not one of them mentioned stock, so the
 * same thing could happen on any row on the site.
 *
 * These cover the rows this commit owns. The storefront GRIDS - shop, category,
 * brand, deals, search, flash sale - are mid-migration onto the shared
 * App\Support\ProductFilters, and get the same treatment from its sort().
 */
class OutOfStockOrderingTest extends TestCase
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
    }

    private function makeProduct(string $name, array $overrides = []): Product
    {
        // created_at is set behind the model, not mass-assigned: a row sorted
        // newest-first only proves anything if the sold-out product is the
        // newer one, and two rows written in the same second tie on a
        // second-precision timestamp and fall back to insertion order.
        $createdAt = $overrides['created_at'] ?? null;
        unset($overrides['created_at']);

        $product = Product::create(array_merge([
            'name' => $name,
            'slug' => Str::slug($name),
            'sku' => strtoupper(Str::slug($name)),
            'price' => 500,
            'mrp' => 900,
            'cost_price' => 200,
            'stock_quantity' => 10,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
        ], $overrides));

        if ($createdAt !== null) {
            Product::where('id', $product->id)->update(['created_at' => $createdAt]);
            $product->refresh();
        }

        return $product;
    }

    /** Where each name first appears in the rendered page. */
    private function positions(string $html, array $names): array
    {
        $found = [];

        foreach ($names as $name) {
            $at = strpos($html, $name);
            $this->assertNotFalse($at, sprintf('Expected "%s" on the page.', $name));
            $found[$name] = $at;
        }

        return $found;
    }

    public function test_the_home_page_rows_put_sold_out_last(): void
    {
        // The reported case: the sold-out card sat in slot two of Trending Now,
        // between two buyable ones. Every home row - featured, new arrivals,
        // bestsellers, trending, deals - is ordered from the same key, so the
        // sold-out product trails all of them.
        $this->makeProduct('Homerow Available One', ['sales_count' => 50]);
        $this->makeProduct('Homerow Soldout', [
            'sales_count' => 40,
            'stock_quantity' => 0,
            'stock_status' => 'out_of_stock',
        ]);
        $this->makeProduct('Homerow Available Two', ['sales_count' => 30]);

        $html = $this->get('/')->assertOk()->getContent();
        $at = $this->positions($html, ['Homerow Available One', 'Homerow Available Two', 'Homerow Soldout']);

        $this->assertLessThan($at['Homerow Soldout'], $at['Homerow Available One']);
        $this->assertLessThan($at['Homerow Soldout'], $at['Homerow Available Two']);
    }

    public function test_a_home_row_sinks_a_product_marked_in_stock_with_nothing_on_the_shelf(): void
    {
        // The half of isInStock() that ordering on stock_status alone would
        // miss. The card reads "Out of Stock" off the quantity, so the sort has
        // to read it too, or the badge and the position disagree.
        //
        // New arrivals leads the home page and runs newest-first, so the empty
        // shelf is the newer row: without a stock key it opens the section.
        $this->makeProduct('Homerow Available', ['created_at' => now()->subDays(2)]);
        $this->makeProduct('Homerow Empty Shelf', [
            'stock_quantity' => 0,
            'stock_status' => 'in_stock',
            'created_at' => now(),
        ]);

        $html = $this->get('/')->assertOk()->getContent();
        $at = $this->positions($html, ['Homerow Available', 'Homerow Empty Shelf']);

        $this->assertLessThan($at['Homerow Empty Shelf'], $at['Homerow Available']);
    }

    public function test_the_wishlist_lists_what_can_still_be_bought_first(): void
    {
        $available = $this->makeProduct('Wishlist Available');
        $soldOut = $this->makeProduct('Wishlist Soldout', [
            'stock_quantity' => 0,
            'stock_status' => 'out_of_stock',
        ]);

        // Favourited sold-out first, so the ids alone would list it first.
        $response = $this->getJson('/wishlist/items?ids='.$soldOut->id.','.$available->id)->assertOk();

        $this->assertSame(
            ['Wishlist Available', 'Wishlist Soldout'],
            array_column($response->json('items'), 'name')
        );
    }

    public function test_the_chosen_sort_still_applies_inside_each_block(): void
    {
        // Stock is the primary key, the row's own order the secondary. On the
        // bestsellers page that is sales_count, and it has to hold among what is
        // buyable and again among what is not - the sold-out block is ordered,
        // not dumped.
        $this->makeProduct('Rank Available High', ['sales_count' => 90]);
        $this->makeProduct('Rank Available Low', ['sales_count' => 70]);
        $this->makeProduct('Rank Soldout High', ['sales_count' => 100, 'stock_quantity' => 0, 'stock_status' => 'out_of_stock']);
        $this->makeProduct('Rank Soldout Low', ['sales_count' => 80, 'stock_quantity' => 0, 'stock_status' => 'out_of_stock']);

        $ordered = Product::query()
            ->where('is_active', true)
            ->inStockFirst()
            ->orderBy('sales_count', 'desc')
            ->pluck('name')
            ->all();

        $this->assertSame([
            'Rank Available High',
            'Rank Available Low',
            'Rank Soldout High',
            'Rank Soldout Low',
        ], $ordered);
    }

    public function test_the_scope_agrees_with_the_card_on_what_is_out_of_stock(): void
    {
        // One source of truth: whatever isInStock() calls unavailable is what
        // the scope has to sink, backorder and empty shelf included.
        //
        // Written unavailable-first, so the secondary orderBy('id') below would
        // hand back exactly the wrong order if the scope did nothing.
        $this->makeProduct('Scope Zero Quantity', ['stock_quantity' => 0]);
        $this->makeProduct('Scope Flagged Out', ['stock_status' => 'out_of_stock']);
        $this->makeProduct('Scope Backorder', ['stock_status' => 'backorder']);
        $this->makeProduct('Scope Available');

        $ordered = Product::query()->inStockFirst()->orderBy('id')->get();

        $seenUnavailable = false;

        foreach ($ordered as $product) {
            if (! $product->isInStock()) {
                $seenUnavailable = true;

                continue;
            }

            $this->assertFalse(
                $seenUnavailable,
                $product->name.' is buyable but sorted behind a sold-out product.'
            );
        }

        $this->assertSame('Scope Available', $ordered->first()->name);
    }
}
