<?php

namespace Database\Seeders;

use App\Models\Quality;
use App\Models\ShopFilterItem;
use Illuminate\Database\Seeder;

/**
 * Seeds the default "Shop It Your Way" filter rails (Size / Price / Shade)
 * and the six "Our Qualities" cards. Idempotent - re-running just no-ops on
 * existing rows (matched by type + label / by title).
 */
class KarmaaHomepageSeeder extends Seeder
{
    public function run(): void
    {
        $filters = [
            // Size
            ['type' => 'size', 'label' => 'S',   'sub_label' => '120 Styles', 'shade_hex' => '#f0d9b8', 'query_string' => 'size=S',   'position' => 1],
            ['type' => 'size', 'label' => 'M',   'sub_label' => '210 Styles', 'shade_hex' => '#d9b58a', 'query_string' => 'size=M',   'position' => 2],
            ['type' => 'size', 'label' => 'L',   'sub_label' => '185 Styles', 'shade_hex' => '#b8895a', 'query_string' => 'size=L',   'position' => 3],
            ['type' => 'size', 'label' => 'XL',  'sub_label' => '140 Styles', 'shade_hex' => '#8c5c34', 'query_string' => 'size=XL',  'position' => 4],
            ['type' => 'size', 'label' => 'XXL', 'sub_label' => '85 Styles',  'shade_hex' => '#5a3a22', 'query_string' => 'size=XXL', 'position' => 5],
            ['type' => 'size', 'label' => '3XL', 'sub_label' => '42 Styles',  'shade_hex' => '#2d1810', 'query_string' => 'size=3XL', 'position' => 6],

            // Price
            ['type' => 'price', 'label' => 'Under ₹1k', 'sub_label' => '60 Styles',  'shade_hex' => '#c9986a', 'query_string' => 'price_max=1000', 'position' => 1],
            ['type' => 'price', 'label' => '₹1k - 2k',  'sub_label' => '95 Styles',  'shade_hex' => '#b8895a', 'query_string' => 'price_min=1000&price_max=2000', 'position' => 2],
            ['type' => 'price', 'label' => '₹2k - 3k',  'sub_label' => '145 Styles', 'shade_hex' => '#a07748', 'query_string' => 'price_min=2000&price_max=3000', 'position' => 3],
            ['type' => 'price', 'label' => '₹3k - 5k',  'sub_label' => '110 Styles', 'shade_hex' => '#8c5c34', 'query_string' => 'price_min=3000&price_max=5000', 'position' => 4],
            ['type' => 'price', 'label' => '₹5k - 7k',  'sub_label' => '70 Styles',  'shade_hex' => '#6e4527', 'query_string' => 'price_min=5000&price_max=7000', 'position' => 5],
            ['type' => 'price', 'label' => '₹7k+',      'sub_label' => '50 Styles',  'shade_hex' => '#4a2d1a', 'query_string' => 'price_min=7000', 'position' => 6],

            // Shade
            ['type' => 'shade', 'label' => 'Cream',    'sub_label' => '88 Styles',  'shade_hex' => '#efe2cb', 'query_string' => 'shade=cream',    'position' => 1],
            ['type' => 'shade', 'label' => 'Sand',     'sub_label' => '120 Styles', 'shade_hex' => '#d4b896', 'query_string' => 'shade=sand',     'position' => 2],
            ['type' => 'shade', 'label' => 'Tan',      'sub_label' => '95 Styles',  'shade_hex' => '#b8895a', 'query_string' => 'shade=tan',      'position' => 3],
            ['type' => 'shade', 'label' => 'Cinnamon', 'sub_label' => '110 Styles', 'shade_hex' => '#8c5c34', 'query_string' => 'shade=cinnamon', 'position' => 4],
            ['type' => 'shade', 'label' => 'Cocoa',    'sub_label' => '70 Styles',  'shade_hex' => '#5a3a22', 'query_string' => 'shade=cocoa',    'position' => 5],
            ['type' => 'shade', 'label' => 'Espresso', 'sub_label' => '45 Styles',  'shade_hex' => '#2d1810', 'query_string' => 'shade=espresso', 'position' => 6],
        ];

        foreach ($filters as $f) {
            ShopFilterItem::updateOrCreate(
                ['type' => $f['type'], 'label' => $f['label']],
                $f + ['is_active' => true]
            );
        }

        $qualities = [
            ['title' => 'Premium Fabrics',        'description' => 'BCI-certified cottons, tencel blends and long-staple linens - sourced from mills we know by name.'],
            ['title' => 'Hand-Finished Detailing','description' => "Seams, buttonholes and hems hand-inspected. If it doesn't pass our table, it doesn't ship."],
            ['title' => 'Precision Tailoring',    'description' => 'Pattern graded across six sizes so the drape holds true - from the shoulder line to the hem break.'],
            ['title' => 'Ethical Production',     'description' => 'Fair-wage partners, regular audits, and transparency reports published twice a year.'],
            ['title' => 'Wash-Tested For Life',   'description' => 'Every fabric survives 50+ wash cycles in our lab before it makes the cut - colour-fast, shape-true.'],
            ['title' => 'Lifetime Mend Promise',  'description' => "Broken stitch? Lost button? We'll fix it on us. Because good clothes deserve long lives."],
        ];

        foreach ($qualities as $i => $q) {
            Quality::updateOrCreate(
                ['title' => $q['title']],
                $q + ['position' => $i + 1, 'is_active' => true]
            );
        }

        $this->command->info(sprintf(
            'KK homepage seeded: %d filter items, %d qualities.',
            ShopFilterItem::count(),
            Quality::count()
        ));
    }
}
