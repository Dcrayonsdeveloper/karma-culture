<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * A texture the shopper picked has to survive the whole way to the order.
 *
 * The line it rides on is identified by product + variant + size + colour +
 * texture, which is the part most likely to be got wrong: leave texture out of
 * any one of the three copies of that key - the unique index, the cart's own
 * "already in it" lookup, the guest-cart merge on sign-in - and Matte and
 * Glossy of the same black M either merge into one line or hit a MySQL 1062 and
 * 500 the request. The colour rollout hit exactly that and there was no test to
 * catch it, so there is one now.
 */
class CartTextureTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Men', 'slug' => 'men', 'is_active' => true]);

        $this->product = Product::create([
            'name' => 'Oxford Shirt',
            'slug' => 'oxford-shirt',
            'sku' => 'OXFORD',
            'price' => 799,
            'mrp' => 999,
            'stock_quantity' => 50,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
            'attributes' => [
                'Colours' => [['name' => 'Black', 'hex' => '#000000']],
                'Textures' => ['Matte', 'Glossy'],
            ],
        ]);

        ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'M',
            'sku' => 'OXFORD-M',
            'price' => 799,
            'stock_quantity' => 30,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
    }

    private function add(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->user)->postJson('/cart/add', array_merge([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'size' => 'M',
            'colour' => 'Black',
            'texture' => 'Matte',
        ], $overrides));
    }

    public function test_the_chosen_texture_is_stored_on_the_cart_line(): void
    {
        $this->add()->assertOk();

        $this->assertSame('Matte', CartItem::query()->firstOrFail()->texture);
    }

    public function test_two_textures_of_one_size_and_colour_are_two_lines(): void
    {
        // The 1062 case. Same product, same variant, same size, same colour -
        // only the texture differs, so the line key has to tell them apart.
        $this->add(['texture' => 'Matte'])->assertOk();
        $this->add(['texture' => 'Glossy'])->assertOk();

        $this->assertSame(2, CartItem::query()->count());
        $this->assertEqualsCanonicalizing(
            ['Matte', 'Glossy'],
            CartItem::query()->pluck('texture')->all(),
        );
    }

    public function test_the_same_texture_twice_is_one_line_with_two_of_it(): void
    {
        $this->add()->assertOk();
        $this->add()->assertOk();

        $this->assertSame(1, CartItem::query()->count());
        $this->assertSame(2, CartItem::query()->firstOrFail()->quantity);
    }

    public function test_a_texture_the_product_does_not_offer_is_refused(): void
    {
        // Free text on the wire, written to the line, carried onto the order and
        // printed on the invoice - so the server has to hold it to what the
        // product page can actually render.
        $this->add(['texture' => 'Corduroy'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'That texture is not available for this product.');

        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_the_casing_a_page_round_trips_is_not_refused(): void
    {
        $this->add(['texture' => 'matte'])->assertOk();

        $this->assertSame(1, CartItem::query()->count());
    }

    public function test_a_product_with_no_textures_still_takes_an_add_to_cart(): void
    {
        $this->product->update(['attributes' => ['Colours' => [['name' => 'Black', 'hex' => '#000000']]]]);

        $this->add(['texture' => null])->assertOk();

        $this->assertNull(CartItem::query()->firstOrFail()->texture);
    }

    public function test_the_cart_feed_carries_the_texture_to_the_drawer(): void
    {
        $this->add()->assertOk();

        $this->actingAs($this->user)
            ->getJson('/cart/data')
            ->assertOk()
            ->assertJsonPath('items.0.texture', 'Matte');
    }

    public function test_the_texture_reaches_the_order_and_stays_there(): void
    {
        $this->add(['texture' => 'Matte'])->assertOk();
        $this->add(['texture' => 'Glossy'])->assertOk();

        $cart = Cart::query()->firstOrFail();

        // The order is written straight off the cart lines, so this is the hop
        // the whole chain hangs on: build it the way checkout does rather than
        // driving the payment form, which is a different test's job.
        $order = Order::create([
            'user_id' => $this->user->id,
            'order_number' => 'TEST-1',
            'status' => 'pending',
            'subtotal' => 1598,
            'total' => 1598,
            'payment_status' => 'pending',
        ]);

        foreach ($cart->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'product_name' => $this->product->name,
                'size' => $item->size,
                'colour' => $item->colour,
                'texture' => $item->texture,
                'sku' => $this->product->sku,
                'mrp' => 999,
                'price' => 799,
                'quantity' => $item->quantity,
                'total' => 799 * $item->quantity,
            ]);
        }

        $this->assertEqualsCanonicalizing(
            ['Matte', 'Glossy'],
            OrderItem::query()->pluck('texture')->all(),
        );

        // A past order must keep reading correctly after the product is edited:
        // the line holds its own copy rather than looking the texture up again.
        $this->product->update(['attributes' => ['Textures' => ['Ribbed']]]);

        $this->assertEqualsCanonicalizing(
            ['Matte', 'Glossy'],
            OrderItem::query()->pluck('texture')->all(),
        );
    }

    public function test_the_customer_sees_the_texture_on_their_own_order(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'order_number' => 'TEST-2',
            'status' => 'pending',
            'subtotal' => 799,
            'total' => 799,
            'payment_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'size' => 'M',
            'colour' => 'Black',
            'texture' => 'Matte',
            'sku' => 'OXFORD',
            'mrp' => 999,
            'price' => 799,
            'quantity' => 1,
            'total' => 799,
        ]);

        $this->actingAs($this->user)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee('Texture: Matte');
    }

    public function test_the_admin_sees_the_texture_on_the_order(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'order_number' => 'TEST-3',
            'status' => 'pending',
            'subtotal' => 799,
            'total' => 799,
            'payment_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'size' => 'M',
            'colour' => 'Black',
            'texture' => 'Matte',
            'sku' => 'OXFORD',
            'mrp' => 999,
            'price' => 799,
            'quantity' => 1,
            'total' => 799,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']), 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Texture:');
    }

    public function test_reordering_puts_the_texture_back_in_the_cart(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'order_number' => 'TEST-4',
            'status' => 'delivered',
            'subtotal' => 1598,
            'total' => 1598,
            'payment_status' => 'paid',
        ]);

        foreach (['Matte', 'Glossy'] as $texture) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'size' => 'M',
                'colour' => 'Black',
                'texture' => $texture,
                'sku' => 'OXFORD-'.$texture,
                'mrp' => 999,
                'price' => 799,
                'quantity' => 1,
                'total' => 799,
            ]);
        }

        $this->actingAs($this->user)
            ->post(route('account.orders.reorder', $order))
            ->assertRedirect();

        // Two lines, not one double-quantity line stripped of what was bought.
        $this->assertSame(2, CartItem::query()->count());
        $this->assertEqualsCanonicalizing(
            ['Matte', 'Glossy'],
            CartItem::query()->pluck('texture')->all(),
        );
    }

    public function test_the_api_stores_the_texture_and_keeps_the_two_lines_apart(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        foreach (['Matte', 'Glossy'] as $texture) {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->postJson('/api/v1/cart/items', [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'size' => 'M',
                    'colour' => 'Black',
                    'texture' => $texture,
                ])
                ->assertSuccessful();
        }

        $this->assertSame(2, CartItem::query()->count());
        $this->assertEqualsCanonicalizing(
            ['Matte', 'Glossy'],
            CartItem::query()->pluck('texture')->all(),
        );
    }

    public function test_the_api_refuses_a_texture_the_product_does_not_offer(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 1,
                'texture' => 'Corduroy',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'That texture is not available for this product.');
    }
}
