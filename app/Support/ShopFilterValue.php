<?php

namespace App\Support;

/**
 * One row of a filter rail, derived rather than stored.
 *
 * The property names match the columns the old hand-curated `shop_filter_items`
 * carried - label, shade_hex, query_string - so the home page rail and anything
 * else that reads a filter row keeps working unchanged now that the rows come
 * from the catalogue instead of a table.
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
    ) {}

    /** The raw filter value, as it goes into a query string. */
    public function value(): string
    {
        return $this->label;
    }
}
