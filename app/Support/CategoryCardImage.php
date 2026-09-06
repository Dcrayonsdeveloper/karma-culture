<?php

namespace App\Support;

use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The picture a category tile should wear: its best product's own photograph.
 *
 * Category artwork used to be a second thing to keep up to date. Only 19 of the
 * 59 categories ever had one uploaded, so the other 40 showed a flat gradient -
 * a shop full of clothes advertising itself with coloured rectangles. The
 * catalogue already holds a photograph for every one of those categories; this
 * finds the right one.
 *
 * "Right" is the shop's own order of preference:
 *
 *   1. a product the admin marked Featured,
 *   2. otherwise the best seller,
 *   3. otherwise the first product in the category.
 *
 * All three fall out of one ORDER BY, because they are the same question asked
 * with decreasing confidence. There is no separate best-seller flag on
 * products - sales_count is what the storefront's own Bestsellers rail sorts
 * on, so it is what "best selling" means here too.
 *
 * The whole thing is two queries no matter how many tiles are on the page: one
 * for the category tree, one ranked query for the images. Nothing is cached, so
 * changing which product is featured changes the homepage on the next request
 * without anyone editing the homepage.
 */
final class CategoryCardImage
{
    /**
     * Best product image per category, keyed by the id of each category given.
     *
     * A category with nothing suitable is simply absent from the result, which
     * is how the caller knows to fall back to the category's own artwork.
     *
     * @param  iterable<Category>  $categories
     * @return array<int, ProductImage>
     */
    public static function forCategories(iterable $categories): array
    {
        $requested = Collection::make($categories)
            ->filter(fn ($c) => $c instanceof Category)
            ->keyBy('id');

        if ($requested->isEmpty()) {
            return [];
        }

        $branches = self::branches($requested->keys()->all());

        $lookupIds = Collection::make($branches)->flatten()->unique()->values()->all();

        if ($lookupIds === []) {
            return [];
        }

        $best = self::bestPerCategory($lookupIds);

        $out = [];

        foreach ($branches as $categoryId => $branchIds) {
            // A parent tile is entitled to a photograph out of its children -
            // "Men's" has no products filed directly against it, but everything
            // underneath it does. Among the candidates the branch offers, the
            // same order of preference decides.
            $candidates = [];

            foreach ($branchIds as $id) {
                if (isset($best[$id])) {
                    $candidates[] = $best[$id];
                }
            }

            if ($candidates === []) {
                continue;
            }

            usort($candidates, static fn ($a, $b) => [$b->is_featured, $b->sales_count, $a->product_id]
                <=> [$a->is_featured, $a->sales_count, $b->product_id]);

            $row = $candidates[0];

            // Hydrated rather than hand-built so display_url stays the one place
            // that knows how these paths resolve - rows hold both a bare
            // "products/x.jpg" and a rooted "/storage/products/x.jpg".
            $out[$categoryId] = ProductImage::hydrate([[
                'id' => $row->image_id,
                'url' => $row->url,
                'thumbnail_url' => $row->thumbnail_url,
                'media_type' => $row->media_type,
                'alt_text' => $row->alt_text,
            ]])->first();
        }

        return $out;
    }

    /**
     * Each requested category paired with itself and every category beneath it.
     *
     * One query for the whole tree rather than a walk per tile: the table is
     * small, and the alternative is a query per level per card.
     *
     * @param  array<int>  $rootIds
     * @return array<int, array<int>>
     */
    private static function branches(array $rootIds): array
    {
        $childrenOf = DB::table('categories')
            ->select('id', 'parent_id')
            ->get()
            ->groupBy('parent_id')
            ->map(fn ($rows) => $rows->pluck('id')->all())
            ->all();

        $branches = [];

        foreach ($rootIds as $rootId) {
            $branch = [];
            $queue = [$rootId];

            // Breadth-first with a seen-set: a parent_id cycle would otherwise
            // spin here forever, and bad data should not hang the homepage.
            while ($queue !== []) {
                $id = array_shift($queue);

                if (isset($branch[$id])) {
                    continue;
                }

                $branch[$id] = true;

                foreach ($childrenOf[$id] ?? [] as $childId) {
                    $queue[] = $childId;
                }
            }

            $branches[$rootId] = array_keys($branch);
        }

        return $branches;
    }

    /**
     * The single best image row for each of the given categories.
     *
     * ROW_NUMBER() keeps this to one round trip: without it this is either a
     * query per category, or every product in the catalogue dragged into PHP to
     * be sorted there.
     *
     * @param  array<int>  $categoryIds
     * @return array<int, object>
     */
    private static function bestPerCategory(array $categoryIds): array
    {
        $ranked = DB::table('products as p')
            ->join('product_images as pi', function ($join) {
                $join->on('pi.product_id', '=', 'p.id')
                    ->where('pi.is_primary', '=', true)
                    // A video in an <img> is a broken image. The tile has its
                    // own video branch; this column is only ever a still.
                    ->where('pi.media_type', '=', 'image');
            })
            ->whereIn('p.category_id', $categoryIds)
            ->where('p.is_active', '=', true)
            ->whereNull('p.deleted_at')
            ->whereNotNull('pi.url')
            ->where('pi.url', '<>', '')
            ->select([
                'pi.id as image_id',
                'pi.url',
                'pi.thumbnail_url',
                'pi.media_type',
                'pi.alt_text',
                'p.category_id',
                'p.is_featured',
                'p.sales_count',
                'p.id as product_id',
                DB::raw(
                    'ROW_NUMBER() OVER ('
                    .'PARTITION BY p.category_id '
                    // Featured first, then the best seller, then the earliest
                    // product - the three tiers as one ordering. pi.id last so
                    // a product carrying two primary rows still ranks stably.
                    .'ORDER BY p.is_featured DESC, p.sales_count DESC, p.id ASC, pi.id ASC'
                    .') as rn'
                ),
            ]);

        return DB::query()
            ->fromSub($ranked, 'ranked')
            ->where('rn', '=', 1)
            ->get()
            ->keyBy('category_id')
            ->all();
    }
}
