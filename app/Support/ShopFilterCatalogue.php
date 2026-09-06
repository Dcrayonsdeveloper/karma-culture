<?php

namespace App\Support;

use App\Models\Colour;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopFilterExclusion;
use App\Models\Texture;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * The filter values the shop actually carries, worked out from the catalogue.
 *
 * There is one source of truth for WHICH values exist - the products - and a
 * second, separate one for WHETHER a value is offered: the admin's exclusion
 * list. Nothing duplicates a product's attributes into a filter table, so the
 * two can never drift: a size added to a product is a size on the rail the
 * moment it is saved, and the last product carrying a colour taking it away
 * takes the colour off the rail with it.
 *
 * Where each value comes from:
 *   size    - product_variants.name of active variants on active products
 *   shade   - products.attributes -> "Colours" (name + swatch hex)
 *   texture - products.attributes -> "Textures" (plain strings)
 *   price   - bands derived from the live price spread, never exact prices
 *
 * Values are normalised before they are grouped, so Black / black / BLACK /
 * "Black " are one filter, shown under the spelling the catalogue uses most.
 *
 * The whole derivation is cached against ProductVariant::filterCacheVersion(),
 * which every product, variant and exclusion save bumps - so a page request
 * costs a cache read, not a scan of the catalogue, and an edit is live at once.
 *
 * There is a THIRD input, and it deliberately has no say in any of the above:
 * the Colours and Textures master lists an admin curates. Those supply artwork
 * - a fabric photo, and a swatch hex for a colour no product bothered to give
 * one - for values the catalogue already carries. They never add a value. A
 * colour sitting in the picker that nobody has put on a product is not a
 * hanger, or the rails would go straight back to promoting listings with
 * nothing on them, which is the whole reason they stopped being a typed-in
 * table. See {@see masters()}.
 */
class ShopFilterCatalogue
{
    /** The rails. `shade` is the colour rail - the name the storefront uses. */
    public const TYPES = ['size', 'shade', 'texture', 'price'];

    /** products.attributes keys the two list attributes live under. */
    public const COLOURS_KEY = 'Colours';

    public const TEXTURES_KEY = 'Textures';

    /** Where the price bands are cut: the quartiles of the live catalogue. */
    private const PRICE_QUANTILES = [0.25, 0.5, 0.75];

    /**
     * Container keys the per-request memo is held under.
     *
     * This list is what forget() clears, so anything memoised below has to be
     * named here too - a key added to one and not the other is a cache an
     * admin's own save cannot invalidate for the rest of their request.
     */
    private const MEMO = [
        'kk.shop_filters.version',
        'kk.shop_filters.derived',
        'kk.shop_filters.exclusions',
        'kk.shop_filters.masters',
    ];

    /**
     * Remember one answer for the rest of the request.
     *
     * The cache store here is the database, so every Cache::get is a query -
     * and one page asks the same questions many times over: the home rail
     * resolves four rails, and the shop sidebar asks which values are hidden
     * once per facet. Reading each answer once turns a dozen round trips
     * into three.
     *
     * It lives on the container rather than in a static, so it dies with the
     * application instance - which is what makes it a REQUEST memo and not a
     * process one. A static survived the application being rebuilt and handed
     * the next request, or the next test, the answer from the last one.
     */
    private static function memo(string $key, Closure $resolve): mixed
    {
        $app = app();

        if (! $app->bound($key)) {
            $app->instance($key, $resolve());
        }

        return $app->make($key);
    }

    /**
     * Drop the memo.
     *
     * Called from {@see ProductVariant::bumpFilterCache()}, so a product saved
     * mid-request is reflected by anything that reads the rails afterwards -
     * an admin saving a product and being redirected onto a page that draws
     * them.
     */
    public static function forget(): void
    {
        $app = app();

        foreach (self::MEMO as $key) {
            $app->forgetInstance($key);
        }
    }

