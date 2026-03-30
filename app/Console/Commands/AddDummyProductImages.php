<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AddDummyProductImages extends Command
{
    protected $signature = 'products:add-dummy-images {--force : Overwrite existing images}';
    protected $description = 'Add dummy beauty product images to all products without images';

    private array $beautyImages = [
        'skincare' => [
            'https://images.unsplash.com/photo-1570194065650-d99fb4a38b3a?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1611930022073-b7a4ba5fcccd?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=600&h=600&fit=crop',
        ],
        'makeup' => [
            'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1583241800698-e8ab01830a07?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1631214524020-7e18db9a8f92?w=600&h=600&fit=crop',
        ],
        'lips' => [
            'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1631214524020-7e18db9a8f92?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1583241800698-e8ab01830a07?w=600&h=600&fit=crop',
        ],
        'hair' => [
            'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1585751119414-ef2636f8aede?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=600&h=600&fit=crop',
        ],
        'fragrance' => [
            'https://images.unsplash.com/photo-1541643600914-78b084683601?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1594035910387-fea081e48e23?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=600&h=600&fit=crop',
        ],
        'generic' => [
            'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=600&h=600&fit=crop',
            'https://images.unsplash.com/photo-1526045478516-99145907023c?w=600&h=600&fit=crop',
        ],
    ];

    public function handle(): int
    {
        $force = $this->option('force');

        $query = Product::query();
        if (!$force) {
            $query->whereDoesntHave('images');
        }

        $products = $query->with('category')->get();

        if ($products->isEmpty()) {
            $this->info('All products already have images. Use --force to overwrite.');
            return 0;
        }

        $this->info("Adding dummy images to {$products->count()} products...");
        $bar = $this->output->createProgressBar($products->count());

        foreach ($products as $product) {
            if ($force) {
                $product->images()->delete();
            }

            $images = $this->getImagesForProduct($product);

            // Download and store main image
            $mainUrl = $this->downloadImage($images[0], $product->id, 'main');
            if ($mainUrl) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => $mainUrl,
                    'alt_text' => $product->name,
                    'position' => 0,
                    'is_primary' => true,
                ]);
            }

            // Download and store 2 gallery images
            for ($i = 1; $i <= 2; $i++) {
                $galleryUrl = $this->downloadImage($images[$i] ?? $images[0], $product->id, "gallery-{$i}");
                if ($galleryUrl) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'url' => $galleryUrl,
                        'alt_text' => $product->name . " - Image {$i}",
                        'position' => $i,
                        'is_primary' => false,
                    ]);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done! Dummy images added successfully.');

        return 0;
    }

    private function getImagesForProduct(Product $product): array
    {
        $name = strtolower($product->name);
        $categoryName = strtolower($product->category?->name ?? '');

        if (str_contains($name, 'lip') || str_contains($categoryName, 'lip')) {
            $pool = $this->beautyImages['lips'];
        } elseif (str_contains($name, 'serum') || str_contains($name, 'cream') || str_contains($name, 'moistur') || str_contains($name, 'cleanser') || str_contains($categoryName, 'skin')) {
            $pool = $this->beautyImages['skincare'];
        } elseif (str_contains($name, 'foundation') || str_contains($name, 'powder') || str_contains($name, 'primer') || str_contains($name, 'mascara') || str_contains($name, 'eyeshadow') || str_contains($categoryName, 'face') || str_contains($categoryName, 'eye')) {
            $pool = $this->beautyImages['makeup'];
        } elseif (str_contains($name, 'shampoo') || str_contains($name, 'hair') || str_contains($name, 'keratin') || str_contains($categoryName, 'hair')) {
            $pool = $this->beautyImages['hair'];
        } elseif (str_contains($name, 'perfum') || str_contains($name, 'parfum') || str_contains($name, 'fragrance') || str_contains($name, 'cologne') || str_contains($categoryName, 'fragrance')) {
            $pool = $this->beautyImages['fragrance'];
        } else {
            $pool = $this->beautyImages['generic'];
        }

        shuffle($pool);

        return $pool;
    }

    private function downloadImage(string $url, int $productId, string $suffix): ?string
    {
        try {
            $response = Http::timeout(15)->get($url);

            if ($response->successful()) {
                $filename = "products/dummy-{$productId}-{$suffix}.jpg";
                Storage::disk('public')->put($filename, $response->body());

                return '/storage/' . $filename;
            }
        } catch (\Exception $e) {
            $this->warn("  Failed to download image: {$e->getMessage()}");
        }

        return null;
    }
}
