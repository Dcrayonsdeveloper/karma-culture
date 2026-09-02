<?php

namespace Tests\Feature\Checkout;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checkout used to ask for the confirmation email in an editable box that was
 * merely prefilled from the account, so the address an order was confirmed to
 * had nothing to do with the account that placed it. The box is a read-only
 * display now, and - the half that actually matters - the server reads the
 * address off the user row instead of the POST.
 */
class CheckoutEmailIsAccountEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private UserAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role'  => 'customer',
            'email' => 'account.holder@example.com',
        ]);

        $category = Category::create([
            'name'      => 'Kids Shoes',
            'slug'      => 'kids-shoes',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name'           => 'Kids Sneakers',
            'slug'           => 'kids-sneakers',
            'sku'            => 'KS-EMAIL-001',
            'price'          => 1499,
            'mrp'            => 1999,
            'cost_price'     => 600,
            'stock_quantity' => 15,
            'category_id'    => $category->id,
            'status'         => 'approved',
            'is_active'      => true,
        ]);

        $this->address = UserAddress::create([
            'user_id'        => $this->user->id,
            'label'          => 'Home',
            'first_name'     => 'Test',
            'last_name'      => 'User',
            'phone'          => '9876543210',
            'address_line_1' => '123 Test Street',
            'city'           => 'Mumbai',
            'state'          => 'Maharashtra',
            'postal_code'    => '400001',
            'country'        => 'IN',
            'is_default'     => true,
        ]);
    }

    private function fillCart(): void
    {
        $this->actingAs($this->user)->post('/cart/add', [
            'product_id' => $this->product->id,
            'quantity'   => 1,
        ]);
    }

    /**
     * The address is still shown - the customer has to be able to see where the
     * confirmation is going - but not as something the form submits.
     */
    public function test_checkout_shows_the_account_email_as_a_read_only_box(): void
    {
        $this->fillCart();

        $response = $this->actingAs($this->user)->get('/checkout');

        $response->assertStatus(200);
        $response->assertSee($this->user->email, false);
        // The false second argument keeps the needle unescaped: these are raw
        // attributes in the markup, not display text.
        $response->assertDontSee('name="email"', false);
        $response->assertSee('id="kk-co-email"', false);
        $response->assertSee('readonly', false);
    }

    /**
     * The point of the change. A POST is not the page, so the read-only
     * attribute proves nothing on its own - an email typed straight into the
     * request must not reach the order.
     */
    public function test_a_posted_email_cannot_replace_the_account_address(): void
    {
        $this->fillCart();

        $this->actingAs($this->user)->post('/checkout/process', [
            'address_id'     => $this->address->id,
            'email'          => 'somebody.else@example.com',
            'payment_method' => 'cod',
            'notes'          => '',
        ]);

        $order = Order::where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame($this->user->email, $order->shipping_address_snapshot['email']);
        $this->assertSame($this->user->email, $order->billing_address_snapshot['email']);
        $this->assertSame($this->user->email, $order->metadata['guest_email']);
    }

    /**
     * The box no longer submits anything, so an order arriving with no email
     * key at all is the normal case - not a validation failure.
     */
    public function test_an_order_with_no_email_in_the_post_still_goes_through(): void
    {
        $this->fillCart();

        $response = $this->actingAs($this->user)->post('/checkout/process', [
            'address_id'     => $this->address->id,
            'payment_method' => 'cod',
            'notes'          => '',
        ]);

        $response->assertSessionHasNoErrors();

        $order = Order::where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame($this->user->email, $order->shipping_address_snapshot['email']);
    }

    /**
     * A typed address takes the same route: nothing about the new-address form
     * reopens the email as an input.
     */
    public function test_a_typed_address_order_also_carries_the_account_email(): void
    {
        $this->fillCart();

        $this->actingAs($this->user)->post('/checkout/process', [
            'full_name'      => 'Test User',
            'email'          => 'somebody.else@example.com',
            'phone'          => '9876543210',
            'address_line_1' => '12 Residency Road',
            'city'           => 'Bengaluru',
            'state'          => 'Karnataka',
            'postal_code'    => '560025',
            'payment_method' => 'cod',
        ]);

        $order = Order::where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame($this->user->email, $order->shipping_address_snapshot['email']);
    }
}
