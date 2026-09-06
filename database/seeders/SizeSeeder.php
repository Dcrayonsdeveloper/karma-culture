<?php

namespace Database\Seeders;

use App\Models\Size;
use App\Support\ShopFilterCatalogue;
use Illuminate\Database\Seeder;

/**
 * The size picker's starting list.
 *
 * Two rules this seeder has to keep, because it runs again on every deploy that
 * seeds and DatabaseSeeder calls it unconditionally:
 *
 *  1. Re-running it must not duplicate. BrandSeeder uses a bare create() and
 *     happily writes a second Apple on a second run - that is a known bug in
 *     that file, not a pattern to copy. Here the row is matched on `key`, the
 *     normalised name, which is also the table's unique column.
 *
 *  2. Re-running it must not undo an admin. Everything below is a DEFAULT, not
 *     a setting: if somebody has renamed "3XL" to "XXXL", reordered the list,
 *     or deactivated a size the shop stopped carrying, that is the current
 *     truth and the seeder has no business restoring last release's opinion
 *     over it. Hence firstOrCreate rather than updateOrCreate - the attributes
 *     are only ever used to CREATE a missing row.
 *
 * Order is a shopper's order, not the alphabet's, which is why position is
 * assigned by array order rather than sorted: XS through 3XL is the sequence
 * anybody scanning a size list expects to read.
 */
class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            ['name' => 'XS', 'description' => 'Extra small'],
            ['name' => 'S', 'description' => 'Small'],
            ['name' => 'M', 'description' => 'Medium'],
            ['name' => 'L', 'description' => 'Large'],
            ['name' => 'XL', 'description' => 'Extra large'],
            ['name' => 'XXL', 'description' => 'Double extra large'],
            ['name' => '3XL', 'description' => 'Triple extra large'],
        ];

        $position = 1;

        foreach ($sizes as $size) {
            Size::firstOrCreate(
                // Matched on the normalised name and not on `name`, so a run
                // after an admin has retyped "m" as "M" finds the existing row
                // instead of colliding with the unique index on `key`.
                ['key' => ShopFilterCatalogue::normaliseKey($size['name'])],
                [
                    'name' => $size['name'],
                    'description' => $size['description'],
                    'position' => $position,
                    'is_active' => true,
                ]
            );

            $position++;
        }
    }
}
