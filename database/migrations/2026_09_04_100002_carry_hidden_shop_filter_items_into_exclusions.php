<?php

use App\Support\ShopFilterCatalogue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Carry the admin's existing "hidden" decisions onto the derived rails.
     *
     * The "Shop It Your Way" rails used to be a hand-typed list of hangers in
     * `shop_filter_items`; they are now worked out from the catalogue. WHICH
     * values exist is no longer stored, so those rows are obsolete as a list -
     * but one thing in them is not derivable and must not be lost: a row an
     * admin had switched OFF is a decision that a shopper should not be
     * offered that value. Each of those becomes an exclusion.
     *
     * Nothing is deleted. The old table and every row in it are left exactly
     * as they are, so this is reversible by dropping the exclusions table and
     * nothing else - and so that a value that turns out to have been switched
     * off for a reason nobody remembers can still be read back out of it.
     *
     * Active rows are ignored on purpose: they said "show this", and a derived
     * rail already shows every value the catalogue carries.
     */
    public function up(): void
    {
        if (! Schema::hasTable('shop_filter_items') || ! Schema::hasTable('shop_filter_exclusions')) {
            return;
        }

        $now = now();
        $rows = [];

        foreach (DB::table('shop_filter_items')->where('is_active', false)->get() as $item) {
            $hidden = self::hiddenValue((string) ($item->query_string ?? ''), (string) ($item->label ?? ''));

            if ($hidden === null) {
                continue;
            }

            [$type, $key, $label] = $hidden;

            // Keyed by type+value so two switched-off hangers naming the same
            // value - the live table has duplicates - insert one row, not two.
            $rows[$type.'|'.$key] = [
                'uuid' => (string) Str::uuid7(),
                'type' => $type,
                'value_key' => $key,
                'label' => $label,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            // insertOrIgnore rather than insert: the unique index is the point,
            // and a re-run of a partially applied migration must not fail.
            DB::table('shop_filter_exclusions')->insertOrIgnore(array_values($rows));
        }
    }

    public function down(): void
    {
        // The exclusions table is dropped by its own migration; there is
        // nothing to undo here, and deleting rows would throw away decisions
        // an admin may have made since.
    }

    /**
     * Read a hanger's query string as one filter value.
     *
     * @return array{0: string, 1: string, 2: string}|null [type, value key, label]
     */
    private static function hiddenValue(string $queryString, string $label): ?array
    {
        parse_str(ltrim(trim($queryString), '?&'), $params);

        $first = function (string $key) use ($params): ?string {
            $value = $params[$key] ?? null;
            $value = is_array($value) ? ($value[0] ?? null) : $value;

            return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
        };

        foreach (['size' => 'size', 'shade' => 'shade', 'colour' => 'shade', 'texture' => 'texture'] as $param => $type) {
            if (($value = $first($param)) !== null) {
                return [$type, ShopFilterCatalogue::normaliseKey($value), $value];
            }
        }

        $min = $first('price_min') ?? $first('min_price');
        $max = $first('price_max') ?? $first('max_price');

        if ($min !== null || $max !== null) {
            $key = ShopFilterCatalogue::priceKey(
                $min === null ? null : (float) $min,
                $max === null ? null : (float) $max,
            );

            return ['price', $key, $label !== '' ? $label : $key];
        }

        // A hanger with no readable bound named no value, so there is nothing
        // for a shopper to be shown or not shown. Nothing to carry over.
        return null;
    }
};
