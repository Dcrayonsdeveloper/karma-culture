<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

class BeautyAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            [
                'name' => 'Skin Type',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => ['Normal', 'Dry', 'Oily', 'Combination', 'Sensitive', 'All Skin Types'],
            ],
            [
                'name' => 'Shade',
                'type' => 'color',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => [
                    ['value' => 'Fair Ivory', 'color_code' => '#FDEBD0'],
                    ['value' => 'Light Beige', 'color_code' => '#F5CBA7'],
                    ['value' => 'Natural Beige', 'color_code' => '#E8B88A'],
                    ['value' => 'Warm Sand', 'color_code' => '#D4A574'],
                    ['value' => 'Golden Tan', 'color_code' => '#C49A6C'],
                    ['value' => 'Caramel', 'color_code' => '#A67B5B'],
                    ['value' => 'Mocha', 'color_code' => '#8B6F47'],
                    ['value' => 'Espresso', 'color_code' => '#6B4226'],
                    ['value' => 'Deep Mahogany', 'color_code' => '#4A2C2A'],
                    ['value' => 'Rich Ebony', 'color_code' => '#3B1F1F'],
                ],
            ],
            [
                'name' => 'Size / Volume',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => ['5ml', '10ml', '15ml', '30ml', '50ml', '75ml', '100ml', '150ml', '200ml', '250ml', '500ml'],
            ],
            [
                'name' => 'Finish',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => ['Matte', 'Glossy', 'Satin', 'Dewy', 'Shimmer', 'Metallic', 'Velvet', 'Natural'],
            ],
            [
                'name' => 'Coverage',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => ['Sheer', 'Light', 'Medium', 'Full', 'Buildable'],
            ],
            [
                'name' => 'SPF',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => ['SPF 15', 'SPF 20', 'SPF 25', 'SPF 30', 'SPF 40', 'SPF 50', 'SPF 50+', 'No SPF'],
            ],
            [
                'name' => 'Lip Color',
                'type' => 'color',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => [
                    ['value' => 'Nude Pink', 'color_code' => '#E8B4B8'],
                    ['value' => 'Rose', 'color_code' => '#C77B8B'],
                    ['value' => 'Berry', 'color_code' => '#8E3A59'],
                    ['value' => 'Red', 'color_code' => '#C0392B'],
                    ['value' => 'Coral', 'color_code' => '#E67E51'],
                    ['value' => 'Mauve', 'color_code' => '#9B7EA3'],
                    ['value' => 'Plum', 'color_code' => '#6C3461'],
                    ['value' => 'Wine', 'color_code' => '#722F37'],
                    ['value' => 'Peach', 'color_code' => '#FFDAB9'],
                    ['value' => 'Brown Nude', 'color_code' => '#A0785A'],
                ],
            ],
            [
                'name' => 'Concern',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => ['Anti-Aging', 'Acne & Blemishes', 'Hydration', 'Brightening', 'Pore Care', 'Dark Spots', 'Fine Lines', 'Dullness', 'Redness', 'Sun Protection', 'Dark Circles'],
            ],
            [
                'name' => 'Hair Type',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => ['Straight', 'Wavy', 'Curly', 'Coily', 'Fine', 'Thick', 'Normal', 'All Hair Types'],
            ],
            [
                'name' => 'Ingredient Highlight',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => ['Vitamin C', 'Hyaluronic Acid', 'Retinol', 'Niacinamide', 'Salicylic Acid', 'Glycolic Acid', 'Aloe Vera', 'Tea Tree', 'Collagen', 'Argan Oil', 'Shea Butter', 'Ceramides'],
            ],
            [
                'name' => 'Scent',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => ['Floral', 'Fruity', 'Woody', 'Fresh', 'Citrus', 'Oriental', 'Musky', 'Aquatic', 'Unscented'],
            ],
            [
                'name' => 'Product Form',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => ['Cream', 'Gel', 'Serum', 'Oil', 'Lotion', 'Mousse', 'Spray', 'Powder', 'Balm', 'Stick', 'Sheet Mask', 'Foam'],
            ],
            [
                'name' => 'Weight',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => ['5g', '10g', '15g', '25g', '30g', '50g', '75g', '100g', '150g', '200g', '250g', '500g'],
            ],
            [
                'name' => 'Certification',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'values' => ['Vegan', 'Cruelty-Free', 'Organic', 'Paraben-Free', 'Sulfate-Free', 'Dermatologically Tested', 'Hypoallergenic', 'Non-Comedogenic'],
            ],
        ];

        foreach ($attributes as $attrData) {
            $values = $attrData['values'];
            unset($attrData['values']);

            $attribute = Attribute::firstOrCreate(
                ['name' => $attrData['name']],
                $attrData
            );

            foreach ($values as $position => $value) {
                if (is_array($value)) {
                    AttributeValue::firstOrCreate(
                        ['attribute_id' => $attribute->id, 'value' => $value['value']],
                        ['color_code' => $value['color_code'], 'position' => $position]
                    );
                } else {
                    AttributeValue::firstOrCreate(
                        ['attribute_id' => $attribute->id, 'value' => $value],
                        ['position' => $position]
                    );
                }
            }
        }
    }
}
