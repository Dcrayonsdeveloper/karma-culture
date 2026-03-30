# Search Algorithm Specification

## [AI-REF] Amazon-Inspired Search Engine

This document defines the search and discovery system implementation.

---

## Overview

The search system provides:
1. **Full-text search** with typo tolerance
2. **Faceted filtering** with real-time counts
3. **Personalized ranking** based on user behavior
4. **Autocomplete** with suggestions
5. **Search analytics** for optimization

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      Search Request                              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Query Parser                                  │
│  • Tokenization        • Spell correction                       │
│  • Synonym expansion   • Query understanding                    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Meilisearch Engine                            │
│  • Full-text search    • Typo tolerance                         │
│  • Faceting            • Geo search                             │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                  Ranking & Personalization                       │
│  • Relevance scoring   • User preferences                       │
│  • Sales velocity      • Seller reputation                      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Search Results                                │
└─────────────────────────────────────────────────────────────────┘
```

---

## Search Index Configuration

### Meilisearch Settings

```php
// config/scout.php
'meilisearch' => [
    'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
    'key' => env('MEILISEARCH_KEY'),
],

// Index settings
'index-settings' => [
    \App\Models\Product::class => [
        'filterableAttributes' => [
            'category_id',
            'category_ids',  // All ancestor categories
            'brand_id',
            'seller_id',
            'price',
            'rating',
            'in_stock',
            'is_featured',
            'discount_percentage',
            'attributes.color',
            'attributes.size',
            'attributes.material',
            // Dynamic attributes added per category
        ],
        'sortableAttributes' => [
            'price',
            'rating',
            'review_count',
            'sales_count',
            'created_at',
            'popularity_score',
            'discount_percentage',
        ],
        'searchableAttributes' => [
            'name',              // Highest priority
            'brand_name',
            'category_name',
            'short_description',
            'tags',
            'sku',
            'description',       // Lowest priority
        ],
        'rankingRules' => [
            'words',
            'typo',
            'proximity',
            'attribute',
            'sort',
            'exactness',
            'popularity_score:desc',
        ],
        'typoTolerance' => [
            'enabled' => true,
            'minWordSizeForTypos' => [
                'oneTypo' => 4,
                'twoTypos' => 8,
            ],
        ],
        'synonyms' => [
            'phone' => ['mobile', 'smartphone', 'cellphone'],
            'laptop' => ['notebook', 'computer'],
            'tv' => ['television', 'smart tv'],
            // Load from database
        ],
        'stopWords' => ['the', 'a', 'an', 'and', 'or', 'but'],
    ],
],
```

### Product Index Schema

```php
// app/Models/Product.php
public function toSearchableArray(): array
{
    return [
        'id' => $this->id,
        'uuid' => $this->uuid,
        'name' => $this->name,
        'slug' => $this->slug,
        'short_description' => $this->short_description,
        'description' => strip_tags($this->description),
        'sku' => $this->sku,
        'barcode' => $this->barcode,

        // Pricing
        'price' => (float) $this->price,
        'mrp' => (float) $this->mrp,
        'discount_percentage' => $this->discount_percentage,

        // Categories (hierarchical)
        'category_id' => $this->category_id,
        'category_ids' => $this->getCategoryPath(),
        'category_name' => $this->category?->name,
        'category_path' => $this->category?->getFullPath(),

        // Brand
        'brand_id' => $this->brand_id,
        'brand_name' => $this->brand?->name,

        // Seller
        'seller_id' => $this->seller_id,
        'seller_name' => $this->seller?->business_name,
        'seller_rating' => $this->seller?->rating,

        // Ratings & Reviews
        'rating' => (float) $this->rating,
        'review_count' => (int) $this->review_count,

        // Stock
        'in_stock' => $this->stock_quantity > 0,
        'stock_quantity' => $this->stock_quantity,

        // Metrics
        'sales_count' => (int) $this->sales_count,
        'view_count' => (int) $this->view_count,
        'wishlist_count' => (int) $this->wishlist_count,

        // Computed scores
        'popularity_score' => $this->calculatePopularityScore(),
        'relevance_boost' => $this->getRelevanceBoost(),

        // Flags
        'is_featured' => (bool) $this->is_featured,
        'is_new' => $this->created_at->gt(now()->subDays(30)),

        // Attributes (dynamic)
        'attributes' => $this->getIndexableAttributes(),

        // Tags
        'tags' => $this->tags->pluck('name')->toArray(),

        // Images
        'image_url' => $this->primaryImage?->url,
        'thumbnail_url' => $this->getThumbnailUrl(),

        // Timestamps
        'created_at' => $this->created_at->timestamp,
        'updated_at' => $this->updated_at->timestamp,
    ];
}

