<?php

namespace Database\Seeders;

use App\Models\Colour;
use App\Support\ShopFilterCatalogue;
use Illuminate\Database\Seeder;

/**
 * The colour picker's starting list.
 *
 * Re-runnable and non-destructive, for the same two reasons SizeSeeder is:
 * DatabaseSeeder calls it on every seeding run, and a re-run that duplicated
 * rows (BrandSeeder's bare create() bug) or reset an admin's edits would be
 * worse than not seeding at all.
 *
 * The split is deliberate and worth stating, because "just use updateOrCreate"
 * is the obvious tidy-up and it is wrong here:
 *
 *   - The ROW is firstOrCreate. Name, description, position and is_active are
 *     first-release defaults. Once the row exists, the admin's version of all
 *     four is the truth - reordering the list or deactivating Olive is a
 *     decision, and a deploy must not quietly undo it.
 *
 *   - hex_code is the ONE field backfilled afterwards, and only when it is
 *     NULL. A colour created by hand in the admin (or carried over from an
 *     older list) can sit there with no swatch and render as the grey
 *     placeholder forever; if we ship a hex for that name, filling the gap is
 *     strictly an improvement. An admin who has SET a hex - tuned Navy to match
 *     the actual dye lot - keeps it, because it is not null.
 *
 * image_url stays null across the board: the hex is the swatch, and a fabric
 * photo is something an admin uploads when a flat block of colour misleads.
 */
class ColourSeeder extends Seeder
{
    public function run(): void
    {
        $colours = [
            ['name' => 'Black', 'hex' => '#111111', 'description' => 'A deep, near-neutral black'],
            ['name' => 'White', 'hex' => '#FFFFFF', 'description' => 'Clean optical white'],
            ['name' => 'Ivory', 'hex' => '#F3EDE3', 'description' => 'Warm off-white with a soft cream cast'],
            ['name' => 'Navy', 'hex' => '#1F2A44', 'description' => 'Dark, slightly muted blue'],
            ['name' => 'Olive', 'hex' => '#6B7043', 'description' => 'Earthy green with a khaki base'],
            ['name' => 'Tan', 'hex' => '#C2A17B', 'description' => 'Mid sandy brown'],
            ['name' => 'Maroon', 'hex' => '#6E2233', 'description' => 'Deep wine red'],
        ];

        $position = 1;

        foreach ($colours as $colour) {
            $row = Colour::firstOrCreate(
                // Matched on the normalised name, which is the table's unique
                // column - so a run after somebody typed "black" finds that row
                // rather than failing on the unique index.
                ['key' => ShopFilterCatalogue::normaliseKey($colour['name'])],
                [
                    'name' => $colour['name'],
                    'hex_code' => $colour['hex'],
                    'image_url' => null,
                    'description' => $colour['description'],
                    'position' => $position,
                    'is_active' => true,
                ]
            );

            // Only ever fills a hole. See the class docblock: a null hex draws
            // the grey placeholder swatch, and a colour we know the hex for
            // should not keep drawing it.
            if ($row->hex_code === null) {
                $row->hex_code = $colour['hex'];
                $row->save();
            }

            $position++;
        }
    }
}
