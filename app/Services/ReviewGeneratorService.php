<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;

class ReviewGeneratorService
{
    public function generateForOrderItem(OrderItem $orderItem): Review
    {
        $product = $orderItem->product;
        $order = $orderItem->order;
        $user = $order->user;
        $rating = $this->pickRating();

        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_item_id' => $orderItem->id,
            'rating' => $rating,
            'title' => $this->generateTitle($product, $rating),
            'content' => $this->generateContent($product, $rating),
            'pros' => $this->generatePros($product, $rating),
            'cons' => $this->generateCons($product, $rating),
            'is_verified_purchase' => true,
            'is_approved' => true,
            'status' => 'approved',
            'is_generated' => true,
            'generated_from_order_item_id' => $orderItem->id,
            'helpful_count' => rand(0, 8),
            'created_at' => $this->randomTimestampToday(),
        ]);

        return $review;
    }

    public function pickRating(): int
    {
        $rand = mt_rand(1, 100);

        return match (true) {
            $rand <= 1 => 1,    // 1%
            $rand <= 5 => 2,    // 4%
            $rand <= 20 => 3,   // 15%
            $rand <= 55 => 4,   // 35%
            default => 5,       // 45%
        };
    }

    public function generateTitle(Product $product, int $rating): string
    {
        $categorySlug = $product->category?->slug ?? '';
        $productType = $this->detectProductType($product->name, $categorySlug);
        $childTerm = $this->randomChildTerm();

        $templates = $this->getTitleTemplates($rating);
        $template = $templates[array_rand($templates)];

        return $this->fillPlaceholders($template, $product, $productType, $childTerm);
    }

    public function generateContent(Product $product, int $rating): string
    {
        $categorySlug = $product->category?->slug ?? '';
        $productType = $this->detectProductType($product->name, $categorySlug);
        $childTerm = $this->randomChildTerm();
        $timeframe = $this->randomTimeframe();

        $templates = $this->getContentTemplates($rating);
        $template = $templates[array_rand($templates)];

        return $this->fillPlaceholders($template, $product, $productType, $childTerm, $timeframe);
    }

    public function generatePros(Product $product, int $rating): array
    {
        $categorySlug = $product->category?->slug ?? '';
        $pool = $this->getProsPool($categorySlug);
        $count = match (true) {
            $rating >= 5 => rand(3, 4),
            $rating >= 4 => rand(2, 3),
            $rating >= 3 => rand(1, 2),
            default => rand(0, 1),
        };

        shuffle($pool);
        return array_slice($pool, 0, $count);
    }

    public function generateCons(Product $product, int $rating): array
    {
        if ($rating >= 5 && rand(1, 3) > 1) {
            return [];
        }

        $pool = $this->getConsPool();
        $count = match (true) {
            $rating <= 2 => rand(2, 3),
            $rating <= 3 => rand(1, 2),
            $rating <= 4 => rand(0, 1),
            default => 0,
        };

        shuffle($pool);
        return array_slice($pool, 0, $count);
    }

    private function randomTimestampToday(): \Carbon\Carbon
    {
        $hour = rand(6, 22);
        $minute = rand(0, 59);
        $second = rand(0, 59);

        return now()->setTime($hour, $minute, $second);
    }

    private function randomChildTerm(): string
    {
        $terms = ['son', 'daughter', 'kid', 'little one', 'child', 'toddler', 'baby', 'kiddo'];
        return $terms[array_rand($terms)];
    }

    private function randomTimeframe(): string
    {
        $frames = ['a few days', 'a week', 'about two weeks', 'a couple of weeks', 'some time now', 'a month'];
        return $frames[array_rand($frames)];
    }

    private function detectProductType(string $productName, string $categorySlug): string
    {
        $name = strtolower($productName . ' ' . $categorySlug);

        if (preg_match('/dress|frock|gown|lehenga/', $name)) return 'dress';
        if (preg_match('/shirt|t-shirt|tee|top/', $name)) return 'top';
        if (preg_match('/pant|jeans|trouser|legging|shorts/', $name)) return 'bottom';
        if (preg_match('/shoe|sandal|slipper|boot/', $name)) return 'shoes';
        if (preg_match('/toy|puzzle|game|block/', $name)) return 'toy';
        if (preg_match('/bag|backpack/', $name)) return 'bag';
        if (preg_match('/set|combo|outfit/', $name)) return 'set';
        if (preg_match('/jacket|sweater|hoodie|coat/', $name)) return 'winter wear';
        if (preg_match('/sock|underwear|innerwear/', $name)) return 'innerwear';
        if (preg_match('/accessory|bow|clip|band|watch/', $name)) return 'accessory';

        return 'product';
    }

    private function fillPlaceholders(string $template, Product $product, string $productType, string $childTerm, string $timeframe = ''): string
    {
        $shortName = strlen($product->name) > 40 ? substr($product->name, 0, 37) . '...' : $product->name;

        return str_replace(
            ['{product_name}', '{product_type}', '{child_term}', '{timeframe}', '{brand}'],
            [$shortName, $productType, $childTerm, $timeframe, $product->brand?->name ?? 'this brand'],
            $template
        );
    }

    // ─── Title Templates ─────────────────────────────────────

    private function getTitleTemplates(int $rating): array
    {
        return match (true) {
            $rating >= 5 => [
                'Absolutely love this {product_type}!',
                'Perfect for my {child_term}',
                'Exceeded all my expectations',
                'Best purchase this month!',
                'My {child_term} loves it!',
                'Amazing quality {product_type}',
                'Worth every rupee',
                'Highly recommend this',
                'Fantastic {product_type}!',
                'So happy with this purchase',
                'Great buy for my {child_term}',
                'Beautiful {product_type}, very pleased',
                'Wonderful quality and design',
                'My {child_term} won\'t stop wearing this',
                'Outstanding {product_type}!',
                'Must buy! Really impressed',
                'Exactly what I was looking for',
                'Superb quality {product_type}',
                'Delighted with this purchase',
                'Perfect gift for my {child_term}',
                'Simply the best {product_type}',
                'Brilliant quality and finish',
                '5 stars all the way!',
                'Very happy customer!',
                'Couldn\'t be happier with this',
                'My {child_term} absolutely adores this',
                'Great value for money',
                'Just what we needed!',
                'Top quality {product_type}',
                'Impressed with the quality',
            ],
            $rating >= 4 => [
                'Good quality {product_type}',
                'Happy with this purchase',
                'Nice {product_type}, minor things',
                'Pretty good for the price',
                'Solid {product_type}, would recommend',
                'My {child_term} likes it a lot',
                'Good value, decent quality',
                'Satisfied with this {product_type}',
                'Mostly impressed, small issue',
                'Nice design and comfortable',
                'Good {product_type}, fits well',
                'My {child_term} enjoys this',
                'Decent quality, looks great',
                'Happy overall with this',
                'Good product, quick delivery',
                'Like it, but small improvements needed',
                'A solid purchase overall',
                'My {child_term} wears it often',
                'Quite nice {product_type}',
                'Pleased with the quality',
                'Good for everyday use',
                'Nice and comfortable',
                'Reasonably good {product_type}',
                'Better than expected',
                'Would buy again',
            ],
            $rating >= 3 => [
                'Decent but could be better',
                'OK for the price',
                'Average {product_type}',
                'It\'s alright, nothing special',
                'Mixed feelings about this',
                'Some pros and cons',
                'Fair quality for the price',
                'Acceptable {product_type}',
                'Not bad, not great either',
                'Meets basic expectations',
                'Could use some improvements',
                'Average quality {product_type}',
                'It\'s okay for what it costs',
                'Somewhat satisfied',
                'Decent product overall',
            ],
            $rating >= 2 => [
                'Disappointed with the quality',
                'Not as expected',
                'Would not buy again',
                'Below average {product_type}',
                'Not worth the price',
                'Quality could be much better',
                'Expected better for the price',
                'Underwhelming {product_type}',
                'Not satisfied',
                'Did not meet expectations',
            ],
            default => [
                'Very disappointing',
                'Not recommended',
                'Poor quality {product_type}',
                'Waste of money',
                'Would not recommend',
                'Extremely disappointed',
                'Bad quality overall',
            ],
        };
    }

    // ─── Content Templates ───────────────────────────────────

    private function getContentTemplates(int $rating): array
    {
        return match (true) {
            $rating >= 5 => [
                'Bought this {product_type} for my {child_term} and it turned out to be a great purchase. The quality is excellent and the material feels premium. Really happy with how it looks and fits.',
                'I\'ve been looking for a good {product_type} for {timeframe} and finally found this one. My {child_term} absolutely loves it. The colors are vibrant and haven\'t faded after washing.',
                'This {product_type} is amazing! My {child_term} has been using it for {timeframe} now and it still looks brand new. Definitely worth the money.',
                'Ordered this for my {child_term}\'s birthday and it was a hit! The quality exceeded my expectations. Packaging was also very neat and presentable.',
                'Excellent product! The {product_type} fits perfectly and my {child_term} is very comfortable wearing it. The stitching is neat and the fabric is soft. Will definitely order more from here.',
                'Very impressed with this {product_type}. It\'s exactly as shown in the pictures. My {child_term} loves the design and I love the quality. Fast shipping too!',
                'This is our third purchase from ForeverKids and every time the quality has been consistent. This {product_type} is no exception. Soft fabric, great fit, and my {child_term} is happy.',
                'I was skeptical about ordering online for my {child_term} but this {product_type} changed my mind. The material is breathable and comfortable. Highly recommend to other parents.',
                'My {child_term} picked this out and couldn\'t wait to use it. It\'s been {timeframe} and the quality is holding up great. We\'re very pleased with this purchase.',
                'Just received this {product_type} and I\'m thoroughly impressed. The attention to detail is wonderful. My {child_term} loves it and I would definitely order from ForeverKids again.',
                'Fantastic quality for the price! The {product_type} is well-made and my {child_term} is very comfortable. The design is cute and unique. Very happy with this find.',
                'This {product_type} is honestly one of the best I\'ve bought for my {child_term}. The material is soft, the colors are beautiful, and it washes well. A definite winner!',
                'Was looking for something special for my {child_term} and this {product_type} did not disappoint. Everyone compliments it when we go out. Great quality and great price.',
                'Purchased this {product_type} after reading other reviews and I\'m glad I did. My {child_term} has been using it constantly. The quality is exceptional for what you pay.',
                'Absolutely love this {product_type}! My {child_term} refuses to take it off. The material is soft against the skin and the design is adorable. Will be buying more!',
                'I compared this with several other options and this {product_type} stood out for the quality. My {child_term} has been using it for {timeframe} and it still looks amazing.',
                'Such a lovely {product_type}! My {child_term} looks adorable in it. The fabric quality is really good and it\'s very comfortable. Delivery was quick too.',
                'Really pleased with this purchase. The {product_type} is well-stitched and the material is high quality. My {child_term} loves wearing it. Would recommend to friends.',
                'This is exactly what I expected and more. The {product_type} fits perfectly and my {child_term} is very happy with it. Great experience shopping here.',
                'Wonderful {product_type}! Got so many compliments on it. My {child_term} is comfortable and loves the design. Will definitely come back for more.',
            ],
            $rating >= 4 => [
                'Good {product_type} overall. My {child_term} likes it and the quality is decent for the price. Only minor thing is the sizing runs a tiny bit small.',
                'Bought this for my {child_term} and it\'s a solid purchase. Nice material and good stitching. Could be slightly better in terms of color accuracy from the photos.',
                'Happy with this {product_type}. My {child_term} has been wearing it for {timeframe}. The quality is good though I wish the fabric was a bit thicker.',
                'Pretty good {product_type} for the price point. My {child_term} is comfortable in it. Delivery was prompt. Would have given 5 stars if packaging was a bit better.',
                'Nice {product_type}! My {child_term} likes wearing it. The quality is better than what I expected at this price range. Just a small loose thread that I trimmed off.',
                'Got this for my {child_term} and it\'s mostly great. Good quality fabric and nice design. The color is slightly different from the picture but still looks good.',
                'Decent purchase. My {child_term} enjoys using this {product_type}. It\'s well-made and comfortable. Minor issue with sizing but overall satisfied.',
                'This {product_type} is quite nice. My {child_term} wears it regularly. Good quality and value for money. Shipping was fast. Would recommend with minor reservations.',
                'I like this {product_type} a lot. My {child_term} is happy with it. The material feels good and it looks exactly like the pictures. Just wish it came in more colors.',
                'Solid {product_type} from ForeverKids. My {child_term} has been using it for {timeframe}. Good quality but took a bit longer to deliver than expected.',
                'My {child_term} looks great in this {product_type}. The quality is quite good and the price is fair. Small improvement needed in the stitching around the edges.',
                'Good buy! The {product_type} fits my {child_term} well. Material is soft and comfortable. One small thing - the care instructions tag is a bit scratchy.',
                'Purchased this for my {child_term} and I\'m satisfied with it. Nice design and good fabric quality. Would have preferred a zip instead of buttons though.',
                'Overall a good {product_type}. My {child_term} likes it. Quality is good for the price. Just needs slightly better packaging for delivery.',
                'Nice {product_type} from ForeverKids. My {child_term} is comfortable and likes the design. Value for money. Would buy again if they fix the slight color inconsistency.',
            ],
            $rating >= 3 => [
                'The {product_type} is okay. Not bad but not exceptional either. My {child_term} wears it but the fabric quality could be better for what I paid.',
                'Average {product_type}. It serves its purpose and my {child_term} doesn\'t complain. The design is fine but the material feels a bit thin.',
                'Mixed feelings about this one. The design looks nice but the quality doesn\'t feel premium. My {child_term} uses it occasionally. Fair for the price.',
                'It\'s alright. My {child_term} doesn\'t mind it but the sizing was off and the fabric shrank a bit after washing. Okay product overall.',
                'Decent {product_type} for casual use. My {child_term} has it for {timeframe} now. The stitching could be better but it\'s acceptable for the price.',
                'Got this for my {child_term} and it\'s average quality. The design from the photos looks better than in person. Not bad but I expected more.',
                'The {product_type} is fine for everyday use. My {child_term} wears it sometimes. Quality is standard, nothing to write home about.',
                'OK product. My {child_term} uses it but it\'s not their favourite. The material is acceptable and the design is simple. Meets basic expectations.',
                'Average purchase. The {product_type} looks decent but the fabric quality is just okay. My {child_term} finds it comfortable enough. Fair value.',
                'Not great, not terrible. This {product_type} does the job. My {child_term} has it for {timeframe}. Some loose threads but still wearable.',
            ],
            $rating >= 2 => [
                'Honestly expected better. The {product_type} quality is below what I thought I\'d get. My {child_term} doesn\'t find it comfortable. The material feels cheap.',
                'The {product_type} looked much better in the photos. In person, the quality is disappointing. My {child_term} wore it once and the stitching started coming apart.',
                'Not happy with this purchase. The sizing is way off and the fabric quality is poor. My {child_term} doesn\'t like wearing it. Would not recommend at this price.',
                'Below average {product_type}. The material shrank after first wash and the colors faded. My {child_term} doesn\'t want to wear it anymore.',
                'Disappointed with the quality of this {product_type}. For the price paid, I expected much better. The fabric is thin and the stitching is messy in places.',
                'Not worth it. The {product_type} quality is poor and doesn\'t match the product images at all. My {child_term} found it uncomfortable. Returning this.',
            ],
            default => [
                'Very poor quality {product_type}. The material feels terrible and the stitching is falling apart. Completely different from what was shown online. Very disappointed.',
                'Terrible purchase. The {product_type} arrived with defects and my {child_term} couldn\'t even use it. Complete waste of money. Would not recommend to anyone.',
                'Worst {product_type} I\'ve bought. Cheap fabric, poor construction, and nothing like the pictures. Regret buying this. Stay away.',
            ],
        };
    }

    // ─── Pros/Cons Pools ─────────────────────────────────────

    private function getProsPool(string $categorySlug): array
    {
        $general = [
            'Good quality material',
            'Fast delivery',
            'Nice packaging',
            'Value for money',
            'True to description',
            'Comfortable fit',
            'Easy to maintain',
            'Good colour options',
            'Durable build',
            'Looks great',
        ];

        $category = strtolower($categorySlug);

        if (str_contains($category, 'cloth') || str_contains($category, 'dress') || str_contains($category, 'wear') || str_contains($category, 'shirt') || str_contains($category, 'girl') || str_contains($category, 'boy')) {
            return array_merge($general, [
                'Soft fabric',
                'True to size',
                'Easy to wash',
                'Vibrant colours',
                'Breathable material',
                'Neat stitching',
                'No colour fading after wash',
                'Comfortable for all-day wear',
                'Skin-friendly fabric',
                'Stretchy and flexible',
            ]);
        }

        if (str_contains($category, 'toy') || str_contains($category, 'game') || str_contains($category, 'puzzle')) {
            return array_merge($general, [
                'Safe materials',
                'Keeps kids engaged',
                'Sturdy build',
                'Educational value',
                'Non-toxic paint',
                'Age-appropriate design',
                'Bright and attractive',
                'Smooth edges, child safe',
                'Promotes creativity',
                'Easy to assemble',
            ]);
        }

        if (str_contains($category, 'shoe') || str_contains($category, 'foot')) {
            return array_merge($general, [
                'Comfortable sole',
                'Good grip',
                'Lightweight',
                'True to size',
                'Easy to put on',
                'Durable material',
                'Supports foot well',
                'Breathable design',
            ]);
        }

        if (str_contains($category, 'bag') || str_contains($category, 'backpack')) {
            return array_merge($general, [
                'Spacious compartments',
                'Sturdy zippers',
                'Lightweight yet strong',
                'Water-resistant material',
                'Comfortable straps',
                'Attractive design',
            ]);
        }

        return $general;
    }

    private function getConsPool(): array
    {
        return [
            'Sizing runs slightly small',
            'Colour slightly different from photo',
            'Packaging could be better',
            'Delivery took a bit longer',
            'Could use better buttons',
            'Fabric is a bit thin',
            'Care instructions not very clear',
            'Limited colour choices',
            'Price is a touch high',
            'Wrinkled on arrival',
            'Tags are a bit scratchy',
            'Wish it came with a pouch',
            'Zipper feels a little stiff',
            'Print faded slightly after wash',
        ];
    }
}
