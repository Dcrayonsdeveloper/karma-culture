<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\PosRegister;
use App\Models\Product;
use App\Models\Staff;
use App\Models\StaffShift;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCartTest extends TestCase
{
    use RefreshDatabase;

    private array $posSession;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $store = Store::create(['name' => 'Test Store', 'code' => 'TS-01', 'is_active' => true]);
        $register = PosRegister::create(['store_id' => $store->id, 'name' => 'C1', 'device_id' => 'POS-T1', 'status' => 'active']);
        $user = User::factory()->create(['first_name' => 'Cashier', 'role' => 'admin']);
        $staff = Staff::create(['user_id' => $user->id, 'employee_id' => 'E-01', 'role' => 'cashier', 'store_id' => $store->id, 'pin' => bcrypt('1234'), 'is_active' => true]);
        $shift = StaffShift::create(['staff_id' => $staff->id, 'store_id' => $store->id, 'register_id' => $register->id, 'shift_start' => now(), 'opening_cash' => 1000, 'status' => 'open']);

        $category = Category::create(['name' => 'Kids Wear', 'slug' => 'kids-wear', 'is_active' => true]);

        $this->product = Product::create([
            'name' => 'Test T-Shirt',
            'slug' => 'test-t-shirt',
            'sku' => 'TST-001',
            'price' => 499,
            'mrp' => 699,
            'cost_price' => 200,
            'stock_quantity' => 50,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $this->posSession = [
            'pos_staff_id' => $staff->id,
            'pos_store_id' => $store->id,
            'pos_device_id' => 'POS-T1',
            'pos_register_id' => $register->id,
            'pos_shift_id' => $shift->id,
            'pos_staff_name' => 'Cashier',
            'pos_staff_role' => 'cashier',
        ];
    }

    public function test_add_product_to_cart(): void
    {
        $response = $this->withSession($this->posSession)
            ->post('/pos/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['cart' => ['items', 'subtotal', 'total']]);
    }

    public function test_cannot_add_out_of_stock_product(): void
    {
        $this->product->update(['stock_quantity' => 0]);

        $response = $this->withSession($this->posSession)
            ->post('/pos/cart/add', [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        $response->assertStatus(422);
    }

    public function test_update_cart_item_quantity(): void
    {
        // Add item first
        $this->withSession($this->posSession)
            ->post('/pos/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);

        // Get cart to find item ID
        $cartResponse = $this->withSession($this->posSession)->get('/pos/cart/data');
        $cart = $cartResponse->json('cart');
        $itemId = $cart['items'][0]['cart_item_id'];

        // Update quantity
        $response = $this->withSession($this->posSession)
            ->patch('/pos/cart/' . $itemId, ['quantity' => 3]);

        $response->assertStatus(200);
    }

    public function test_remove_item_from_cart(): void
    {
        $this->withSession($this->posSession)
            ->post('/pos/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);

        $cartResponse = $this->withSession($this->posSession)->get('/pos/cart/data');
        $itemId = $cartResponse->json('cart.items.0.cart_item_id');

        $response = $this->withSession($this->posSession)
            ->delete('/pos/cart/' . $itemId);

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('cart.items'));
    }

    public function test_clear_cart(): void
    {
        $this->withSession($this->posSession)
            ->post('/pos/cart/add', ['product_id' => $this->product->id, 'quantity' => 2]);

        $response = $this->withSession($this->posSession)
            ->delete('/pos/cart');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('cart.items'));
    }

    public function test_get_cart_data(): void
    {
        $response = $this->withSession($this->posSession)
            ->get('/pos/cart/data');

        $response->assertStatus(200);
        $response->assertJsonStructure(['cart' => ['items', 'subtotal', 'discount', 'tax', 'total']]);
    }
}