private function calculatePopularityScore(): float
{
    $salesWeight = 0.4;
    $viewWeight = 0.2;
    $ratingWeight = 0.25;
    $reviewWeight = 0.15;

    $normalizedSales = min($this->sales_count / 1000, 1);
    $normalizedViews = min($this->view_count / 10000, 1);
    $normalizedRating = $this->rating / 5;
    $normalizedReviews = min($this->review_count / 100, 1);

    return (
        ($normalizedSales * $salesWeight) +
        ($normalizedViews * $viewWeight) +
        ($normalizedRating * $ratingWeight) +
        ($normalizedReviews * $reviewWeight)
    );
}

private function getRelevanceBoost(): float
{
    $boost = 1.0;

    // Boost featured products
    if ($this->is_featured) $boost *= 1.2;

    // Boost highly rated sellers
    if ($this->seller?->rating >= 4.5) $boost *= 1.1;

    // Boost products with images
    if ($this->images->count() >= 3) $boost *= 1.05;

    // Boost verified products
    if ($this->status === 'approved') $boost *= 1.1;

    return $boost;
}
```

---

## Search Service Implementation

```php
// app/Services/Search/ProductSearchService.php
namespace App\Services\Search;

class ProductSearchService
{
    public function __construct(
        private MeilisearchClient $client,
        private PersonalizationService $personalization,
        private SearchAnalyticsService $analytics,
        private SynonymService $synonyms,
    ) {}

    public function search(SearchQuery $query, ?User $user = null): SearchResults
    {
        // Start timer for analytics
        $startTime = microtime(true);

        // Parse and enhance query
        $enhancedQuery = $this->enhanceQuery($query);

        // Build search options
        $options = $this->buildSearchOptions($enhancedQuery);

        // Execute search
        $rawResults = $this->client
            ->index('products')
            ->search($enhancedQuery->term, $options);

        // Apply personalization
        if ($user) {
            $rawResults = $this->personalization->rerank(
                $rawResults,
                $user,
                $query
            );
        }

        // Build response
        $results = SearchResults::fromMeilisearch($rawResults);

        // Track analytics
        $this->analytics->track(
            $query,
            $results,
            $user,
            microtime(true) - $startTime
        );

        return $results;
    }

    private function enhanceQuery(SearchQuery $query): SearchQuery
    {
        // Spell correction
        $correctedTerm = $this->spellCorrect($query->term);

        // Synonym expansion
        $expandedTerms = $this->synonyms->expand($correctedTerm);

        return $query->withTerm($expandedTerms);
    }

    private function buildSearchOptions(SearchQuery $query): array
    {
        $options = [
            'limit' => $query->perPage,
            'offset' => ($query->page - 1) * $query->perPage,
            'attributesToRetrieve' => [
                'id', 'uuid', 'name', 'slug', 'price', 'mrp',
                'rating', 'review_count', 'image_url', 'in_stock',
                'brand_name', 'discount_percentage',
            ],
            'attributesToHighlight' => ['name', 'short_description'],
            'facets' => [
                'category_id',
                'brand_id',
                'price',
                'rating',
                'attributes.color',
                'attributes.size',
            ],
        ];

        // Build filters
        $filters = $this->buildFilters($query);
        if (!empty($filters)) {
            $options['filter'] = $filters;
        }

        // Build sort
        if ($query->sort) {
            $options['sort'] = [$this->mapSort($query->sort)];
        }

        return $options;
    }

