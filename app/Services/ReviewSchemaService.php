<?php

namespace App\Services;

use App\Models\Product;

class ReviewSchemaService
{
    public function getProductSchema(Product $product): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => strip_tags($product->short_description ?? $product->description ?? ''),
            'sku' => $product->sku,
            'url' => route('product.show', $product),
        ];

        // Images
        $images = $product->images->pluck('url')->map(fn ($url) => url($url))->toArray();
        if (!empty($images)) {
            $schema['image'] = $images;
        }

        // Brand
        if ($product->brand) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ];
        }

        // Offers
        $schema['offers'] = [
            '@type' => 'Offer',
            'url' => route('product.show', $product),
            'priceCurrency' => 'INR',
            'price' => number_format((float) $product->price, 2, '.', ''),
            'availability' => $product->isInStock()
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'seller' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'ForeverKids'),
            ],
        ];

        if ($product->mrp > $product->price) {
            $schema['offers']['priceValidUntil'] = now()->addMonths(3)->format('Y-m-d');
        }

        // Aggregate Rating
        $approvedReviews = $product->approvedReviews;
        if ($approvedReviews->count() > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format($approvedReviews->avg('rating'), 1),
                'reviewCount' => (string) $approvedReviews->count(),
                'bestRating' => '5',
                'worstRating' => '1',
            ];

            // Include up to 10 most recent reviews in structured data
            $schema['review'] = $approvedReviews->sortByDesc('created_at')->take(10)->map(function ($review) {
                $reviewSchema = [
                    '@type' => 'Review',
                    'datePublished' => $review->created_at->format('Y-m-d'),
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => (string) $review->rating,
                        'bestRating' => '5',
                        'worstRating' => '1',
                    ],
                    'reviewBody' => $review->content,
                ];

                // Author
                $authorName = $review->user
                    ? $review->user->first_name . ' ' . strtoupper(substr($review->user->last_name, 0, 1)) . '.'
                    : ($review->guest_name ?? 'Anonymous');

                $reviewSchema['author'] = [
                    '@type' => 'Person',
                    'name' => $authorName,
                ];

                if ($review->title) {
                    $reviewSchema['name'] = $review->title;
                }

                return $reviewSchema;
            })->values()->toArray();
        }

        return $schema;
    }

    public function getFaqSchema(Product $product): ?array
    {
        $questions = $product->questions->filter(fn ($q) => $q->is_answered && $q->answers->isNotEmpty());

        if ($questions->isEmpty()) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions->map(function ($question) {
                return [
                    '@type' => 'Question',
                    'name' => $question->question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $question->answers->first()->answer,
                    ],
                ];
            })->values()->toArray(),
        ];
    }
}
