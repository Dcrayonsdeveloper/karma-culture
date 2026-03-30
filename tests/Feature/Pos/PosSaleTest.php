<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\PosRegister;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\Staff;
use App\Models\StaffShift;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSaleTest extends TestCase
{
    use RefreshDatabase;

    private array $posSession;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $store = Store::create(['name' => 'Test Store', 'code' => 'TS-02', 'is_active' => true]);
        $register = PosRegister::create(['store_id' => $store->id, 'name' => 'C2', 'device_id' => 'POS-T2', 'status' => 'active']);
        $user = User::factory()->create(['first_name' => 'Cashier2', 'role' => 'admin']);
        $staff = Staff::create(['user_id' => $user->id, 'employee_id' => 'E-02', 'role' => 'cashier', 'store_id' => $store->id, 'pin' => bcrypt('1234'), 'is_active' => true]);
        $shift = StaffShift::create(['staff_id' => $staff->id, 'store_id' => $store->id, 'register_id' => $register->id, 'shift_start' => now(), 'opening_cash' => 1000, 'status' => 'open']);

        $category = Category::create(['name' => 'Boys Wear', 'slug' => 'boys-wear', 'is_active' => true]);

        $this->product = Product::create([
            'name' => 'Boys Shirt',
            'slug' => 'boys-shirt',
            'sku' => 'BSH-001',
            'price' => 599,
            'mrp' => 799,
            'cost_price' => 250,
            'stock_quantity' => 30,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        $this->posSession = [
            'pos_staff_id' => $staff->id,
            'pos_store_id' => $store->id,
            'pos_device_id' => 'POS-T2',
            'pos_register_id' => $register->id,
            'pos_shift_id' => $shift->id,
        ];
    }

    public function test_complete_cash_sale(): void
    {
        // Add to cart
        $this->withSession($this->posSession)
            ->post('/pos/cart/add', ['product_id' => $this->product->id, 'quantity' => 2]);

        // Complete sale
        $response = $this->withSession($this->posSession)
            ->post('/pos/sale/complete', [
                'payment_method' => 'cash',
                'paid_amount' => 1200,
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);
        $this->assertNotNull($response->json('sale_number'));

        // Verify stock was deducted
        $this->product->refresh();
        $this->assertEquals(28, $this->product->stock_quantity);
    }

    public function test_complete_card_sale(): void
    {
        $this->withSession($this->posSession)
            ->post('/pos/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);

        $response = $this->withSession($this->posSession)
            ->post('/pos/sale/complete', [
                'payment_method' => 'card',
                'paid_amount' => 599,
                'payment_ref' => 'AUTH-12345',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);
    }

    public function test_sale_fails_with_empty_cart(): void
    {
        $response = $this->withSession($this->posSession)
            ->post('/pos/sale/complete', [
                'payment_method' => 'cash',
                'paid_amount' => 1000,
            ]);

        $response->assertStatus(422);
    }

    public function test_cash_sale_fails_with_insufficient_amount(): void
    {
        $this->withSession($this->posSession)
            ->post('/pos/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);

        $response = $this->withSession($this->posSession)
            ->post('/pos/sale/complete', [
                'payment_method' => 'cash',
                'paid_amount' => 100,
            ]);

        $response->assertStatus(422);
    }

    public function test_sale_number_auto_generated(): void
    {
        $this->withSession($this->posSession)
            ->post('/pos/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);

        $this->withSession($this->posSession)
            ->post('/pos/sale/complete', ['payment_method' => 'cash', 'paid_amount' => 599]);

        $sale = PosSale::latest()->first();
        $this->assertNotNull($sale);
        $this->assertStringStartsWith('POS-', $sale->sale_number);
    }

    public function test_receipt_loads_for_valid_sale(): void
    {
        $this->withSession($this->posSession)
            ->post('/pos/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);

        $saleResponse = $this->withSession($this->posSession)
            ->post('/pos/sale/complete', ['payment_method' => 'cash', 'paid_amount' => 599]);

        $sale = PosSale::latest()->first();

        $response = $this->withSession($this->posSession)
            ->get('/pos/sale/' . $sale->id . '/receipt');

        $response->assertStatus(200);
    }
}
