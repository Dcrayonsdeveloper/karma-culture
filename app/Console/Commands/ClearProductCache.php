<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class ClearProductCache extends Command
{
    protected $signature = 'cache:clear-products {--product= : Clear cache for a specific product ID}';
    protected $description = 'Clear product-related caches';

    public function handle(CacheService $cacheService): int
    {
        $productId = $this->option('product');

        if ($productId) {
            $cacheService->invalidateProduct((int) $productId);
            $this->info("Cache cleared for product #{$productId}.");
        } else {
            $cacheService->invalidateAll();
            $this->info('All product caches cleared.');
        }

        return Command::SUCCESS;
    }
}
