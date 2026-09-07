<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Sales Report's CSV used to be the same GROUP BY DATE the chart is drawn
 * from - a date, an order count and a day's takings. That has nowhere to name a
 * product or a customer, because by the time the rows are grouped both have
 * been summed away, so the shop could not tell from the export who had bought
 * what or which number to ring about it. The export is order lines now.
 */
class SalesExportTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Product $tee;
    private Product $cap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'admin']);
        Admin::create([
            'user_id' => $this->adminUser->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Sales Export Test',
            'slug' => 'sales-export-test',
            'is_active' => true,
        ]);

        $this->tee = Product::create([
            'name' => 'Export Tee',
            'slug' => 'export-tee',
            'sku' => 'EXP-TEE',
            'category_id' => $category->id,
            'price' => 600,
            'mrp' => 900,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);

        $this->cap = Product::create([
            'name' => 'Export Cap',
            'slug' => 'export-cap',
            'sku' => 'EXP-CAP',
            'category_id' => $category->id,
            'price' => 400,
            'mrp' => 500,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, array{product: Product, quantity?: int, size?: string, colour?: string}>  $lines
     */
    private function placeOrder(array $attributes, array $lines): Order
    {
        $total = collect($lines)->sum(fn ($line) => $line['product']->price * ($line['quantity'] ?? 1));

        $order = Order::create(array_merge([
            'order_number' => 'EXP-' . uniqid(),
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'subtotal' => $total,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => $total,
            'source' => 'web',
            'metadata' => ['payment_method' => 'cod'],
        ], $attributes));

        foreach ($lines as $line) {
            $product = $line['product'];
            $quantity = $line['quantity'] ?? 1;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'size' => $line['size'] ?? null,
                'colour' => $line['colour'] ?? null,
                'price' => $product->price,
                'mrp' => $product->mrp,
                'quantity' => $quantity,
                'total' => $product->price * $quantity,
            ]);
        }

        return $order;
    }

    /** @return array<int, array<int, string>> */
    private function exportRows(): array
    {
        $response = $this->actingAs($this->adminUser, 'admin')->get('/admin/reports/export/sales');
        $response->assertStatus(200);

        $csv = $response->streamedContent();
        $rows = [];

        foreach (explode("\n", trim($csv)) as $line) {
            $line = trim($line, "\r");
            if ($line !== '') {
                $rows[] = str_getcsv($line);
            }
        }

        return $rows;
    }

    public function test_export_names_the_product_the_customer_and_the_phone(): void
    {
        $customer = User::factory()->create([
            'first_name' => 'Meera',
            'last_name' => 'Nair',
            'phone' => '9876500011',
        ]);

        $this->placeOrder([
            'user_id' => $customer->id,
            'shipping_address_snapshot' => [
                'name' => 'Meera Nair',
                'phone' => '9876543210',
                'city' => 'Kochi',
            ],
        ], [['product' => $this->tee, 'quantity' => 2, 'size' => 'M', 'colour' => 'Indigo']]);

        $rows = $this->exportRows();
        $header = $rows[0];
        $row = $rows[1];

        $this->assertSame('Meera Nair', $row[array_search('Customer', $header, true)]);
        // The number given at checkout, not the one on the account - the
        // customer can give a different one per delivery.
        $this->assertSame('9876543210', $row[array_search('Phone', $header, true)]);
        $this->assertSame('Export Tee', $row[array_search('Product', $header, true)]);
        $this->assertSame('EXP-TEE', $row[array_search('SKU', $header, true)]);
        $this->assertSame('M / Indigo', $row[array_search('Variant', $header, true)]);
        $this->assertSame('2', $row[array_search('Quantity', $header, true)]);
    }

    public function test_guest_orders_are_named_from_the_address_snapshot(): void
    {
        $this->placeOrder([
            'user_id' => null,
            'shipping_address_snapshot' => ['name' => 'Ravi Kumar', 'city' => 'Pune'],
            'metadata' => ['payment_method' => 'cod', 'guest_phone' => '9000011122'],
        ], [['product' => $this->cap]]);

        $rows = $this->exportRows();
        $header = $rows[0];
        $row = $rows[1];

        $this->assertSame('Ravi Kumar', $row[array_search('Customer', $header, true)]);
        $this->assertSame('9000011122', $row[array_search('Phone', $header, true)]);
    }

    public function test_a_multi_line_order_prints_its_total_once(): void
    {
        $this->placeOrder([
            'user_id' => null,
            'shipping_address_snapshot' => ['name' => 'Asha Rao', 'phone' => '9333322211'],
        ], [
            ['product' => $this->tee],
            ['product' => $this->cap],
        ]);

        $rows = $this->exportRows();
        $header = $rows[0];
        $totalColumn = array_search('Order Total (once per order)', $header, true);
        $customerColumn = array_search('Customer', $header, true);

        $this->assertCount(3, $rows, 'A two-line order is two rows plus the header.');

        // Repeating the order total onto every line would make the column add up
        // to more than the revenue the report shows above it.
        $this->assertEquals(1000, $rows[1][$totalColumn]);
        $this->assertSame('', $rows[2][$totalColumn]);

        // The customer, though, repeats - the column has to stay filterable.
        $this->assertSame('Asha Rao', $rows[1][$customerColumn]);
        $this->assertSame('Asha Rao', $rows[2][$customerColumn]);
    }

    public function test_the_order_total_column_sums_to_the_reported_revenue(): void
    {
        $this->placeOrder(['user_id' => null, 'shipping_address_snapshot' => ['name' => 'A']], [
            ['product' => $this->tee],
            ['product' => $this->cap],
        ]);
        $this->placeOrder(['user_id' => null, 'shipping_address_snapshot' => ['name' => 'B']], [
            ['product' => $this->cap, 'quantity' => 3],
        ]);
        // Not a sale, and so not in either figure.
        $this->placeOrder(['status' => 'cancelled', 'user_id' => null], [['product' => $this->tee]]);

        $rows = $this->exportRows();
        $totalColumn = array_search('Order Total (once per order)', $rows[0], true);
        $exported = collect(array_slice($rows, 1))->sum(fn ($row) => (float) $row[$totalColumn]);

        $reported = $this->actingAs($this->adminUser, 'admin')
            ->get('/admin/reports/sales')
            ->viewData('stats')['total_revenue'];

        $this->assertEquals((float) $reported, $exported);
        $this->assertEquals(2200, $exported);
    }

    public function test_cancelled_orders_stay_out_of_the_export(): void
    {
        $this->placeOrder(['status' => 'cancelled', 'user_id' => null], [['product' => $this->tee]]);

        $this->assertCount(1, $this->exportRows(), 'Only the header should be written.');
    }
}
