<?php

namespace Database\Seeders;

use App\Models\Texture;
use App\Support\ShopFilterCatalogue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The texture picker's starting list.
 *
 * Re-runnable and non-destructive, exactly like SizeSeeder and ColourSeeder:
 * the ROW is firstOrCreate, so a re-run never duplicates (the bare create() in
 * BrandSeeder does, and that is a bug rather than the house pattern) and never
 * restores last release's name, order or is_active over an admin's.
 *
 * image_url is the one field backfilled afterwards, and only when NULL - the
 * same carve-out ColourSeeder makes for hex_code, for the same reason. A
 * texture with no tile is the one case where the picker genuinely cannot show
 * what the value means, since there is no hex to fall back on: Matte and Glossy
 * are the same grey and different fabrics. So if a row for a texture we ship a
 * tile for has none, filling it in is strictly an improvement; if an admin has
 * uploaded their own photograph of the actual cloth, it is not null and it
 * stays.
 *
 * The tiles are checked-in SVGs under public/images/textures/ rather than
 * uploads under storage/, because they are shipped assets: they belong to the
 * release, not to the admin's media library, and deploy.sh already syncs
 * public/images to the webroot.
 */
class TextureSeeder extends Seeder
{
    public function run(): void
    {
        $textures = [
            ['name' => 'Smooth', 'description' => 'An even, unbroken surface with no visible weave'],
            ['name' => 'Matte', 'description' => 'A flat finish that scatters light rather than reflecting it'],
            ['name' => 'Glossy', 'description' => 'A polished finish with a bright, hard highlight'],
            ['name' => 'Satin', 'description' => 'A soft directional sheen without a hard shine'],
            ['name' => 'Ribbed', 'description' => 'Raised parallel ribs running the length of the cloth'],
            ['name' => 'Textured', 'description' => 'A pronounced woven grain you can feel'],
            ['name' => 'Velvet', 'description' => 'A dense short pile that shifts colour with the light'],
        ];

        $position = 1;

        foreach ($textures as $texture) {
            // Derived rather than listed so the filename can never drift out of
            // step with the name above; the shipped tiles are named after the
            // lowercased texture and nothing else.
            $image = '/images/textures/'.Str::lower($texture['name']).'.svg';

            $row = Texture::firstOrCreate(
                ['key' => ShopFilterCatalogue::normaliseKey($texture['name'])],
                [
                    'name' => $texture['name'],
                    'image_url' => $image,
                    'description' => $texture['description'],
                    'position' => $position,
                    'is_active' => true,
                ]
            );

            // Only ever fills a hole - see the class docblock.
            if ($row->image_url === null) {
                $row->image_url = $image;
                $row->save();
            }

            $position++;
        }
    }
}
