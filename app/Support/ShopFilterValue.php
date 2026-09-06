<?php

namespace App\Support;

/**
 * One row of a filter rail, derived rather than stored.
 *
 * The property names match the columns the old hand-curated `shop_filter_items`
 * carried - label, shade_hex, query_string - so the home page rail and anything
 * else that reads a filter row keeps working unchanged now that the rows come
 * from the catalogue instead of a table.
 *
 * `image` is the one property here that no product supplies. It is looked up
 * from the Colours / Textures master lists and it is DECORATION ONLY: the
 * value's identity is still its label, the rail still only carries what some
 * product carries, and a value with no master row - or a master row with no
 * picture - is exactly the value it was before the lists existed. Nothing may
 * branch on it beyond how the value is drawn; the moment something filters or
 * groups by it, the picker has quietly become a second source of truth for
 * what the shop sells.
 */
class ShopFilterValue
{
    public function __construct(
        /** size | shade | texture | price */
        public readonly string $type,
        /** Normalised identity: "black" for Black, BLACK and "Black ". */
        public readonly string $key,
        /** The spelling shown to a shopper. */
        public readonly string $label,
        public readonly ?string $shade_hex,
        /** e.g. `size=M`, `texture=Matte`, `price_min=1000&price_max=2000`. */
        public readonly string $query_string,
        /** Active products currently carrying this value. */
        public readonly int $count,
        public readonly bool $hidden,
        /** Set only when hidden: the exclusion row to DELETE to unhide it. */
        public readonly ?string $exclusion_uuid = null,
        /** Master-list artwork for this value, when the admin has uploaded one. */
        public readonly ?string $image = null,
    ) {}

    /** The raw filter value, as it goes into a query string. */
    public function value(): string
    {
        return $this->label;
    }
}
