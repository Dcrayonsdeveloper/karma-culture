<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\CacheService;
use App\Services\RecommendationService;
use Illuminate\Console\Command;

class WarmCache extends Command
{
    protected $signature = 'cache:warm {--products : Warm product caches} {--categories : Warm category caches} {--all : Warm all caches}';
    protected $description = 'Warm application caches for better performance';

    public function handle(CacheService $cacheService, RecommendationService $recommendationService): int
    {
        $warmAll = $this->option('all');

        if ($warmAll || $this->option('categories')) {
            $this->info('Warming category tree cache...');
            $cacheService->getCategoryTree(fn () => Category::whereNull('parent_id')
                ->where('is_active', true)
                ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('position')])
                ->orderBy('position')
                ->get()
            );
            $this->info('Category tree cached.');
        }

        if ($warmAll || $this->option('products')) {
            $this->info('Warming popular products cache...');
            $recommendationService->popularProducts();
            $this->info('Popular products cached.');

            $topProducts = Product::where('is_active', true)
                ->whereNull('deleted_at')
                ->orderByDesc('sales_count')
                ->limit(50)
                ->pluck('id');

            $this->info("Warming similar products for top {$topProducts->count()} products...");
            $bar = $this->output->createProgressBar($topProducts->count());
            foreach ($topProducts as $productId) {
                $recommendationService->similarProducts($productId);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        if ($warmAll) {
            $this->info('Warming homepage sections cache...');
            $cacheService->getHomepageSections(fn () => 'warmed');
            $this->info('Homepage cached.');
        }

        $this->info('Cache warming complete.');

        return Command::SUCCESS;
    }
}
