<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The review form used to accept any number of files of any size and only
 * complain after the whole upload had been sent. These cover both halves of
 * the fix: the browser-side guards, and the server rules behind them.
 */
class ReviewUploadLimitsTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // The route is throttled to 3 posts an hour and the array cache backing
        // the limiter outlives each test, so reset it between cases.
        Cache::flush();

        $category = Category::create([
            'name' => 'Girls Wear',
            'slug' => 'girls-wear',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'name' => 'Kids Frock',
            'slug' => 'kids-frock',
            'sku' => 'KF-001',
            'price' => 799,
            'mrp' => 999,
            'cost_price' => 300,
            'stock_quantity' => 25,
            'category_id' => $category->id,
            'status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function test_upload_inputs_are_wired_to_the_client_side_guard(): void
    {
        $response = $this->get('/product/'.$this->product->slug);

        $response->assertStatus(200);
        $response->assertSee('kkReviewForm(', false);
        $response->assertSee('@change="pick(\'images\', $event)"', false);
        $response->assertSee('@change="pick(\'videos\', $event)"', false);
        // Submit stays disabled while the selection exceeds what PHP will accept.
        $response->assertSee('rating < 1 || overBudget', false);
    }

    public function test_advertised_size_limit_never_exceeds_the_php_upload_cap(): void
    {
        $response = $this->get('/product/'.$this->product->slug);

        $uploadCap = $this->iniBytes(ini_get('upload_max_filesize'));
        $imageCap = $uploadCap > 0 ? min(5 * 1024 * 1024, $uploadCap) : 5 * 1024 * 1024;

        // The cap handed to the browser is the honest one, so the hint text and
        // the guard can never promise more than the server can take.
        $response->assertSee("bytes: {$imageCap}", false);
    }

    public function test_more_than_five_images_are_rejected(): void
    {
        Storage::fake('public');

        $response = $this->post(route('product.guest-review', $this->product), [
            'guest_name' => 'Test Shopper',
            'guest_email' => 'shopper@example.com',
            'rating' => 5,
            'content' => 'This is a genuinely long enough review body to pass validation.',
            'images' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
                UploadedFile::fake()->image('three.jpg'),
                UploadedFile::fake()->image('four.jpg'),
                UploadedFile::fake()->image('five.jpg'),
                UploadedFile::fake()->image('six.jpg'),
            ],
        ]);

        $response->assertSessionHasErrors('images');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_oversized_image_is_rejected(): void
    {
        Storage::fake('public');

        $response = $this->post(route('product.guest-review', $this->product), [
            'guest_name' => 'Test Shopper',
            'guest_email' => 'shopper@example.com',
            'rating' => 5,
            'content' => 'This is a genuinely long enough review body to pass validation.',
            'images' => [UploadedFile::fake()->image('huge.jpg')->size(6144)], // 6 MB
        ]);

        $response->assertSessionHasErrors('images.0');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_within_limits_upload_is_stored(): void
    {
        Storage::fake('public');

        $response = $this->post(route('product.guest-review', $this->product), [
            'guest_name' => 'Test Shopper',
            'guest_email' => 'shopper@example.com',
            'rating' => 5,
            'content' => 'This is a genuinely long enough review body to pass validation.',
            'images' => [UploadedFile::fake()->image('fine.jpg')->size(512)],
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('reviews', 1);
        $this->assertSame(1, Review::first()->images()->count());
    }

    private function iniBytes(?string $value): int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }
        $num = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $num * 1024 ** 3,
            'm' => $num * 1024 ** 2,
            'k' => $num * 1024,
            default => $num,
        };
    }
}