    private function buildFilters(SearchQuery $query): array
    {
        $filters = ['in_stock = true'];

        if ($query->categoryId) {
            $filters[] = "category_ids = {$query->categoryId}";
        }

        if ($query->brandIds) {
            $brandFilter = implode(' OR ', array_map(
                fn($id) => "brand_id = $id",
                $query->brandIds
            ));
            $filters[] = "($brandFilter)";
        }

        if ($query->minPrice !== null) {
            $filters[] = "price >= {$query->minPrice}";
        }

        if ($query->maxPrice !== null) {
            $filters[] = "price <= {$query->maxPrice}";
        }

        if ($query->minRating !== null) {
            $filters[] = "rating >= {$query->minRating}";
        }

        // Dynamic attribute filters
        foreach ($query->attributes as $key => $values) {
            $attrFilter = implode(' OR ', array_map(
                fn($v) => "attributes.$key = '$v'",
                (array) $values
            ));
            $filters[] = "($attrFilter)";
        }

        return $filters;
    }

    private function mapSort(string $sort): string
    {
        return match($sort) {
            'price_asc' => 'price:asc',
            'price_desc' => 'price:desc',
            'rating' => 'rating:desc',
            'newest' => 'created_at:desc',
            'bestselling' => 'sales_count:desc',
            'discount' => 'discount_percentage:desc',
            default => 'popularity_score:desc',
        };
    }
}
```

---

## Personalization Engine

```php
// app/Services/Search/PersonalizationService.php
namespace App\Services\Search;

class PersonalizationService
{
    public function __construct(
        private UserBehaviorService $behavior,
        private Cache $cache,
    ) {}

    public function rerank(
        array $results,
        User $user,
        SearchQuery $query
    ): array {
        // Get user preferences
        $preferences = $this->getUserPreferences($user);

        // Score each result
        foreach ($results['hits'] as &$hit) {
            $hit['personalized_score'] = $this->calculatePersonalizedScore(
                $hit,
                $preferences,
                $query
            );
        }

        // Sort by personalized score
        usort($results['hits'], fn($a, $b) =>
            $b['personalized_score'] <=> $a['personalized_score']
        );

        return $results;
    }

    private function getUserPreferences(User $user): UserPreferences
    {
        return $this->cache->remember(
            "user_preferences:{$user->id}",
            now()->addHours(1),
            fn() => $this->behavior->analyzeUser($user)
        );
    }

    private function calculatePersonalizedScore(
        array $product,
        UserPreferences $preferences,
        SearchQuery $query
    ): float {
        $score = $product['popularity_score'] ?? 0.5;

        // Boost preferred categories
        if (in_array($product['category_id'], $preferences->topCategories)) {
            $score *= 1.2;
        }

        // Boost preferred brands
        if (in_array($product['brand_id'], $preferences->topBrands)) {
            $score *= 1.15;
        }

        // Boost price range match
        if ($this->inPriceRange($product['price'], $preferences->priceRange)) {
            $score *= 1.1;
        }

        // Boost based on purchase history
        if ($this->hasPurchasedSimilar($product, $preferences)) {
            $score *= 1.25;
        }

        // Penalize recently viewed (show variety)
        if (in_array($product['id'], $preferences->recentlyViewed)) {
            $score *= 0.9;
        }

        return $score;
    }
}
```

---

## Autocomplete Service

```php
// app/Services/Search/AutocompleteService.php
namespace App\Services\Search;

