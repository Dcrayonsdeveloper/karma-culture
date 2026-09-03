<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The sitemap is the one place that hands Google a URL nobody clicked to reach,
 * so it is the one place a wrong URL goes unnoticed. Three were wrong.
 *
 * Products were submitted as /products/{slug} while every link on the site, the
 * canonical tag and every JSON payload use /product/{slug}. Both answered 200,
 * so Google indexed a product twice and split it between the two.
 *
 * Categories and brands were submitted as /products?category={slug} and
 * /products?brand={slug}. /products is the all-products listing, so those did
 * reach a page - but for as long as the listing lived at /shop and /products was
 * a redirect to it, the query string was dropped, because a Laravel redirect
 * route forwards path parameters only. Every category and every brand in the
 * sitemap therefore landed on one unfiltered listing, and the real
 * /category/{slug} and /brands/{slug} pages were never submitted at all.
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalogue(): array
    {
        $category = Category::create([
            'name' => 'Shirts',
            'slug' => 'shirts',
            'is_active' => true,
        ]);

        $brand = Brand::create([
            'name' => 'Karmaa',
            'slug' => 'karmaa',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Poplin Shirt',
            'slug' => 'poplin-shirt',
            'sku' => 'POPLIN',
            'price' => 500,
            'mrp' => 900,
            'cost_price' => 200,
            'stock_quantity' => 10,
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'approved',
            'is_active' => true,
        ]);

        return [$category, $brand, $product];
    }

    public function test_the_product_sitemap_submits_the_url_the_site_actually_links_to(): void
    {
        $this->seedCatalogue();

        $xml = $this->get('/sitemap-products.xml')->assertOk()->getContent();

        $this->assertStringContainsString(url('/product/poplin-shirt'), $xml);

        // The plural path is not a page at all now. Submitting a URL that 404s
        // is exactly what the sitemap is meant not to do.
        $this->assertStringNotContainsString(url('/products/poplin-shirt'), $xml);
    }

    public function test_the_category_sitemap_submits_real_pages_not_a_query_that_gets_dropped(): void
    {
        $this->seedCatalogue();

        $xml = $this->get('/sitemap-categories.xml')->assertOk()->getContent();

        $this->assertStringContainsString(url('/category/shirts'), $xml);
        $this->assertStringContainsString(url('/brands/karmaa'), $xml);

        // The old form. Anything still carrying ?category= or ?brand= submits a
        // filtered listing in place of the real category and brand pages.
        $this->assertStringNotContainsString('category=shirts', $xml);
        $this->assertStringNotContainsString('brand=karmaa', $xml);
    }

    public function test_the_pages_sitemap_lists_the_shop(): void
    {
        $xml = $this->get('/sitemap-pages.xml')->assertOk()->getContent();

        // /products is the all-products page. It was the one shopping page the
        // sitemap never mentioned.
        $this->assertStringContainsString(url('/products'), $xml);
        $this->assertStringContainsString(url('/brands'), $xml);
    }

    public function test_the_plural_product_and_category_urls_are_not_pages(): void
    {
        // The plural paths briefly 301'd to the singular ones. They 404 now:
        // /product/{slug} and /category/{slug} are the only paths this site
        // serves, they are what the sitemap and the canonical tag advertise, and
        // a wrong address is answered as a wrong address rather than forwarded
        // to a page nobody asked for.
        $this->seedCatalogue();

        $this->get('/products/poplin-shirt')->assertNotFound();
        $this->get('/categories/shirts')->assertNotFound();
    }

    public function test_the_canonical_product_and_category_pages_still_answer(): void
    {
        $this->seedCatalogue();

        $this->get('/product/poplin-shirt')->assertOk();
        $this->get('/category/shirts')->assertOk();
    }
}
