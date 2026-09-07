<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Favourites - the wishlist's twin.
 *
 * Both lists are the same code now (App\Http\Controllers\SavedListController),
 * so most of what is asserted here is asserted for the wishlist too. That is
 * the point: the pair is only worth sharing an implementation if something
 * fails when they stop agreeing.
 *
 * The cookie test is the one that earns its keep on its own. kk_favourites is
 * written by JavaScript and so arrives in plain text; if it is ever dropped
 * from the encryptCookies(except:) list in bootstrap/app.php, Laravel discards
 * it and every one of these pages renders "You have no favourites yet" over a
 * list the browser is still holding. Nothing else in the suite would notice.
 */
class FavouritesRouteTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $suffix = ''): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'shirts'],
            ['name' => 'Shirts', 'is_active' => true],
        );

        return Product::create([
            'name' => 'Poplin Shirt'.$suffix,
            'slug' => 'poplin-shirt'.($suffix !== '' ? '-'.trim($suffix) : ''),
            'sku' => 'POPLIN'.$suffix,
            'price' => 500,
            'mrp' => 900,
            'cost_price' => 200,
            'stock_quantity' => 10,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function test_the_data_endpoint_lives_under_the_favourites_prefix(): void
    {
        $product = $this->makeProduct();

        $this->getJson('/favourites/items?ids='.$product->id)
            ->assertOk()
            ->assertJsonPath('items.0.id', $product->id);
    }

    public function test_a_guest_can_read_the_favourites_data(): void
    {
        $product = $this->makeProduct();

        // Reading is open on both lists: the page and the header badge answer
        // for a signed-out shopper, because the list is a cookie.
        $this->getJson('/favourites/items?ids='.$product->id)->assertOk();
    }

    public function test_no_ids_yields_an_empty_list_rather_than_an_error(): void
    {
        $this->getJson('/favourites/items')
            ->assertOk()
            ->assertExactJson(['items' => []]);
    }

    /**
     * The reason bootstrap/app.php excepts this cookie from encryption. Without
     * that line the page renders empty and nothing else goes wrong, which is
     * the worst kind of failure to have no test for.
     */
    public function test_the_page_is_rendered_from_the_plain_text_cookie(): void
    {
        $product = $this->makeProduct();

        $this->withUnencryptedCookie('kk_favourites', json_encode([$product->id]))
            ->get('/favourites')
            ->assertOk()
            ->assertSee($product->name);
    }

    /** The same, for the wishlist - one exception list, two names to lose. */
    public function test_the_wishlist_page_is_rendered_from_its_plain_text_cookie(): void
    {
        $product = $this->makeProduct();

        $this->withUnencryptedCookie('kk_wishlist', json_encode([$product->id]))
            ->get('/wishlist')
            ->assertOk()
            ->assertSee($product->name);
    }

    /** A guest reaches the page itself, not the login form. */
    public function test_a_guest_can_open_the_favourites_page(): void
    {
        $this->get('/favourites')->assertOk()->assertSee('My Favourites');
    }

    /**
     * The lists are separate. Saving to one must not fill the other, which is
     * the whole reason there are two of them.
     */
    public function test_the_two_lists_do_not_share_a_cookie(): void
    {
        $wishlisted = $this->makeProduct(' A');
        $favourited = $this->makeProduct(' B');

        $this->withUnencryptedCookie('kk_wishlist', json_encode([$wishlisted->id]))
            ->withUnencryptedCookie('kk_favourites', json_encode([$favourited->id]))
            ->get('/favourites')
            ->assertOk()
            ->assertSee($favourited->name)
            ->assertDontSee($wishlisted->name);
    }

    /**
     * Everything in the cookie is attacker-controlled - the browser can be told
     * to hold anything in it - so a hand-written one must not reach the query
     * or throw.
     */
    public function test_a_junk_cookie_is_treated_as_an_empty_list(): void
    {
        $this->withUnencryptedCookie('kk_favourites', 'not json at all')
            ->get('/favourites')
            ->assertOk()
            ->assertSee('You have no favourites yet');

        $this->withUnencryptedCookie('kk_favourites', json_encode(["1'); DROP TABLE products;--", null, 0]))
            ->get('/favourites')
            ->assertOk();
    }

    /**
     * The card on this page must carry the star whatever the admin has done to
     * the card settings - it is the only way to take something off the list, so
     * a page without it is a list nobody can empty.
     */
    public function test_the_page_forces_its_own_button_onto_the_card(): void
    {
        $product = $this->makeProduct();

        \App\Models\Setting::updateOrCreate(
            ['key' => 'product_card_favourites'],
            ['value' => '0', 'type' => 'boolean', 'group' => 'product_card'],
        );
        \Illuminate\Support\Facades\Cache::forget('setting.product_card_favourites');

        $this->withUnencryptedCookie('kk_favourites', json_encode([$product->id]))
            ->get('/favourites')
            ->assertOk()
            ->assertSee('$store.favourites.toggle('.$product->id.')', false);
    }
}
