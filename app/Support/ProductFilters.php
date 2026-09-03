<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\ProductVariant;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The storefront filter sidebar, in one place.
 *
 * Every listing page - shop, a category, search, a brand, deals, a flash sale,
 * new arrivals, bestsellers - hands its own base query here and gets back the
 * same facets, so the sidebar reads and behaves the same wherever a shopper
 * lands. Before this the shop and category pages each carried their own copy of
 * the logic and had drifted apart (only one offered Rating), search had a third
 * cut-down version that knew nothing about size or colour, and the remaining
 * five pages offered no filters at all.
 *
 * $base is `fn (array $except): Builder` rather than a plain builder because a
 * page's own bound sometimes depends on which facet is being counted - the
 * category page's sub-category ticks REPLACE its bound instead of narrowing it.
 */
class ProductFilters
{
    /**
     * The only orderings a listing understands.
     *
     * 'relevance' and 'discount' belong to search and deals, and are accepted
     * everywhere rather than per-page: a shopper who carries ?sort=discount
     * from the deals page into a category gets a sensible order, not a 422.
     */
    public const SORTS = ['newest', 'price_asc', 'price_desc', 'rating', 'bestselling', 'name', 'relevance', 'discount'];

    /** Query keys the sidebar owns - the "is anything filtered" checks read this. */
    public const KEYS = [
        'category', 'subcategory', 'brand', 'size', 'colour',
        'min_price', 'max_price', 'rating', 'in_stock', 'on_sale',
    ];

    /**
     * The spellings a "Shop It Your Way" hanger stores, keyed by the sidebar's
     * own name for the same filter.
     *
     * tileAliases() copies these into the canonical keys but leaves the
     * originals in the request, which is what a hanger URL still carries. Any
     * code that takes a filter back OFF has to drop both spellings or the next
     * request simply re-derives it: the Active Filters chips stripped only
     * `colour` and handed back a URL that still said `shade=Indigo`, so the
     * cross on a hanger's chip did nothing at all.
     *
     * @var array<string, string>
     */
    public const ALIASES = [
        'min_price' => 'price_min',
        'max_price' => 'price_max',
        'colour' => 'shade',
    ];

    /**
     * Every spelling of the given filters - canonical names plus the hanger
     * aliases - for handing to `request()->except()`.
     *
     * @param  array<int, string>|string  $keys
     * @return array<int, string>
     */
    public static function allSpellings(array|string $keys): array
    {
        $keys = (array) $keys;

        return array_values(array_unique(array_merge(
            $keys,
            array_values(Arr::only(self::ALIASES, $keys)),
        )));
    }

    /**
     * How many values one multi-select filter may carry.
     *
     * A shopper ticks a handful of sizes. A URL carrying five thousand of them
     * is someone making the page build a five-thousand-clause WHERE, so the
     * list is cut rather than the request refused.
     */
    private const MAX_FILTER_VALUES = 50;

    /** @var array<string, mixed> */
    private array $filters;

    private CategoryTree $tree;

    private ?array $categoryIds;

    private ?array $subcategoryIds;

    /**
     * @param  Closure(array): Builder  $base
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        private Request $request,
        private Closure $base,
        private array $options = [],
    ) {
        $this->filters = self::normalise($request);

        // A page whose whole point is an ordering (bestsellers, new arrivals,
        // deals) opens on it. normalise() falls back to 'newest' for everyone
        // else, so without this the bestsellers page opened sorted by date.
        if (isset($options['default_sort']) && ! in_array($request->input('sort'), self::SORTS, true)) {
            $this->filters['sort'] = $options['default_sort'];
        }

        $this->tree = $options['tree'] ?? new CategoryTree;
        $this->categoryIds = $this->tree->idsForSlug($this->filters['category']);
        $this->subcategoryIds = $this->tree->idsForSlugs($this->filters['subcategory']);
    }

    /** @param  Closure(array): Builder  $base */
    public static function for(Request $request, Closure $base, array $options = []): self
    {
        return new self($request, $base, $options);
    }

