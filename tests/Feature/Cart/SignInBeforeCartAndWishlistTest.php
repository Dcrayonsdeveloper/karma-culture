<?php

namespace Tests\Feature\Cart;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The cart and the wishlist take an account.
 *
 * Both used to work for a signed-out visitor - the cart in a session row, the
 * wishlist in a cookie - and checkout was the first thing to ask who they
 * were. That put the login page at the end of the journey rather than the
 * start, after the basket was full and there was something to lose.
 *
 * Two gates, and they are not redundant. The button checks first, so a guest
 * never makes the request and never sees a failure - they are simply taken to
 * the login page, which is told the page to come back to. The routes check
 * again, because a button is not a security boundary: a stale tab, a replayed
 * form or a script reaches the same route with no page in front of it.
 */
class SignInBeforeCartAndWishlistTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'customer',
            'email' => 'shopper@example.com',
            'password' => Hash::make('Password!2345'),
        ]);

        $category = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'is_active' => true]);

        $this->product = Product::create([
            'name' => 'Oxford Shirt',
            'slug' => 'oxford-shirt',
            'sku' => 'OXFORD',
            'price' => 799,
            'mrp' => 999,
            'cost_price' => 300,
            'stock_quantity' => 20,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    /** Every route that changes a cart, not only the one that fills it. */
    public static function guardedCartRoutes(): array
    {
        return [
            'add' => ['post', '/cart/add'],
            'clear' => ['delete', '/cart'],
            'update an item' => ['put', '/cart/1'],
            'remove an item' => ['delete', '/cart/1'],
            'apply a coupon' => ['post', '/cart/apply-coupon'],
            'remove a coupon' => ['delete', '/cart/remove-coupon'],
        ];
    }

    /**
     * @dataProvider guardedCartRoutes
     */
    public function test_a_guest_cannot_change_a_cart(string $method, string $url): void
    {
        $this->{$method}($url, ['product_id' => $this->product->id, 'quantity' => 1])
            ->assertRedirect(route('login'));
    }

    public function test_a_signed_in_customer_still_fills_their_cart(): void
    {
        $this->actingAs($this->user)
            ->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 2])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    public function test_a_guest_cannot_change_a_wishlist(): void
    {
        $this->post(route('wishlist.store', $this->product))->assertRedirect(route('login'));
        $this->delete(route('wishlist.destroy', $this->product))->assertRedirect(route('login'));
    }

    /**
     * The button names the page it was pressed on, because the middleware
     * cannot: it sees a POST from a script, and an intended URL of /cart/add
     * would send the customer back to a route that does not answer GET.
     */
    public function test_the_login_page_remembers_where_the_customer_was(): void
    {
        $this->get('/login?next=/product/oxford-shirt')->assertOk();

        $this->post('/login', [
            'email' => 'shopper@example.com',
            'password' => 'Password!2345',
        ])->assertRedirect('/product/oxford-shirt');
    }

    /**
     * ...but only to a page on this site. A login page that will forward
     * anywhere is the shape a credential phishing chain needs: sign in on the
     * real shop, land on somebody else's copy of it still believing you are here.
     */
    public function test_the_login_page_refuses_to_forward_off_site(): void
    {
        foreach (['https://evil.example/steal', '//evil.example/steal', '/\\evil.example'] as $hostile) {
            $this->get('/login?next='.urlencode($hostile))->assertOk();

            $this->post('/login', [
                'email' => 'shopper@example.com',
                'password' => 'Password!2345',
            ])->assertRedirect(route('account.dashboard'));

            $this->post('/logout');
        }
    }

    /** The button gates before the request, so a guest never sees a failure. */
    public function test_the_buttons_check_before_they_ask(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertMatchesRegularExpression(
            '/function kkRequireLogin\(\)\s*\{\s*if \(document\.body\.dataset\.authenticated === .true.\) return true;/',
            $js,
            'The guard reads the flag the layout renders on <body>.'
        );

        // Cart add and both halves of the wishlist toggle.
        $this->assertGreaterThanOrEqual(
            3,
            substr_count($js, 'kkRequireLogin()'),
            'Every entry point has to gate, or the one that does not is the way around it.'
        );

        // And the stale-tab case, where the flag says signed in and the server
        // disagrees.
        $this->assertStringContainsString('error.response.status === 401', $js);
    }
}
