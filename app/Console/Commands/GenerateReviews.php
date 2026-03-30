<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Models\Review;
use App\Models\Setting;
use App\Services\ReviewGeneratorService;
use Illuminate\Console\Command;

class GenerateReviews extends Command
{
    protected $signature = 'reviews:generate
                            {--dry-run : Show what would be generated without creating reviews}
                            {--count= : Override the number of reviews to generate}';

    protected $description = 'Generate reviews from delivered orders (sales-driven drip feed)';

    public function handle(ReviewGeneratorService $generator): int
    {
        if (!Setting::get('review_gen_enabled', true)) {
            $this->info('Review generation is disabled.');
            return 0;
        }

        $minDays = Setting::get('review_gen_delay_min_days', 2);
        $maxDays = Setting::get('review_gen_delay_max_days', 14);
        $conversionMin = Setting::get('review_gen_conversion_rate_min', 30);
        $conversionMax = Setting::get('review_gen_conversion_rate_max', 50);
        $maxPerProduct = Setting::get('review_gen_max_per_product_day', 2);

        // Find eligible order items: delivered, within delay window, no review yet
        $alreadyReviewedItemIds = Review::where('is_generated', true)
            ->whereNotNull('generated_from_order_item_id')
            ->pluck('generated_from_order_item_id');

        $eligibleItems = OrderItem::query()
            ->whereHas('order', function ($q) use ($minDays, $maxDays) {
                $q->where('status', 'delivered')
                  ->whereBetween('delivered_at', [
                      now()->subDays($maxDays),
                      now()->subDays($minDays),
                  ]);
            })
            ->whereNotIn('id', $alreadyReviewedItemIds)
            ->with(['order.user', 'product.category', 'product.brand'])
            ->get()
            ->filter(function ($item) {
                // Exclude if user already wrote a real review for this product
                if (!$item->order || !$item->order->user) {
                    return false;
                }
                return !Review::where('product_id', $item->product_id)
                    ->where('user_id', $item->order->user_id)
                    ->where('is_generated', false)
                    ->exists();
            });

        if ($eligibleItems->isEmpty()) {
            $this->info('No eligible order items found for review generation.');
            return 0;
        }

        $this->info("Found {$eligibleItems->count()} eligible order items.");

        // Apply conversion rate: randomly select a percentage
        $conversionRate = rand($conversionMin, $conversionMax) / 100;
        $targetCount = $this->option('count')
            ? (int) $this->option('count')
            : max(1, (int) round($eligibleItems->count() * $conversionRate));

        $shuffled = $eligibleItems->shuffle()->take($targetCount);

        $this->info("Will generate {$shuffled->count()} reviews (conversion rate: " . round($conversionRate * 100) . "%).");

        if ($this->option('dry-run')) {
            foreach ($shuffled as $item) {
                $this->line("  Would review: {$item->product_name} by {$item->order->user->full_name}");
            }
            return 0;
        }

        // Track reviews per product today to enforce cap
        $reviewsPerProduct = [];
        $generated = 0;

        foreach ($shuffled as $item) {
            $productId = $item->product_id;

            // Enforce max per product per day
            $todayCount = ($reviewsPerProduct[$productId] ?? 0)
                + Review::where('product_id', $productId)
                    ->where('is_generated', true)
                    ->whereDate('created_at', today())
                    ->count();

            if ($todayCount >= $maxPerProduct) {
                continue;
            }

            // Double-check no duplicate
            $exists = Review::where('generated_from_order_item_id', $item->id)->exists();
            if ($exists) {
                continue;
            }

            $review = $generator->generateForOrderItem($item);
            $reviewsPerProduct[$productId] = ($reviewsPerProduct[$productId] ?? 0) + 1;
            $generated++;

            $this->line("  Generated: ★{$review->rating} for \"{$item->product_name}\" by {$item->order->user->first_name}");
        }

        $this->info("Successfully generated {$generated} reviews.");

        return 0;
    }
}