class AutocompleteService
{
    public function suggest(string $query, int $limit = 10): array
    {
        $suggestions = [];

        // Product name suggestions
        $products = $this->searchProducts($query, 5);
        foreach ($products as $product) {
            $suggestions[] = [
                'type' => 'product',
                'text' => $product['name'],
                'slug' => $product['slug'],
                'image' => $product['thumbnail_url'],
                'price' => $product['price'],
            ];
        }

        // Category suggestions
        $categories = $this->searchCategories($query, 3);
        foreach ($categories as $category) {
            $suggestions[] = [
                'type' => 'category',
                'text' => $category['name'],
                'slug' => $category['slug'],
                'count' => $category['product_count'],
            ];
        }

        // Brand suggestions
        $brands = $this->searchBrands($query, 2);
        foreach ($brands as $brand) {
            $suggestions[] = [
                'type' => 'brand',
                'text' => $brand['name'],
                'slug' => $brand['slug'],
            ];
        }

        // Popular searches
        $popular = $this->getPopularSearches($query, 3);
        foreach ($popular as $search) {
            $suggestions[] = [
                'type' => 'search',
                'text' => $search['query'],
                'count' => $search['count'],
            ];
        }

        return array_slice($suggestions, 0, $limit);
    }

    private function searchProducts(string $query, int $limit): array
    {
        return Product::search($query)
            ->take($limit)
            ->get()
            ->map(fn($p) => [
                'name' => $p->name,
                'slug' => $p->slug,
                'thumbnail_url' => $p->getThumbnailUrl(),
                'price' => $p->price,
            ])
            ->toArray();
    }
}
```

---

## Faceted Filtering

### Filter Configuration by Category

```php
// app/Services/Search/FacetService.php
class FacetService
{
    public function getFacetsForCategory(?Category $category): array
    {
        $baseFacets = [
            'price' => [
                'type' => 'range',
                'ranges' => $this->getPriceRanges($category),
            ],
            'rating' => [
                'type' => 'range',
                'options' => [4, 3, 2, 1],
                'labels' => ['4+ Stars', '3+ Stars', '2+ Stars', '1+ Star'],
            ],
            'brand_id' => [
                'type' => 'multi_select',
                'label' => 'Brand',
            ],
        ];

        // Add category-specific attributes
        if ($category) {
            $attributes = $category->getFilterableAttributes();
            foreach ($attributes as $attr) {
                $baseFacets["attributes.{$attr->slug}"] = [
                    'type' => $attr->type === 'color' ? 'color' : 'multi_select',
                    'label' => $attr->name,
                    'options' => $attr->options,
                ];
            }
        }

        return $baseFacets;
    }

    private function getPriceRanges(?Category $category): array
    {
        // Dynamic price ranges based on category
        if ($category) {
            $stats = $this->getCategoryPriceStats($category);
            return $this->generateRanges($stats['min'], $stats['max']);
        }

        return [
            ['min' => 0, 'max' => 500, 'label' => 'Under ₹500'],
            ['min' => 500, 'max' => 1000, 'label' => '₹500 - ₹1,000'],
            ['min' => 1000, 'max' => 5000, 'label' => '₹1,000 - ₹5,000'],
            ['min' => 5000, 'max' => 10000, 'label' => '₹5,000 - ₹10,000'],
            ['min' => 10000, 'max' => null, 'label' => 'Over ₹10,000'],
        ];
    }
}
```

---

## Search Analytics

```php
// app/Services/Search/SearchAnalyticsService.php
class SearchAnalyticsService
{
    public function track(
        SearchQuery $query,
        SearchResults $results,
        ?User $user,
        float $responseTime
    ): void {
        SearchLog::create([
            'user_id' => $user?->id,
            'session_id' => session()->getId(),
            'query' => $query->term,
            'filters' => $query->filters,
            'results_count' => $results->total,
            'response_time_ms' => $responseTime * 1000,
            'page' => $query->page,
        ]);

        // Track zero results
        if ($results->total === 0) {
            $this->trackZeroResults($query);
        }
    }

    public function trackClick(
        int $productId,
        string $searchQuery,
        int $position,
        ?User $user
    ): void {
        SearchLog::where('query', $searchQuery)
            ->where('session_id', session()->getId())
            ->latest()
            ->first()
            ?->update([
                'clicked_product_id' => $productId,
                'clicked_position' => $position,
            ]);
    }

