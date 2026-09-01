<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class FeatureHighlightDemoSeeder extends Seeder
{
    /**
     * Populate feature_highlights (Task 12/#7) for a handful of showcase products
     * using their own images, so the Amazon/Flipkart-style sections are visible.
     * Only fills products that don't already have curated highlights.
     */
    public function run(): void
    {
        $copy = [
            ['heading' => 'Crafted for Everyday Luxury', 'caption' => 'Premium fabrics and precise tailoring designed to move with you - from morning meetings to evening plans.'],
            ['heading' => 'Details That Matter', 'caption' => 'Reinforced seams, a breathable weave and a refined finish that holds its shape wash after wash.'],
            ['heading' => 'Made Responsibly', 'caption' => 'Ethically sourced and made in India with genuine care for people and the planet.'],
        ];

        // All active products that have images (skips any with curated highlights).
        $products = Product::where('is_active', true)
            ->whereHas('images')
            ->with('images')
            ->get();

        foreach ($products as $product) {
            if (! empty($product->feature_highlights)) {
                continue; // never overwrite curated content
            }

            $images = $product->images->pluck('url')->filter()->take(3)->values();
            if ($images->isEmpty()) {
                continue;
            }

            $blocks = [];
            foreach ($images as $i => $url) {
                $blocks[] = [
                    'image' => $url,
                    'heading' => $copy[$i]['heading'] ?? $copy[0]['heading'],
                    'caption' => $copy[$i]['caption'] ?? $copy[0]['caption'],
                ];
            }

            $product->update(['feature_highlights' => $blocks]);
        }
    }
}
