<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopFilterItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The "Shop It Your Way" hangers, checked against the catalogue before they
 * are hung.
 *
 * Each hanger carries a query string an admin typed by hand (size=M,
 * shade=Tan, price_min=7000) and links straight into /shop with it. Nothing
 * ever checked that the shop had anything to show for it, so a hanger naming a
 * size the shop does not stock - or a plain typo, ?size=cd - was a promoted
 * dead end onto "No products found". On the live site that was six of the
 * eight size hangers and all six shade hangers: the rail's whole job is to
 * open a listing, and most of it opened nothing.
 *
 * The count is reported where the hanger is edited rather than used to hide
 * it. Hiding the empty ones was tried and taken back out: it emptied the Shade
 * tab off the live storefront outright, and a rail vanishing on its own is
 * harder to understand - and impossible to fix from the admin - than one that
 * opens a listing with nothing on it. So Homepage > Shop Filters prints what
 * each hanger currently matches, flags a query the shop does not read at all,
 * and offers the sizes and shades the catalogue really carries; the storefront
 * hangs whatever is active.
 */
class ShopFilterTiles
{
    /**
     * How many products each hanger opens, keyed by id. Null where a hanger has
     * no query string at all: that one is a plain tile linking nowhere, which
     * is a choice rather than a dead end.
     *
     * @param  Collection<int, ShopFilterItem>  $items
     * @return array<int, int|null>
     */
    public static function counts(Collection $items): array
    {
        $counts = [];
        $seen = [];

        foreach ($items as $item) {
            $query = trim((string) $item->query_string);

            // Two hangers on the same query string are one lookup, not two -
            // the Size and Shade rails often repeat a bound between them.
            if (! array_key_exists($query, $seen)) {
                $seen[$query] = self::countFor($query);
            }

            $counts[$item->id] = $seen[$query];
        }

        return $counts;
    }

    /** How many products /shop?<query> comes back with. Null if there is no query. */
    public static function countFor(?string $queryString): ?int
    {
        $queryString = trim((string) $queryString);

        if ($queryString === '') {
            return null;
        }

        parse_str(ltrim($queryString, '?&'), $params);

        if ($params === []) {
            return null;
        }

        // Counted through the shop's own filter stack rather than a hand-rolled
        // query - aliases, validation and all - so the count can never disagree
        // with the page the hanger opens.
        $request = Request::create('/shop', 'GET', $params);
        $request->merge(ProductFilters::tileAliases($request));

        return ProductFilters::for($request, fn () => Product::query()->where('is_active', true))
            ->query()
            ->count();
    }

    /**
     * Keys in a hanger's query string that the shop does not read.
     *
     * `price=0` is not a bound - the shop has no `price` filter - so a hanger
     * carrying it opens the whole catalogue while looking, from the count
     * beside it, entirely healthy. That is not a dead end and the hanger is
     * left hanging, but the admin screen says so: silently doing nothing is
     * the harder half of this bug to see.
     *
     * @return array<int, string>
     */
    public static function unreadKeys(?string $queryString): array
    {
        parse_str(ltrim(trim((string) $queryString), '?&'), $params);

        // Everything a listing understands: the sidebar's own keys, the names
        // the hangers store, and the ordering and page a link may carry.
        $known = array_merge(ProductFilters::KEYS, ['price_min', 'price_max', 'shade', 'sort', 'q', 'page']);

        return array_values(array_diff(array_keys($params), $known));
    }

    /**
     * Query strings known to lead somewhere, offered to the admin screen as a
     * datalist beside the field.
     *
     * "cd" was typed into a live hanger because nothing on that screen ever
     * showed which sizes the shop carries. Price is left out: a bound is a
     * judgement about the catalogue, not a value to be picked out of it.
     *
     * @return array<string, array<int, string>>
     */
    public static function suggestions(): array
    {
        $sizes = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->pluck('name')
            ->map(fn ($n) => ProductVariant::sizeLabel($n))
            ->filter()
            ->unique()
            ->sortBy(fn ($s) => ProductVariant::sizeRank($s))
            // Encoded, because the field only accepts a query string: a size or
            // a shade with a space in it ("UK 7", "Dusty Rose") has to reach it
            // as one.
            ->map(fn ($s) => 'size='.urlencode($s))
            ->values()
            ->all();

        $shades = Product::query()
            ->where('is_active', true)
            ->pluck('attributes')
            ->flatMap(fn ($a) => collect(data_get($a, 'Colours', []))
                ->map(fn ($c) => is_array($c) ? trim((string) ($c['name'] ?? '')) : trim((string) $c)))
            ->filter()
            ->unique()
            ->sort()
            ->map(fn ($s) => 'shade='.urlencode($s))
            ->values()
            ->all();

        return ['size' => $sizes, 'price' => [], 'shade' => $shades];
    }
}
