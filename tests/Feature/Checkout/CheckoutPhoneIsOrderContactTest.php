<?php

namespace Tests\Feature\Checkout;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The phone box on checkout is the number for THAT order - the one the shop
 * and the delivery partner ring about this delivery - and not the number
 * attached to the account or to the address book entry.
 *
 * It used to live inside the new-address form, disabled whenever a saved
 * address was picked, so a saved-address order carried whatever number that
 * address had been saved with and the customer had no way to give another.
 */
class CheckoutPhoneIsOrderContactTest extends TestCase
{
    use RefreshDatabase;

    /** The number saved against the account itself. */
    private const ACCOUNT_PHONE = '9000000001';

    /** The number saved against the address book entry. */
    private const ADDRESS_PHONE = '9876543210';

    /** The number typed into the checkout box for this one order. */
    private const ORDER_PHONE = '9123456780';

    private User $user;
    private Product $product;
    private UserAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role'  => 'customer',
            'email' => 'account.holder@example.com',
            'phone' => self::ACCOUNT_PHONE,
        ]);

        $category = Category::create([
            'name'      => 'Kids Shoes',
            'slug'      => 'kids-shoes',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name'           => 'Kids Sneakers',
            'slug'           => 'kids-sneakers',
            'sku'            => 'KS-PHONE-001',
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
            'phone'          => self::ADDRESS_PHONE,
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
     * The box has to be outside the new-address form, or a customer shipping to
     * a saved address never sees it. The false second argument keeps the
     * needles unescaped: these are raw attributes in the markup.
     */
    public function test_the_phone_box_is_asked_for_even_with_a_saved_address(): void
    {
        $this->fillCart();

        $response = $this->actingAs($this->user)->get('/checkout');

        $response->assertStatus(200);
        $response->assertSee('name="phone"', false);
        $response->assertSee('Phone for this order', false);
        // Bound to Alpine state rather than to the address form's disabled
        // rule, so it is submitted whichever address is selected.
        $response->assertSee('x-model="phone"', false);
    }

    /**
     * The point of the change: a saved address no longer decides the number.
     */
    public function test_a_saved_address_order_carries_the_number_from_the_form(): void
    {
        $this->fillCart();

        $response = $this->actingAs($this->user)->post('/checkout/process', [
            'address_id'     => $this->address->id,
            'phone'          => self::ORDER_PHONE,
            'payment_method' => 'cod',
            'notes'          => '',
        ]);

        $response->assertSessionHasNoErrors();

        $order = Order::where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame(self::ORDER_PHONE, $order->shipping_address_snapshot['phone']);
        $this->assertSame(self::ORDER_PHONE, $order->billing_address_snapshot['phone']);
        $this->assertSame(self::ORDER_PHONE, $order->metadata['guest_phone']);
    }

    /**
     * Giving a number for one order is not a change of address book or of
     * account details - both are left exactly as they were.
     */
    public function test_the_order_number_does_not_write_back_to_the_account(): void
    {
        $this->fillCart();

        $this->actingAs($this->user)->post('/checkout/process', [
            'address_id'     => $this->address->id,
            'phone'          => self::ORDER_PHONE,
            'payment_method' => 'cod',
        ]);

        $this->assertSame(self::ADDRESS_PHONE, $this->address->fresh()->phone);
        $this->assertSame(self::ACCOUNT_PHONE, $this->user->fresh()->phone);
    }

    /**
     * A typed address takes the same route, and the account's own number does
     * not overwrite what was typed.
     */
    public function test_a_typed_address_order_carries_the_number_from_the_form(): void
    {
        $this->fillCart();

        $this->actingAs($this->user)->post('/checkout/process', [
            'full_name'      => 'Test User',
            'phone'          => self::ORDER_PHONE,
            'address_line_1' => '12 Residency Road',
            'city'           => 'Bengaluru',
            'state'          => 'Karnataka',
            'postal_code'    => '560025',
            'payment_method' => 'cod',
        ]);

        $order = Order::where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame(self::ORDER_PHONE, $order->shipping_address_snapshot['phone']);
        $this->assertSame(self::ORDER_PHONE, $order->metadata['guest_phone']);
    }

    /**
     * The decoration customers type is stripped before the number is stored, so
     * the admin, the invoice and the Shiprocket handoff all read one shape.
     */
    public function test_a_decorated_number_is_stored_as_the_bare_ten_digits(): void
    {
        $this->fillCart();

        $this->actingAs($this->user)->post('/checkout/process', [
            'address_id'     => $this->address->id,
            'phone'          => '+91 91234 56780',
            'payment_method' => 'cod',
        ]);

        $order = Order::where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame(self::ORDER_PHONE, $order->shipping_address_snapshot['phone']);
        $this->assertSame(self::ORDER_PHONE, $order->metadata['guest_phone']);
    }

    /**
     * The page always submits a number, so a POST without one is an old form or
     * an API-shaped request: the saved address's own number stands in rather
     * than the order being left with nobody to call.
     */
    public function test_a_post_with_no_phone_falls_back_to_the_saved_address(): void
    {
        $this->fillCart();

        $response = $this->actingAs($this->user)->post('/checkout/process', [
            'address_id'     => $this->address->id,
            'payment_method' => 'cod',
        ]);

        $response->assertSessionHasNoErrors();

        $order = Order::where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame(self::ADDRESS_PHONE, $order->shipping_address_snapshot['phone']);
        $this->assertSame(self::ADDRESS_PHONE, $order->metadata['guest_phone']);
    }

    /**
     * A number that is not a mobile number is still refused, saved address or
     * not - an unreachable contact is worse than a rejected form.
     */
    public function test_a_junk_number_is_refused_even_with_a_saved_address(): void
    {
        $this->fillCart();

        $response = $this->actingAs($this->user)->post('/checkout/process', [
            'address_id'     => $this->address->id,
            'phone'          => '12345',
            'payment_method' => 'cod',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('orders', 0);
    }

    /**
     * The order page the shop works from shows the order's own number, so
     * nobody rings the account holder for a delivery going somewhere else.
     */
    public function test_the_admin_order_page_shows_the_order_number(): void
    {
        $this->fillCart();

        $this->actingAs($this->user)->post('/checkout/process', [
            'address_id'     => $this->address->id,
            'phone'          => self::ORDER_PHONE,
            'payment_method' => 'cod',
        ]);

        $order = Order::where('user_id', $this->user->id)->firstOrFail();

        $admin = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id'   => $admin->id,
            'role'      => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/orders/' . $order->id);

        $response->assertStatus(200);
        $response->assertSee('Contact for this order');
        $response->assertSee(self::ORDER_PHONE);
    }
}