    public function getPopularSearches(int $limit = 10): Collection
    {
        return Cache::remember('popular_searches', 3600, fn() =>
            SearchLog::select('query')
                ->selectRaw('COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->where('results_count', '>', 0)
                ->groupBy('query')
                ->orderByDesc('count')
                ->limit($limit)
                ->get()
        );
    }
}
```

---

## Synonym Management

```php
// app/Services/Search/SynonymService.php
class SynonymService
{
    public function expand(string $query): string
    {
        $synonyms = $this->getSynonyms();
        $words = explode(' ', strtolower($query));

        $expanded = [];
        foreach ($words as $word) {
            $expanded[] = $word;
            if (isset($synonyms[$word])) {
                $expanded = array_merge($expanded, $synonyms[$word]);
            }
        }

        return implode(' ', array_unique($expanded));
    }

    public function getSynonyms(): array
    {
        return Cache::remember('search_synonyms', 86400, fn() =>
            Synonym::all()
                ->groupBy('word')
                ->map(fn($group) => $group->pluck('synonym')->toArray())
                ->toArray()
        );
    }

    public function updateMeilisearchSynonyms(): void
    {
        $synonyms = $this->getSynonyms();

        app(MeilisearchClient::class)
            ->index('products')
            ->updateSynonyms($synonyms);
    }
}
```

---

## Product Ranking Algorithm

### Ranking Factors

| Factor | Weight | Description |
|--------|--------|-------------|
| Text Relevance | 0.30 | Match quality with search query |
| Sales Velocity | 0.20 | Recent sales performance |
| Rating Score | 0.15 | Average rating * review count factor |
| Seller Quality | 0.10 | Seller rating and fulfillment rate |
| Conversion Rate | 0.10 | Views to purchase ratio |
| Freshness | 0.05 | Newer products get slight boost |
| Stock Level | 0.05 | Products with good stock |
| Media Quality | 0.05 | Products with multiple images |

### Implementation

```php
// app/Jobs/UpdateProductRankings.php
class UpdateProductRankings implements ShouldQueue
{
    public function handle(): void
    {
        Product::active()
            ->chunkById(1000, function ($products) {
                foreach ($products as $product) {
                    $product->update([
                        'popularity_score' => $this->calculateScore($product),
                    ]);
                }

                // Reindex chunk
                Product::whereIn('id', $products->pluck('id'))
                    ->searchable();
            });
    }

    private function calculateScore(Product $product): float
    {
        $factors = [
            'sales' => $this->normalizeSales($product->sales_count),
            'rating' => $this->normalizeRating($product->rating, $product->review_count),
            'seller' => $this->normalizeSellerScore($product->seller),
            'conversion' => $this->normalizeConversion($product),
            'freshness' => $this->normalizeFreshness($product->created_at),
            'stock' => $this->normalizeStock($product->stock_quantity),
            'media' => $this->normalizeMedia($product->images_count),
        ];

        $weights = [
            'sales' => 0.25,
            'rating' => 0.20,
            'seller' => 0.15,
            'conversion' => 0.15,
            'freshness' => 0.10,
            'stock' => 0.10,
            'media' => 0.05,
        ];

        return collect($factors)
            ->map(fn($value, $key) => $value * $weights[$key])
            ->sum();
    }
}
```

---

## Acceptance Criteria

### Search Functionality
- [ ] Full-text search returns results in < 200ms
- [ ] Typo tolerance handles 1-2 character mistakes
- [ ] Filters work correctly with AND logic
- [ ] Facet counts update dynamically
- [ ] Pagination works correctly
- [ ] Sort options return correct order

### Autocomplete
- [ ] Suggestions appear within 100ms
- [ ] Product, category, and brand suggestions shown
- [ ] Keyboard navigation works
- [ ] Click/enter triggers search or navigation

### Personalization
- [ ] Logged-in users see personalized results
- [ ] Recently viewed products deprioritized
- [ ] Preferred brands/categories boosted
- [ ] Guest users see popularity-based ranking

### Analytics
- [ ] All searches logged
- [ ] Click-through tracked
- [ ] Zero-result queries identified
- [ ] Popular searches cached

