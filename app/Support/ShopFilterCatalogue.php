<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopFilterExclusion;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
 */
class ShopFilterCatalogue
{
    /** The rails. `shade` is the colour rail - the name the storefront uses. */
    public const TYPES = ['size', 'shade', 'texture', 'price'];

    /** products.attributes keys the two list attributes live under. */
    public const COLOURS_KEY = 'Colours';

    public const TEXTURES_KEY = 'Textures';

    /** How many price bands to aim for before empty ones are dropped. */
    private const PRICE_BANDS = 4;

    /** Container keys the per-request memo is held under. */
    private const MEMO = ['kk.shop_filters.version', 'kk.shop_filters.derived', 'kk.shop_filters.exclusions'];

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
                shade_hex: $row['hex'] ?? null,
                query_string: $row['query'],
                count: $row['count'],
                hidden: $hidden,
                exclusion_uuid: $hidden ? $excluded[$key]['uuid'] : null,
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
                    shade_hex: null,
                    query_string: self::queryFor($type, $row['label'], $key),
                    count: 0,
                    hidden: true,
                    exclusion_uuid: $row['uuid'],
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
     * Price bands across the live spread.
     *
     * Exact prices would be hundreds of one-product filters, so the spread is
     * cut into round bands - the shape the hand-typed rail already used
     * ("Under 1k", "1k - 2k", "7k+"). Two queries, whatever the catalogue
     * size: one for the spread, one grouped count that drops the empty bands.
     * A single band is not a filter, so a catalogue with one price offers no
     * price rail at all rather than one chip that changes nothing.
     *
     * @return array<string, array{label: string, count: int, hex: ?string, query: string}>
     */
    private static function derivePriceBands(): array
    {
        $spread = self::activeProducts()
            ->selectRaw('MIN(products.price) as lo, MAX(products.price) as hi, COUNT(*) as n')
            ->first();

        $high = (float) ($spread->hi ?? 0);
        $total = (int) ($spread->n ?? 0);

        if ($total === 0 || $high <= 0) {
            return [];
        }

        $step = self::niceStep($high / self::PRICE_BANDS);
        // The band the dearest product falls in, capped so the rail never runs
        // past five chips however wide the spread is - the top one is left
        // open-ended and swallows the tail.
        $cut = min((int) floor($high / $step), self::PRICE_BANDS);

        $counts = self::activeProducts()
            ->selectRaw('LEAST(FLOOR(products.price / ?), ?) as band, COUNT(*) as aggregate', [$step, $cut])
            ->groupBy('band')
            ->pluck('aggregate', 'band');

        $bands = [];

        for ($i = 0; $i <= $cut; $i++) {
            $count = (int) ($counts[$i] ?? $counts[(string) $i] ?? 0);

            if ($count === 0) {
                continue; // an empty band is a promoted dead end
            }

            $min = $i === 0 ? null : $i * $step;
            $max = $i === $cut ? null : ($i + 1) * $step;

            $bands[self::priceKey($min, $max)] = [
                'label' => self::priceLabel($min, $max),
                'count' => $count,
                'hex' => null,
                'query' => self::priceQuery($min, $max),
            ];
        }

        return count($bands) > 1 ? $bands : [];
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

    /** A round step: 1, 2, 2.5 or 5 times a power of ten. */
    private static function niceStep(float $raw): float
    {
        if ($raw <= 0) {
            return 1.0;
        }

        $magnitude = 10 ** floor(log10($raw));
        $fraction = $raw / $magnitude;

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
