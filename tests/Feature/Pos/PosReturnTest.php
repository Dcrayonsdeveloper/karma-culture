<?php

namespace Tests\Feature\Pos;

use App\Models\Category;
use App\Models\PosRegister;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\Product;
use App\Models\Staff;
use App\Models\StaffShift;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosReturnTest extends TestCase
{
    use RefreshDatabase;

    private array $posSession;
    private PosSale $sale;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $store = Store::create(['name' => 'Test Store', 'code' => 'TS-03', 'is_active' => true]);
        $register = PosRegister::create(['store_id' => $store->id, 'name' => 'C3', 'device_id' => 'POS-T3', 'status' => 'active']);
        $user = User::factory()->create(['first_name' => 'Staff3', 'role' => 'admin']);
        $staff = Staff::create(['user_id' => $user->id, 'employee_id' => 'E-03', 'role' => 'manager', 'store_id' => $store->id, 'pin' => bcrypt('1234'), 'is_active' => true]);
        $shift = StaffShift::create(['staff_id' => $staff->id, 'store_id' => $store->id, 'register_id' => $register->id, 'shift_start' => now(), 'opening_cash' => 1000, 'status' => 'open']);

        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat', 'is_active' => true]);

        $this->product = Product::create([
            'name' => 'Return Product',
            'slug' => 'return-product',
            'sku' => 'RET-001',
            'price' => 400,
            'mrp' => 500,
            'cost_price' => 150,
            'stock_quantity' => 20,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        // Create a completed sale
        $this->sale = PosSale::create([
            'store_id' => $store->id,
            'register_id' => $register->id,
            'staff_id' => $staff->id,
            'subtotal' => 800,
            'discount' => 0,
            'tax' => 0,
            'total' => 800,
            'paid_amount' => 800,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        PosSaleItem::create([
            'pos_sale_id' => $this->sale->id,
            'product_id' => $this->product->id,
            'product_name' => 'Return Product',
            'quantity' => 2,
            'price' => 400,
            'discount' => 0,
            'tax' => 0,
            'total' => 800,
        ]);

        $this->posSession = [
            'pos_staff_id' => $staff->id,
            'pos_store_id' => $store->id,
            'pos_device_id' => 'POS-T3',
            'pos_register_id' => $register->id,
            'pos_shift_id' => $shift->id,
        ];
    }

    public function test_find_sale_by_number(): void
    {
        $response = $this->withSession($this->posSession)
            ->get('/pos/returns/find?q=' . urlencode($this->sale->sale_number));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'sales');
    }

    public function test_find_sale_requires_minimum_chars(): void
    {
        $response = $this->withSession($this->posSession)
            ->get('/pos/returns/find?q=AB');

        $response->assertJsonCount(0, 'sales');
    }

    public function test_process_return(): void
    {
        $saleItem = $this->sale->items->first();

        $response = $this->withSession($this->posSession)
            ->post('/pos/returns', [
                'pos_sale_id' => $this->sale->id,
                'items' => [
                    [
                        'sale_item_id' => $saleItem->id,
                        'quantity' => 1,
                        'reason' => 'Wrong size',
                        'condition' => 'unused_with_tags',
                    ],
                ],
                'refund_method' => 'cash',
                'reason' => 'Customer return',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['success' => true]);

        // Stock should be restored
        $this->product->refresh();
        $this->assertEquals(21, $this->product->stock_quantity);
    }

    public function test_return_with_credit_note(): void
    {
        // Create a customer for the sale
        $customer = User::factory()->create(['role' => 'customer']);
        $this->sale->update(['customer_id' => $customer->id]);

        $saleItem = $this->sale->items->first();

        $response = $this->withSession($this->posSession)
            ->post('/pos/returns', [
                'pos_sale_id' => $this->sale->id,
                'items' => [
                    [
                        'sale_item_id' => $saleItem->id,
                        'quantity' => 1,
                        'condition' => 'unused_with_tags',
                    ],
                ],
                'refund_method' => 'credit_note',
            ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('credit_note'));
    }
}
