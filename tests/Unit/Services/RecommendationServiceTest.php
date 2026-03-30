<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecommendationService $service;
    private User $user;
    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RecommendationService();

        $this->user = User::factory()->create(['role' => 'customer']);

        $this->category = Category::create([
            'name' => 'Rec Test Category',
            'slug' => 'rec-test-category',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Rec Main Product',
            'slug' => 'rec-main-product',
            'sku' => 'RMP-001',
            'price' => 499,
            'mrp' => 699,
            'cost_price' => 200,
            'stock_quantity' => 50,
            'category_id' => $this->category->id,
            'status' => 'approved',
            'is_active' => true,
            'sales_count' => 100,
            'rating' => 4.5,
        ]);

        // Create additional products in same category
        for ($i = 2; $i <= 6; $i++) {
            Product::create([
                'name' => "Rec Product {$i}",
                'slug' => "rec-product-{$i}",
                'sku' => "RMP-00{$i}",
                'price' => 499 + ($i * 50),
                'mrp' => 699 + ($i * 50),
                'cost_price' => 200,
                'stock_quantity' => 50,
                'category_id' => $this->category->id,
                'status' => 'approved',
                'is_active' => true,
                'sales_count' => 100 - ($i * 10),
                'rating' => 4.0,
            ]);
        }
    }

    public function test_recently_viewed_returns_collection(): void
    {
        // Create product view records
        ProductView::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        $results = $this->service->recentlyViewed($this->user->id);

        $this->assertCount(1, $results);
        $this->assertEquals($this->product->id, $results->first()->id);
    }

    public function test_recently_viewed_returns_empty_for_no_user(): void
    {
        $results = $this->service->recentlyViewed(null);

        $this->assertCount(0, $results);
    }

    public function test_popular_products_returns_collection(): void
    {
        $results = $this->service->popularProducts(5);

        $this->assertGreaterThan(0, $results->count());
        $this->assertLessThanOrEqual(5, $results->count());
    }

    public function test_popular_products_ordered_by_sales_count(): void
    {
        $results = $this->service->popularProducts(3);

        // First product should have highest sales_count
        if ($results->count() >= 2) {
            $this->assertGreaterThanOrEqual(
                $results[1]->sales_count,
                $results[0]->sales_count
            );
        }
    }

    public function test_similar_products_excludes_source_product(): void
    {
        $results = $this->service->similarProducts($this->product->id, 5);

        $ids = $results->pluck('id')->toArray();
        $this->assertNotContains($this->product->id, $ids);
    }

    public function test_similar_products_returns_same_category(): void
    {
        $results = $this->service->similarProducts($this->product->id, 5);

        foreach ($results as $product) {
            $this->assertEquals($this->category->id, $product->category_id);
        }
    }

    public function test_similar_products_returns_empty_for_invalid_id(): void
    {
        $results = $this->service->similarProducts(999999);

        $this->assertCount(0, $results);
    }

    public function test_frequently_bought_together_returns_collection(): void
    {
        // Create orders with co-purchased products
        $address = UserAddress::create([
            'user_id' => $this->user->id,
            'label' => 'Home',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '9876543210',
            'address_line_1' => '123 Test',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country' => 'IN',
            'is_default' => true,
        ]);

        $otherProduct = Product::where('id', '!=', $this->product->id)->first();

        $order = Order::create([
            'user_id' => $this->user->id,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 1000,
            'paid_amount' => 1000,
            'source' => 'web',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name' => 'Rec Main Product',
            'sku' => 'RMP-001',
            'price' => 499,
            'mrp' => 699,
            'quantity' => 1,
            'tax' => 0,
            'discount' => 0,
            'total' => 499,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $otherProduct->id,
            'product_name' => $otherProduct->name,
            'sku' => $otherProduct->sku,
            'price' => $otherProduct->price,
            'mrp' => $otherProduct->mrp,
            'quantity' => 1,
            'tax' => 0,
            'discount' => 0,
            'total' => $otherProduct->price,
        ]);

        $results = $this->service->frequentlyBoughtTogether($this->product->id);

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    public function test_personalized_returns_collection(): void
    {
        ProductView::create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        $results = $this->service->personalizedForUser($this->user->id);

        $this->assertGreaterThan(0, $results->count());
    }
}