    /**
     * The names the "Shop It Your Way" hangers store, mapped onto the sidebar's
     * own.
     *
     * A hanger keeps its query string as an admin typed it - price_min=1000,
     * shade=Tan - so the shop accepts those spellings as well as its own.
     * Renaming the stored strings instead would break every hanger already set
     * up. ShopFilterTiles puts a hanger through this too, so the count it
     * checks a hanger against is the listing that hanger actually opens.
     *
     * @return array<string, mixed>
     */
    public static function tileAliases(Request $request): array
    {
        return array_filter([
            'min_price' => $request->input('min_price', $request->input('price_min')),
            'max_price' => $request->input('max_price', $request->input('price_max')),
            'colour' => $request->input('colour', $request->filled('shade') ? (array) $request->input('shade') : null),
            'size' => $request->filled('size') ? (array) $request->input('size') : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** @return array<string, mixed> */
    public function values(): array
    {
        return $this->filters;
    }

    public function tree(): CategoryTree
    {
        return $this->tree;
    }

    /**
     * The one query behind both the grid and the sidebar.
     *
     * $except names the dimensions to leave out, which is what makes the
     * sidebar live: the Size list is built from everything matching the OTHER
     * filters, so picking a colour immediately reshapes the sizes on offer. A
     * facet never applies its own filter, because that is the only way two
     * sizes can both stay pickable - filtering to XL first would leave XL as
     * the only size the list could ever show.
     */
    public function query(array $except = []): Builder
    {
        $query = ($this->base)($except);
        $f = $this->filters;

        // Products are filed on the deepest category, so a parent has to match its
        // descendants too - otherwise picking MEN returns nothing while its
        // sub-categories return everything. A slug that resolved to nothing matches
        // nothing, rather than handing back the whole shop.
        //
        // Membership comes from the category_product pivot, not products.category_id:
        // a product can be shelved in several categories, and the one it is filed
        // under is only the first of them.
        if ($this->options['owns_category'] ?? true) {
            if ($this->categoryIds !== null && ! in_array('category', $except, true)) {
                $query->inAnyCategory($this->categoryIds);
            }

            if ($this->subcategoryIds !== null && ! in_array('subcategory', $except, true)) {
                $query->inAnyCategory($this->subcategoryIds);
            }
        }

        // Sizes live on the variants, colours on the product's Colours list, so each
        // needs its own lookup rather than a column on products. Stock is deliberately
        // not part of the size match: a sold-out size still belongs to the product, and
        // "In Stock Only" is the control for hiding it.
        if ($f['size'] !== [] && ! in_array('size', $except, true)) {
            $sizes = $f['size'];
            $query->whereHas('variants', fn ($q) => $q->where('is_active', true)->whereSizeIn($sizes));
        }

        if ($f['colour'] !== [] && ! in_array('colour', $except, true)) {
            $colours = $f['colour'];
            $query->where(function ($q) use ($colours) {
                foreach ($colours as $colour) {
                    // Matches the name inside the Colours JSON, and the legacy colour
                    // stored on a variant for older products.
                    //
                    // The value is a bound parameter, so this was never an injection -
                    // but % and _ are LIKE wildcards, and a colour of "%" quietly
                    // matched every product on the site.
                    $needle = '%"'.self::escapeLike($colour).'"%';
                    $q->orWhere('products.attributes', 'like', $needle)
                        ->orWhereHas('variants', fn ($vq) => $vq->where('attributes', 'like', $needle));
                }
            });
        }

        // Brand filter. Slugs, not ids, so the URL stays readable and shareable.
        if ($f['brand'] !== [] && ! in_array('brand', $except, true)) {
            $slugs = $f['brand'];
            $query->whereHas('brand', fn ($q) => $q->whereIn('slug', $slugs));
        }

        if (! in_array('price', $except, true)) {
            if ($f['min_price'] !== null) {
                $query->where('products.price', '>=', $f['min_price']);
            }
            if ($f['max_price'] !== null) {
                $query->where('products.price', '<=', $f['max_price']);
            }
        }

        if ($f['rating'] !== null && ! in_array('rating', $except, true)) {
            $query->where('products.rating', '>=', $f['rating']);
        }

        // In Stock Only, read through the one predicate the card's badge uses.
        // On quantity alone this kept a product flagged out_of_stock that still
        // had units on hand, and then drew "Out of Stock" across the card it had
        // just let through. StockPredicateTest pins both halves.
        if ($f['in_stock']) {
            $query->inStock();
        }

        // On sale: priced under its own MRP.
        if ($f['on_sale']) {
            $query->whereNotNull('products.mrp')->whereColumn('products.price', '<', 'products.mrp');
        }

        return $query;
    }

    /** The grid: the full filter set, sorted and paginated. */
    public function results(int $perPage = 24, array $with = ['category', 'brand', 'primaryImage']): LengthAwarePaginator
    {
        return $this->sort($this->query()->with($with))->paginate($perPage)->withQueryString();
    }

    public function sort(Builder $query, ?string $sort = null): Builder
    {
        // Sold out sinks to the back of every grid, whatever the shopper
        // picked. It goes on first so it is the PRIMARY key and their sort
        // becomes the tie-break, running inside the buyable block and again
        // inside the sold-out tail - a card nobody can buy has no business
        // sitting second in a row of four.
        //
        // The facet lookups below all reorder() before counting, so this never
        // reaches the sidebar's grouped counts.
        $query->inStockFirst();

        return match ($sort ?? $this->filters['sort']) {
            'price_asc' => $query->orderBy('products.price', 'asc'),
            'price_desc' => $query->orderBy('products.price', 'desc'),
            'rating' => $query->orderBy('products.rating', 'desc'),
            'bestselling', 'relevance' => $query->orderBy('products.sales_count', 'desc'),
            'name' => $query->orderBy('products.name', 'asc'),
            // Deepest cut first. mrp is guarded against 0 so a mispriced row
            // divides by 1 rather than blowing up the whole listing.
            'discount' => $query->orderByRaw('(products.mrp - products.price) / GREATEST(COALESCE(products.mrp, 0), 1) DESC'),
            default => $query->orderBy('products.created_at', 'desc'),
        };
    }

    /**
     * Everything the shared sidebar partial renders.
     *
     * $overrides lets a page supply a facet it builds itself - the category
     * page works out its own sibling sub-category list, with per-row counts
     * that answer "how many would I get if I ticked this box".
     */
    public function facets(array $overrides = []): array
    {
        $panel = [
            'action' => $this->options['action'] ?? url()->current(),
            'reset' => $this->options['reset'] ?? url()->current(),
            'hidden' => $this->options['hidden'] ?? [],
            'values' => $this->filters,
            'categories' => collect(),
            'subcategories' => collect(),
            'active_subcategories' => $this->filters['subcategory'],
            'sizes' => $this->sizes(),
            'colours' => $this->colours(),
            'brands' => $this->brands(),
            'show_rating' => ($this->options['rating'] ?? true) && $this->hasRatedProducts(),
        ];

        if ($this->options['owns_category'] ?? true) {
            $panel['categories'] = $this->categories();
            $panel['subcategories'] = $this->subcategories();
        }

        return array_merge($panel, $overrides);
    }

    /**
     * Whether this listing holds anything a rating filter could match.
     *
     * products.rating defaults to 0 and only moves when a review is approved,
     * so on a catalogue with no reviews yet every one of the five options
     * returned an empty grid - the section was five ways to break the page. It
     * excludes its own filter, like the other facets, or picking 4 stars on a
     * listing whose only rated product is 3-star would take the section away
     * and leave no way to undo the choice.
     */
    private function hasRatedProducts(): bool
    {
        return $this->query(['rating'])
            ->reorder()
            ->where('products.rating', '>', 0)
            ->exists();
    }

    /**
     * Top-level categories, each carrying the count it would return under the
     * shopper's OTHER filters. Every one is kept, so the shape of the catalogue
     * stays visible and the sidebar matches the navigation menu.
     *
     * Sub-category ticks are left out of that count: they belong to the
     * category being replaced, so counting them would show 0 beside every
     * other category.
     */
    private function categories(): Collection
    {
        $counts = $this->countsByCategory(['category', 'subcategory']);

        return $this->tree->rows()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->each(fn ($c) => $c->setAttribute('products_total', $this->totalUnder($c->id, $counts)))
            ->sortBy('name')
            ->values();
    }

    /**
     * Sub-categories are narrowed rather than listed whole: the list used to
     * offer every sub-category in the shop, so a shopper inside MEN was invited
     * to tick Sarees and got nothing back. A ticked one is always kept, or it
     * could never be unticked once another filter had emptied it.
     */
    private function subcategories(): Collection
    {
        $counts = $this->countsByCategory(['subcategory']);
        $selected = $this->filters['subcategory'];

        return $this->tree->rows()
            ->where('is_active', true)
            ->filter(fn ($c) => $c->parent_id !== null)
            ->when($this->categoryIds !== null, fn ($rows) => $rows->whereIn('id', $this->categoryIds ?: [0]))
            ->each(fn ($c) => $c->setAttribute('products_total', $this->totalUnder($c->id, $counts)))
            ->filter(fn ($c) => $c->products_total > 0 || in_array($c->slug, $selected, true))
            ->sortBy('name')
            ->values();
    }

    /**
     * Matching products per category in ONE grouped query - a count query per
     * row would be forty round trips on a forty-category shop.
     */
    private function countsByCategory(array $except): Collection
    {
        // Grouped on the pivot, so a product shelved in two categories is counted
        // under both - grouping products.category_id would credit it only to the
        // one it is filed under and under-report every other shelf. count(distinct)
        // because the join fans a product out into one row per category.
        return $this->query($except)
            ->reorder()
            ->join('category_product', 'category_product.product_id', '=', 'products.id')
            ->select('category_product.category_id')
            ->selectRaw('count(distinct products.id) as aggregate')
            ->groupBy('category_product.category_id')
            ->pluck('aggregate', 'category_id');
    }

    private function totalUnder(int $id, Collection $counts): int
    {
        $total = 0;

        foreach ($this->tree->descendantIds($id) as $categoryId) {
            $total += (int) ($counts[$categoryId] ?? 0);
        }

        return $total;
    }

    /**
     * Sizes carried by the products the shopper is currently looking at. The
     * list used to be every size in the shop, so a category holding one polo
     * still offered UK 7 to UK 11 and picking one returned nothing. A ticked
     * size is always kept, even once another filter has emptied it out, or it
     * could never be unticked - the shopper would be stranded with no results.
     */
    public function sizes(): Collection
    {
        return $this->sizesOf($this->query(['size']))
            ->merge($this->stocked($this->filters['size'], fn () => $this->sizesOf($this->catalogue())))
            ->unique()
            ->sortBy(fn ($s) => ProductVariant::sizeRank($s))
            ->values();
    }

    /** The size labels carried by the products a query matches. */
    private function sizesOf(Builder $query): Collection
    {
        return ProductVariant::query()
            ->where('is_active', true)
            ->whereIn('product_id', $query->reorder()->select('products.id'))
            ->pluck('name')
            ->map(fn ($n) => ProductVariant::sizeLabel($n))
            ->filter();
    }

    /**
     * The page's own bound with every facet lifted off it: what this listing
     * could ever offer, whatever the shopper has ticked.
     */
    private function catalogue(): Builder
    {
        return ($this->base)(self::KEYS);
    }

    /**
     * Which of the ticked values the listing actually carries.
     *
     * A ticked value is added back to its facet list so it can be unticked
     * again once another filter has emptied it out. That kept whatever the URL
     * said, though, so ?size=cd - a typo in a home page hanger - drew "cd" as a
     * pickable size on a page holding nothing, and ?shade=cinnamon drew a
     * swatch for a colour the shop has never sold. A value nothing here carries
     * is not one of ours: it stays out of the sidebar, and the Active Filters
     * row above the grid is what takes it back off.
     *
     * The lookup is skipped entirely when nothing is ticked, which is the usual
     * case - it costs a query only on a page that is already filtered.
     *
     * @param  array<int, string>  $selected
     * @param  Closure(): Collection  $offered
     * @return array<int, string>
     */
    private function stocked(array $selected, Closure $offered): array
    {
        if ($selected === []) {
            return [];
        }

        return array_values(array_intersect($selected, $offered()->all()));
    }

    /**
     * Colours, read off the product's own Colours list. The ticked ones are
     * concatenated last so a real swatch hex wins over the hex-less
     * placeholder when unique() collapses the pair.
     */
    public function colours(): Collection
    {
        $ticked = $this->stocked(
            $this->filters['colour'],
            fn () => $this->coloursOf($this->catalogue())->pluck('name'),
        );

        return $this->coloursOf($this->query(['colour']))
            ->concat(collect($ticked)->map(fn ($n) => ['name' => $n, 'hex' => null]))
            ->unique('name')
            ->sortBy('name')
            ->values();
    }

    /** The colours listed by the products a query matches, name and swatch. */
    private function coloursOf(Builder $query): Collection
    {
        return $query
            ->reorder()
            ->pluck('attributes')
            ->flatMap(fn ($a) => collect(data_get($a, 'Colours', []))
                ->map(fn ($c) => is_array($c)
                    ? ['name' => trim((string) ($c['name'] ?? '')), 'hex' => $c['hex'] ?? null]
                    : ['name' => trim((string) $c), 'hex' => null]))
            ->filter(fn ($c) => $c['name'] !== '');
    }

    /**
     * Only brands that actually carry a matching product. The table holds rows
     * left over from the demo seed, and offering "Canon" on a clothing shop
     * returns nothing.
     */
    public function brands(): Collection
    {
        $selected = $this->filters['brand'];

        return Brand::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereIn('id', $this->query(['brand'])->reorder()->select('products.brand_id'))
                ->orWhereIn('slug', $selected))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    /**
     * The filters, validated and normalised.
     *
     * A listing URL is public, shareable and crawled, so a malformed parameter
     * degrades to "no filter" rather than to a 422 the crawler indexes. The
     * check is still a real boundary: before this, `?min_price[]=1` reached
     * `where('price', '>=', [...])` and 500'd the page, and `?rating=abc`
     * reached the comparison unchecked.
     *
     * Scalars go through the validator. The multi-value chips are normalised by
     * hand instead, because Validator::valid() keeps an array whole when only
     * one of its elements fails `size.*` - it would have handed the bad element
     * straight back.
     *
     * @return array{category: ?string, subcategory: array<int, string>, size: array<int, string>, colour: array<int, string>, brand: array<int, string>, min_price: ?float, max_price: ?float, rating: ?int, in_stock: bool, on_sale: bool, sort: string}
     */
    public static function normalise(Request $request): array
    {
        $scalarKeys = ['category', 'min_price', 'max_price', 'rating', 'in_stock', 'on_sale', 'sort'];

        $validator = Validator::make(Arr::only($request->all(), $scalarKeys), [
            'category' => ['nullable', 'string', 'max:120'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'in_stock' => ['nullable', 'boolean'],
            'on_sale' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', Rule::in(self::SORTS)],
        ]);

        $safe = Arr::only($validator->valid(), $scalarKeys);

        $number = function (string $key) use ($safe): ?float {
            $value = $safe[$key] ?? null;

            return ($value === null || $value === '') ? null : (float) $value;
        };

        $category = $safe['category'] ?? null;
        $category = is_string($category) ? trim($category) : null;
        $rating = $safe['rating'] ?? null;
        $sort = $safe['sort'] ?? null;

        [$minPrice, $maxPrice] = self::orderedRange($number('min_price'), $number('max_price'));

        return [
            'category' => ($category === null || $category === '') ? null : $category,
            'subcategory' => self::stringList($request->input('subcategory'), 120),
            'size' => self::stringList($request->input('size'), 40),
            'colour' => self::stringList($request->input('colour'), 60),
            'brand' => self::stringList($request->input('brand'), 120),
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'rating' => ($rating === null || $rating === '') ? null : (int) $rating,
            'in_stock' => filter_var($safe['in_stock'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'on_sale' => filter_var($safe['on_sale'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'sort' => (is_string($sort) && $sort !== '') ? $sort : 'newest',
        ];
    }

    /**
     * A price pair, smaller bound first.
     *
     * Min 1000 with Max 0 is `price >= 1000 AND price <= 0` - a range nothing
     * can ever be in - so the shop answered "0 products found" with an Active
     * Filters chip reading "₹1,000 - ₹0" and nothing anywhere saying what was
     * wrong. The two numbers still name one range, so they are put back in
     * order rather than refused: a 422 on a public, shareable, crawled URL is
     * worse, and dropping one bound would silently widen the grid past what
     * the shopper asked for.
     *
     * It happens here, in normalise(), because most of these never come from
     * the sidebar's two boxes - a link a shopper shared, a crawler, and a
     * "Shop It Your Way" hanger an admin typed backwards all arrive as a query
     * string. Everything downstream reads what normalise() returns, so the
     * boxes and the chip redraw as the corrected range too, which is what
     * tells the shopper it was reordered.
     *
     * Equal bounds are left alone: min == max is the exact-price filter, not a
     * mistake.
     *
     * @return array{0: ?float, 1: ?float}
     */
    public static function orderedRange(?float $min, ?float $max): array
    {
        return ($min !== null && $max !== null && $min > $max) ? [$max, $min] : [$min, $max];
    }

    /**
     * One multi-select filter, reduced to a bounded list of plain strings.
     *
     * Nested arrays (?size[][]=x) and objects are dropped rather than passed to
     * the query builder, which would fatal on them.
     *
     * @return array<int, string>
     */
    private static function stringList(mixed $value, int $maxLength): array
    {
        $items = [];

        foreach (Arr::wrap($value) as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $item = trim((string) $item);

            if ($item === '' || mb_strlen($item) > $maxLength) {
                continue;
            }

            $items[$item] = $item;

            if (count($items) >= self::MAX_FILTER_VALUES) {
                break;
            }
        }

        return array_values($items);
    }

    /** % and _ are LIKE wildcards; a shopper picking a colour means it literally. */
    public static function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
