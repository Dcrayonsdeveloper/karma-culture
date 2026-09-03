<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A URL this site does not serve gets the 404 page.
 *
 * It used to do something else. Four paths that had been pages - /products,
 * /returns, /orders/{id} and /orders/{id}/track - were registered as permanent
 * redirects to whatever replaced them. Typing /products put you on /shop.
 *
 * That is worse than a 404 in both directions. The visitor is moved to a page
 * they did not ask for, with nothing to tell them the address they were holding
 * is dead; and whoever wrote the bad link - an admin filling in a banner, a
 * marketer building a campaign URL - never finds out it is bad, because the site
 * covers for them silently and forever. The forwarding was not even faithful:
 * a Laravel redirect route carries path parameters only, so /products?category=
 * kurtas arrived at an unfiltered /shop.
 *
 * The redirects are gone. Everything the site emits goes through route() and
 * points at a real page already, and the stored links and page copy that still
 * named an old path were repointed by the repoint_legacy_storefront_links
 * migration, so nothing depends on the forwarding that used to happen here.
 */
class NotFoundUrlsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function legacyPaths(): array
    {
        return [
            'the all-products page before it became /shop' => ['/products'],
            'the same, with a trailing slash' => ['/products/'],
            'the same, with the filter that the redirect used to throw away' => ['/products?category=shirts'],
            'the returns page before it became /returns-policy' => ['/returns'],
            'order pages before they moved under /account' => ['/orders/1'],
            'order tracking before it moved under /account' => ['/orders/1/track'],
        ];
    }

    /**
     * @dataProvider legacyPaths
     */
    public function test_a_path_the_site_no_longer_serves_is_a_404(string $path): void
    {
        $this->seedCatalogue();

        $this->get($path)->assertNotFound();
    }

    public function test_an_address_that_never_existed_is_a_404(): void
    {
        // The plain case, to catch anyone adding a catch-all route that sweeps
        // unknown URLs onto the home page.
        $this->get('/there-is-no-page-here')->assertNotFound();
        $this->get('/shop/there-is-no-page-here')->assertNotFound();
        $this->get('/product/no-such-product')->assertNotFound();
        $this->get('/category/no-such-category')->assertNotFound();
    }

    public function test_the_404_page_renders_rather_than_erroring(): void
    {
        // The 404 view is what the visitor actually sees, so a 404 that 500s on
        // the way out would defeat the whole change.
        $this->get('/there-is-no-page-here')
            ->assertNotFound()
            ->assertSee('Page Not Found', false);
    }

    public function test_no_legacy_path_answers_with_a_redirect(): void
    {
        // A guard on the shape of the response, not just its status.
        // assertNotFound() above would still pass if someone reintroduced
        // /products as a redirect that happened to land on a 404.
        foreach (array_column(self::legacyPaths(), 0) as $path) {
            $response = $this->get($path);

            $this->assertFalse(
                $response->isRedirect(),
                "{$path} answered with a redirect to "
                    .($response->headers->get('Location') ?? '?')
                    .'. A path this site does not serve must answer 404, not forward the visitor.'
            );
        }
    }

    public function test_the_route_table_holds_no_redirect_aliases_at_all(): void
    {
        // The durable half of this. The cases above name the six paths that were
        // wrong on the day; this one is the rule, and it catches the seventh -
        // whoever next decides that an old URL deserves a quiet 301 to its
        // replacement, for whatever path that turns out to be.
        //
        // Laravel implements Route::redirect() and Route::permanentRedirect()
        // by pointing the route at its own RedirectController, so a redirect
        // alias is identifiable from the route table without calling anything.
        //
        // This bans redirect *aliases* only - a controller that redirects after
        // doing something (a completed form, an auth guard, a cart action) is
        // ordinary and untouched, because it is not a route whose entire job is
        // to forward one path to another.
        $aliases = [];

        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
            if (str_contains($route->getActionName(), 'RedirectController')) {
                $aliases[] = '/'.$route->uri().' -> '.($route->defaults['destination'] ?? '?');
            }
        }

        $this->assertSame(
            [],
            $aliases,
            "These paths are forwarded rather than served or refused:\n  ".implode("\n  ", $aliases)
                ."\n\nA path this site does not serve gets the 404 page. If the destination is\n"
                ."where visitors should end up, fix whatever is emitting the old path -\n"
                ."a route() call, a seeded link, or a row the admin can edit."
        );
    }

    public function test_the_pages_that_replaced_them_still_answer(): void
    {
        // The other half of the deal: removing the aliases must not have taken
        // the real pages with them.
        $this->seedCatalogue();

        $this->get('/shop')->assertOk();
        $this->get('/returns-policy')->assertOk();
        $this->get('/product/poplin-shirt')->assertOk();
        $this->get('/category/shirts')->assertOk();

        $this->actingAs(User::factory()->create())
            ->get('/account/orders')
            ->assertOk();
    }

    private function seedCatalogue(): void
    {
        $category = Category::create([
            'name' => 'Shirts',
            'slug' => 'shirts',
            'is_active' => true,
        ]);

        $brand = Brand::create([
            'name' => 'Karmaa',
            'slug' => 'karmaa',
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
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }
}
