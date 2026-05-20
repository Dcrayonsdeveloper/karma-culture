<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KarmaaCategorySeeder extends Seeder
{
    /**
     * Build the Karmaa Kulture category tree:
     *   Men's
     *     ├─ T-Shirts → Polo, Round Neck, Henley/Wide Neck, Mandarin, Oversized
     *     ├─ Shirts   → Slim Fit, Regular, Denims
     *     ├─ Kurtas   → Short, Knee Length
     *     └─ Trousers
     *   Women's
     *     ├─ Tops
     *     ├─ T-Shirts
     *     ├─ Jumpsuits
     *     ├─ Co-ord Sets
     *     ├─ Kurtas
     *     ├─ One Piece
     *     └─ Trousers
     */
    public function run(): void
    {
        $tree = [
            "Men's" => [
                'T-Shirts'  => ['Polo', 'Round Neck', 'Henley/Wide Neck', 'Mandarin', 'Oversized'],
                'Shirts'    => ['Slim Fit', 'Regular', 'Denims'],
                'Kurtas'    => ['Short', 'Knee Length'],
                'Trousers'  => [],
            ],
            "Women's" => [
                'Tops'         => [],
                'T-Shirts'     => [],
                'Jumpsuits'    => [],
                'Co-ord Sets'  => [],
                'Kurtas'       => [],
                'One Piece' => [],
                'Trousers'     => [],
            ],
        ];

        $rootPos = 1;

        foreach ($tree as $rootName => $children) {
            $rootSlug = Str::slug($rootName);
            $root = Category::updateOrCreate(
                ['slug' => $rootSlug],
                [
                    'parent_id'  => null,
                    'name'       => $rootName,
                    'description'=> "Shop {$rootName} collection at Karmaa Kulture.",
                    'level'      => 0,
                    'path'       => $rootSlug,
                    'position'   => $rootPos++,
                    'is_active'  => true,
                    'is_featured'=> true,
                ]
            );

            $catPos = 1;
            foreach ($children as $catName => $subs) {
                $catSlug = Str::slug($rootName . ' ' . $catName);
                $cat = Category::updateOrCreate(
                    ['slug' => $catSlug],
                    [
                        'parent_id'  => $root->id,
                        'name'       => $catName,
                        'description'=> "{$catName} for {$rootName} — Karmaa Kulture.",
                        'level'      => 1,
                        'path'       => $rootSlug . '/' . Str::slug($catName),
                        'position'   => $catPos++,
                        'is_active'  => true,
                    ]
                );

                $subPos = 1;
                foreach ($subs as $subName) {
                    $subSlug = Str::slug($rootName . ' ' . $catName . ' ' . $subName);
                    Category::updateOrCreate(
                        ['slug' => $subSlug],
                        [
                            'parent_id'  => $cat->id,
                            'name'       => $subName,
                            'description'=> "{$catName} - {$subName} for {$rootName}.",
                            'level'      => 2,
                            'path'       => $rootSlug . '/' . Str::slug($catName) . '/' . Str::slug($subName),
                            'position'   => $subPos++,
                            'is_active'  => true,
                        ]
                    );
                }
            }
        }

        $this->command->info('Karmaa Kulture categories seeded: '
            . Category::count() . ' total rows (including pre-existing).');
    }
}