    /**
     * One value's identity, independent of spelling.
     *
     * Case and surrounding/repeated whitespace are what separate the four
     * "Black"s an admin can type across four products; nothing else is
     * touched, so "Dusty Rose" and "Dusty-Rose" stay the two different values
     * they are.
     */
    public static function normaliseKey(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Every rail, ready to render.
     *
     * @param  bool  $includeHidden  admin screens pass true - they have to show
     *                               what has been hidden in order to offer it back
     * @return array<string, Collection<int, ShopFilterValue>>
     */
    public static function groups(bool $includeHidden = false): array
    {
        $groups = [];

        foreach (self::TYPES as $type) {
            $groups[$type] = self::values($type, $includeHidden);
        }

        return $groups;
    }

    /**
     * One rail.
     *
     * @return Collection<int, ShopFilterValue>
     */
    public static function values(string $type, bool $includeHidden = false): Collection
    {
        if (! in_array($type, self::TYPES, true)) {
            return collect();
        }

        $derived = self::derived()[$type] ?? [];
        $excluded = self::exclusions()[$type] ?? [];
        // Artwork only. $derived decides which rows exist; this decides how the
        // ones that already exist are drawn, and an unmatched master row is
        // simply never read.
        $masters = self::masters()[$type] ?? [];

        $rows = collect();

        foreach ($derived as $key => $row) {
            $hidden = isset($excluded[$key]);

            if ($hidden && ! $includeHidden) {
                continue;
            }

            $rows->push(new ShopFilterValue(
                type: $type,
                key: $key,
                label: $row['label'],
                // The product's own swatch wins, every time. It is the hex the
                // shopper is shown on the product page, so a rail that painted
                // the master list's idea of Ivory over it would be the one
                // telling the lie. The curated hex is a fallback for a colour
                // no product ever bothered to give one - which is most of them,
                // because the colour list on a product accepts bare strings.
                shade_hex: $row['hex'] ?? $masters[$key]['hex'] ?? null,
                query_string: $row['query'],
                count: $row['count'],
                hidden: $hidden,
                exclusion_uuid: $hidden ? $excluded[$key]['uuid'] : null,
                image: $masters[$key]['image'] ?? null,
            ));
        }

        // A value nothing carries any more still has to be listed on the admin
        // screen while it is hidden, or the only way back is a database edit:
        // hide the last Velvet product's texture, delete the product, and the
        // exclusion becomes unreachable. Count 0, so it reads as retired.
        if ($includeHidden) {
            foreach ($excluded as $key => $row) {
                if (isset($derived[$key])) {
                    continue;
                }

                $rows->push(new ShopFilterValue(
                    type: $type,
                    key: $key,
                    label: $row['label'],
                    // Nothing carries it, so there is no product hex to prefer:
                    // whatever the picker holds is all anyone can be shown, and
                    // a retired value drawn with its swatch is far easier to
                    // recognise in a list than a bare word.
                    shade_hex: $masters[$key]['hex'] ?? null,
                    query_string: self::queryFor($type, $row['label'], $key),
                    count: 0,
                    hidden: true,
                    exclusion_uuid: $row['uuid'],
                    image: $masters[$key]['image'] ?? null,
                ));
            }
        }

        return $rows->values();
    }

    /**
     * The normalised keys an admin has hidden on one rail.
     *
     * The storefront sidebar reads this to drop a hidden value from its own
     * facets: a value taken off the rails should not come back as a checkbox
     * two clicks later.
     *
     * @return array<int, string>
     */
    public static function hiddenKeys(string $type): array
    {
        return array_keys(self::exclusions()[$type] ?? []);
    }

    /** Whether one value, however it is spelled, is hidden on that rail. */
    public static function isHidden(string $type, string $value): bool
    {
        return isset(self::exclusions()[$type][self::normaliseKey($value)]);
    }

    /**
     * The query string that opens a listing for one value.
     *
     * Price keys carry their own bounds ("1000:2000"), which is what makes a
     * band addressable at all - its label is prose.
     */
    public static function queryFor(string $type, string $label, ?string $key = null): string
    {
        if ($type === 'price') {
            [$min, $max] = array_pad(explode(':', (string) $key, 2), 2, '');

            return self::priceQuery(
                $min === '' ? null : (float) $min,
                $max === '' ? null : (float) $max,
            );
        }

        return match ($type) {
            // `shade` is the alias the shop accepts for `colour`; the rails
            // have always spoken it, and hanger URLs already in the wild say
            // shade=Tan.
            'shade' => 'shade='.urlencode($label),
            'texture' => 'texture='.urlencode($label),
            default => 'size='.urlencode($label),
        };
    }

    /**
     * The admin's exclusions, keyed type -> normalised value.
     *
     * @return array<string, array<string, array{uuid: string, label: string}>>
     */
    private static function exclusions(): array
    {
        return self::memo('kk.shop_filters.exclusions', fn () => Cache::remember(
            'kk_shop_filter_exclusions_v'.self::version(),
            now()->addHours(6),
            function (): array {
                $rows = [];

                foreach (ShopFilterExclusion::query()->get(['uuid', 'type', 'value_key', 'label']) as $row) {
                    $rows[$row->type][$row->value_key] = [
                        'uuid' => $row->uuid,
                        'label' => $row->label,
                    ];
                }

                return $rows;
            }
        ));
    }

    /**
     * Every value the live catalogue carries, keyed type -> normalised value.
     *
     * @return array<string, array<string, array{label: string, count: int, hex: ?string, query: string}>>
     */
    private static function derived(): array
    {
        return self::memo('kk.shop_filters.derived', fn () => Cache::remember(
            'kk_shop_filter_catalogue_v'.self::version(),
            now()->addHours(6),
            fn (): array => [
                'size' => self::deriveSizes(),
                'shade' => self::deriveAttributeList(self::COLOURS_KEY, 'shade'),
                'texture' => self::deriveAttributeList(self::TEXTURES_KEY, 'texture'),
                'price' => self::derivePriceBands(),
            ]
        ));
    }

    /** The catalogue's version counter, read once per request. */
    private static function version(): int
    {
        return self::memo('kk.shop_filters.version', fn () => ProductVariant::filterCacheVersion());
    }

    /** The products a filter value has to be carried by to count as offered. */
    private static function activeProducts()
    {
        // Deliberately the same bound the shop itself opens with, rather than
        // scopeActive(): a rail offering a value the shop does not list is the
        // dead end this whole screen exists to stop. Soft-deleted rows are
        // already out, through the model's global scope.
        return Product::query()->where('is_active', true);
    }

    /**
     * Sizes, off the variants.
     *
     * One query returning distinct (size, product) pairs, grouped in PHP -
     * the label has to go through ProductVariant::sizeLabel() before it can be
     * grouped at all, because older rows store the whole variant name.
     *
     * @return array<string, array{label: string, count: int, hex: ?string, query: string}>
     */
    private static function deriveSizes(): array
    {
        $rows = ProductVariant::query()
            ->where('is_active', true)
            ->whereIn('product_id', self::activeProducts()->select('products.id'))
            ->distinct()
            ->get(['name', 'product_id']);

        $spellings = [];
        $carriers = [];

        foreach ($rows as $row) {
            $label = ProductVariant::sizeLabel($row->name);

            if ($label === '') {
                continue;
            }

            $key = self::normaliseKey($label);
            $spellings[$key][$label] = ($spellings[$key][$label] ?? 0) + 1;
            $carriers[$key][$row->product_id] = true;
        }

        $sizes = self::assemble($spellings, $carriers, 'size');

        // A shopper's order, not the alphabet's - which puts L before M and XL
        // before XS.
        uasort($sizes, fn ($a, $b) => ProductVariant::sizeRank($a['label']) <=> ProductVariant::sizeRank($b['label']));

        return $sizes;
    }

    /**
     * Colours or textures, off the product's own attributes JSON.
     *
     * Both are product-level lists rather than per-variant values, so one
     * reader covers the pair. A colour entry is {name, hex}; a texture entry
     * is a plain string. Either form is accepted for both, because the colour
     * list has carried bare strings since before it had swatches.
     *
     * @return array<string, array{label: string, count: int, hex: ?string, query: string}>
     */
    private static function deriveAttributeList(string $jsonKey, string $type): array
    {
        $spellings = [];
        $carriers = [];
        $hexes = [];

        self::activeProducts()
            ->whereNotNull('attributes')
            ->select(['products.id', 'products.attributes'])
            // Chunked so a large catalogue is never wholly resident: this runs
            // once per catalogue change, not once per request, but "once" on
            // 50k products should still not be one 50k-row hydrate.
            ->chunk(500, function ($products) use ($jsonKey, &$spellings, &$carriers, &$hexes): void {
                foreach ($products as $product) {
                    foreach (self::listFrom($product->attributes, $jsonKey) as $entry) {
                        [$label, $hex] = $entry;

                        $key = self::normaliseKey($label);
                        $spellings[$key][$label] = ($spellings[$key][$label] ?? 0) + 1;
                        $carriers[$key][$product->id] = true;

                        if ($hex !== null && ! isset($hexes[$key])) {
                            $hexes[$key] = $hex;
                        }
                    }
                }
            });

        $values = self::assemble($spellings, $carriers, $type);

        foreach ($values as $key => $value) {
            $values[$key]['hex'] = $hexes[$key] ?? null;
        }

        uasort($values, fn ($a, $b) => strcasecmp($a['label'], $b['label']));

        return $values;
    }

    /**
     * One product's list for a JSON key, as [label, hex] pairs.
     *
     * @return array<int, array{0: string, 1: ?string}>
     */
    public static function listFrom(mixed $attributes, string $jsonKey): array
    {
        $entries = [];

        foreach ((array) data_get($attributes, $jsonKey, []) as $entry) {
            if (is_array($entry)) {
                $label = trim((string) ($entry['name'] ?? ''));
                $hex = isset($entry['hex']) && is_string($entry['hex']) ? trim($entry['hex']) : null;
            } elseif (is_scalar($entry)) {
                $label = trim((string) $entry);
                $hex = null;
            } else {
                continue;
            }

            if ($label === '') {
                continue;
            }

            $entries[] = [$label, ($hex ?? '') !== '' ? $hex : null];
        }

        return $entries;
    }

    /**
     * Turn the collected spellings into one row per value.
     *
     * The label shown is the spelling the catalogue uses most often; ties go
     * to the one that sorts first, which puts "Black" ahead of "black"
     * because uppercase sorts first - usually the properly-cased one, and in
     * any case a stable answer rather than whichever product was read first.
     *
     * @param  array<string, array<string, int>>  $spellings
     * @param  array<string, array<int, bool>>  $carriers
     * @return array<string, array{label: string, count: int, hex: ?string, query: string}>
     */
    private static function assemble(array $spellings, array $carriers, string $type): array
    {
        $values = [];

        foreach ($spellings as $key => $forms) {
            arsort($forms);
            $top = max($forms);
            $winners = array_keys(array_filter($forms, fn ($n) => $n === $top));
            sort($winners, SORT_STRING);
            $label = $winners[0];

            $values[$key] = [
                'label' => $label,
                'count' => count($carriers[$key] ?? []),
                'hex' => null,
                'query' => self::queryFor($type, $label, $key),
            ];
        }

        return $values;
    }

    /**
     * Price bands, cut where the products are rather than where the price axis
     * is.
     *
     * Exact prices would be hundreds of one-product filters, so the spread is
     * cut into round bands - the shape the hand-typed rail already used
     * ("Under 1k", "1k - 2k", "7k+"). The cuts are the quartiles of the live
     * catalogue, each rounded up to a round number, so the bands hold roughly a
     * quarter of the shop each.
     *
     * Dividing the axis instead - max price over four - was tried and is what
     * this replaces: one product at 35k stretched the bands to 10k wide, three
     * of the four came back empty and got dropped, and the rail offered
     * "Under 10k" (almost everything) beside "30k+" (almost nothing). A band
     * has to narrow the grid to be worth a chip.
     *
     * Cost is four small indexed reads and one grouped count, once per
     * catalogue change rather than once per request. A single band is not a
     * filter, so a catalogue that cannot be cut offers no price rail at all.
     *
     * @return array<string, array{label: string, count: int, hex: ?string, query: string}>
     */
    private static function derivePriceBands(): array
    {
        $total = (int) self::activeProducts()->count();

        if ($total < 2) {
            return [];
        }

        // The price a quarter, half and three quarters of the way up the
        // catalogue. OFFSET on an ordered, indexed column rather than a
        // percentile function, which MySQL and MariaDB spell differently.
        $cuts = [];

        foreach (self::PRICE_QUANTILES as $quantile) {
            $price = (float) self::activeProducts()
                ->orderBy('products.price')
                ->offset((int) floor($quantile * $total))
                ->limit(1)
                ->value('products.price');

            if ($price > 0) {
                $cuts[] = self::niceRound($price);
            }
        }

        // Two quartiles that round to the same figure are one cut: a catalogue
        // priced tightly enough gets fewer bands rather than repeated ones.
        $cuts = array_values(array_unique($cuts));
        sort($cuts);

        if ($cuts === []) {
            return [];
        }

        $counts = self::countPerBand($cuts);
        $bands = [];

        foreach (range(0, count($cuts)) as $i) {
            $count = (int) ($counts[$i] ?? $counts[(string) $i] ?? 0);

            if ($count === 0) {
                continue; // an empty band is a chip that returns nothing
            }

            $min = $i === 0 ? null : $cuts[$i - 1];
            $max = $i === count($cuts) ? null : $cuts[$i];

            $bands[self::priceKey($min, $max)] = [
                'label' => self::priceLabel($min, $max),
                'count' => $count,
                'hex' => null,
                'query' => self::priceQuery($min, $max),
            ];
        }

        return count($bands) > 1 ? $bands : [];
    }

    /**
     * How many active products fall in each band, in one grouped query.
     *
     * The CASE is built from the cut count, and every bound is a placeholder -
     * the figures come from the catalogue, but they reach SQL as parameters
     * like everything else.
     *
     * @param  array<int, float>  $cuts
     * @return Collection<int|string, int>
     */
    private static function countPerBand(array $cuts): Collection
    {
        $case = '';
        $bindings = [];

        foreach ($cuts as $i => $cut) {
            $case .= 'WHEN products.price < ? THEN '.$i.' ';
            $bindings[] = $cut;
        }

        return self::activeProducts()
            ->selectRaw('CASE '.$case.'ELSE '.count($cuts).' END as band, COUNT(*) as aggregate', $bindings)
            ->groupBy('band')
            ->pluck('aggregate', 'band');
    }

    /** A band's identity: "0:1000", "1000:2000", "7000:". */
    public static function priceKey(?float $min, ?float $max): string
    {
        return ($min === null ? '0' : self::plain($min)).':'.($max === null ? '' : self::plain($max));
    }

    public static function priceQuery(?float $min, ?float $max): string
    {
        $parts = [];

        if ($min !== null && $min > 0) {
            $parts[] = 'price_min='.self::plain($min);
        }

        if ($max !== null) {
            $parts[] = 'price_max='.self::plain($max);
        }

        return implode('&', $parts);
    }

    public static function priceLabel(?float $min, ?float $max): string
    {
        if ($min === null || $min <= 0) {
            return 'Under ₹'.self::compact((float) $max);
        }

        if ($max === null) {
            return '₹'.self::compact($min).'+';
        }

        return '₹'.self::compact($min).' - '.self::compact($max);
    }

    /**
     * The next round figure at or above this one: 1, 2, 2.5 or 5 times a power
     * of ten. A band boundary a shopper reads as a boundary - 5,000, not 4,873.
     */
    private static function niceRound(float $value): float
    {
        if ($value <= 0) {
            return 0.0;
        }

        $magnitude = 10 ** floor(log10($value));
        $fraction = $value / $magnitude;

        foreach ([1, 2, 2.5, 5] as $multiple) {
            if ($fraction <= $multiple) {
                return $multiple * $magnitude;
            }
        }

        return 10 * $magnitude;
    }

    /** The number as it goes into a URL - no separators, no decimals. */
    private static function plain(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /** "1k", "2.5k", "950" - the shorthand the rail already used. */
    private static function compact(float $value): string
    {
        if ($value >= 1000) {
            $thousands = $value / 1000;

            return rtrim(rtrim(number_format($thousands, 1, '.', ''), '0'), '.').'k';
        }

        return number_format($value, 0);
    }
}
