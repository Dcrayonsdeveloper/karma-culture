<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        // Get first admin user to attribute posts to
        $author = User::whereHas('admin')->first();

        $posts = [
            [
                'title'          => 'The Ultimate Skincare Basics Guide for Beginners',
                'slug'           => 'skincare-basics-guide-beginners',
                'category'       => 'Skincare',
                'tags'           => ['skincare', 'beginners', 'moisturizer', 'cleanser'],
                'featured_image' => 'blog/blog-skincare-basics.jpg',
                'excerpt'        => 'Starting a skincare routine can feel overwhelming. This beginner-friendly guide breaks down the essential steps — cleanse, tone, moisturise — and explains why each one matters for healthy, glowing skin.',
                'content'        => '<h2>Why Skincare Matters</h2><p>Great skin is not simply a matter of genetics — your daily habits have a big impact on what you see in the mirror. Depending on the products you use and a range of lifestyle factors, you can improve your skin health significantly.</p><h2>Step 1: Cleanse</h2><p>Cleansing removes dirt, oil, and makeup that accumulate throughout the day. Use a gentle, pH-balanced cleanser suited to your skin type — foaming for oily skin, cream-based for dry skin.</p><h2>Step 2: Tone</h2><p>A toner helps restore your skin\'s pH balance after cleansing and preps it to absorb serums and moisturisers more effectively. Look for alcohol-free formulas with hydrating ingredients like hyaluronic acid.</p><h2>Step 3: Moisturise</h2><p>Moisturising is non-negotiable, even for oily skin. It maintains the skin barrier and locks in hydration. For daytime, choose one with SPF for added sun protection.</p><h2>Step 4: SPF (Daytime Only)</h2><p>Sunscreen is your single most powerful anti-ageing product. Apply SPF 30 or higher every morning, even on cloudy days or when staying indoors near windows.</p><blockquote>Consistency beats perfection. A simple 3-step routine done every day will always outperform an elaborate routine done occasionally.</blockquote>',
                'is_published'   => true,
                'published_at'   => now()->subDays(10),
                'view_count'     => 247,
                'seo_data'       => [
                    'meta_title'       => 'Skincare Basics Guide for Beginners | ForeverKids',
                    'meta_description' => 'Learn the essential skincare steps for beginners — cleanse, tone, moisturise, and protect. Build a simple routine that works.',
                ],
            ],
            [
                'title'          => 'Hair Care Secrets: Keep Your Hair Strong & Shiny',
                'slug'           => 'hair-care-secrets-strong-shiny',
                'category'       => 'Hair Care',
                'tags'           => ['hair', 'hair care', 'shampoo', 'conditioning'],
                'featured_image' => 'blog/blog-hair-care.jpg',
                'excerpt'        => 'Discover the professional hair care secrets that will transform dull, damaged hair into strong, shiny locks. From choosing the right shampoo to deep conditioning techniques.',
                'content'        => '<h2>Understanding Your Hair Type</h2><p>Hair care begins with knowing your hair type — straight, wavy, curly, or coily — and your hair porosity (how well it absorbs moisture). This determines which products will actually work for you.</p><h2>How Often Should You Wash?</h2><p>Over-washing strips natural oils, while under-washing leads to buildup. Generally: oily hair benefits from washing every 1–2 days, while dry or curly hair may only need washing once or twice a week.</p><h2>The Right Shampooing Technique</h2><p>Apply shampoo only to the scalp. As you rinse, it will naturally cleanse the rest of your hair. Scrubbing the lengths can cause tangling and breakage.</p><h2>Deep Conditioning is a Game Changer</h2><p>A weekly deep conditioning mask (left on for 20–30 minutes under a shower cap) dramatically improves hair elasticity and shine. Look for masks with keratin, argan oil, or shea butter.</p><h2>Heat Protection is Non-Negotiable</h2><p>Always apply a heat protectant spray before using any heat styling tools. Set your dryer or straightener to the lowest effective temperature for your hair type.</p>',
                'is_published'   => true,
                'published_at'   => now()->subDays(7),
                'view_count'     => 183,
                'seo_data'       => [
                    'meta_title'       => 'Hair Care Secrets for Strong Shiny Hair | ForeverKids',
                    'meta_description' => 'Professional hair care secrets to keep your hair strong, shiny and healthy. Learn the right techniques for washing, conditioning and styling.',
                ],
            ],
            [
                'title'          => '10 Makeup Tips Every Beginner Should Know',
                'slug'           => '10-makeup-tips-for-beginners',
                'category'       => 'Makeup',
                'tags'           => ['makeup', 'foundation', 'beginner makeup', 'tips'],
                'featured_image' => 'blog/blog-makeup-tips.jpg',
                'excerpt'        => 'Whether you\'re just starting out or looking to level up your makeup skills, these 10 essential tips will help you apply makeup more confidently and achieve a flawless, natural finish.',
                'content'        => '<h2>1. Start With Skincare</h2><p>Great makeup starts with great skin. A well-moisturised, primed face gives makeup something to cling to, helping it last longer and look more natural.</p><h2>2. Choose the Right Foundation Shade</h2><p>Test foundation on your jawline — not your wrist or hand. The right shade should disappear into your skin. When in doubt, go slightly lighter and bronze up with a subtle bronzer.</p><h2>3. Less Is More for Beginners</h2><p>A little foundation, some mascara, and a swipe of lip colour is already a complete look. Build complexity gradually as your skills grow.</p><h2>4. Blend, Blend, Blend</h2><p>The secret to flawless makeup is blending. Use a damp beauty sponge or brush to work products into the skin rather than sitting on top of it.</p><h2>5. Set Your Makeup</h2><p>A light dusting of translucent setting powder or a spritz of setting spray locks everything in place and reduces shine throughout the day.</p><h2>6. Curl Your Lashes Before Mascara</h2><p>This simple step opens up your eyes dramatically. Do it before mascara (never after — it can break lashes).</p><h2>7. Clean Up Edges With Concealer</h2><p>After applying eyeshadow or liner, use a small brush with concealer to clean up any fallout under the eye and sharpen the edges of your look.</p><h2>8. Use Lip Liner</h2><p>Lip liner prevents lipstick from bleeding, makes lips look fuller, and helps colour last longer. Trace just outside your natural lip line for a plumper appearance.</p><h2>9. Invest in Good Brushes</h2><p>You don\'t need dozens of brushes. A few quality ones — foundation, blush, eyeshadow — make a bigger difference than having a large set of cheap tools.</p><h2>10. Remove Makeup Every Night</h2><p>Sleeping in makeup clogs pores and accelerates skin ageing. Always cleanse thoroughly before bed, following up with your night moisturiser.</p>',
                'is_published'   => true,
                'published_at'   => now()->subDays(5),
                'view_count'     => 412,
                'seo_data'       => [
                    'meta_title'       => '10 Makeup Tips for Beginners | ForeverKids Blog',
                    'meta_description' => 'Essential makeup tips for beginners: from choosing the right foundation to blending techniques. Build confidence in your makeup routine.',
                ],
            ],
            [
                'title'          => 'Your Complete Guide to Sunscreen: Why SPF is Non-Negotiable',
                'slug'           => 'complete-guide-to-sunscreen-spf',
                'category'       => 'Skincare',
                'tags'           => ['sunscreen', 'SPF', 'sun protection', 'anti-aging'],
                'featured_image' => 'blog/blog-sunscreen-guide.jpg',
                'excerpt'        => 'SPF is the single most effective anti-ageing product you can own. Learn how sunscreen works, how much to apply, and why you need it even on cloudy days and indoors.',
                'content'        => '<h2>What Does SPF Actually Mean?</h2><p>SPF (Sun Protection Factor) measures how well a sunscreen protects against UVB rays — the ones that cause sunburn. SPF 30 blocks about 97% of UVB rays; SPF 50 blocks about 98%. Higher is better, but no sunscreen blocks 100%.</p><h2>UVA vs UVB</h2><p>UVB rays cause sunburn; UVA rays penetrate deeper, causing premature ageing and increasing skin cancer risk. Look for "broad spectrum" sunscreen — it protects against both types.</p><h2>How Much Sunscreen to Use</h2><p>Most people apply far too little. For your face alone, you need about a teaspoon (or two finger-lengths). For your entire body, you need roughly a shot glass worth.</p><h2>Do I Need Sunscreen Indoors?</h2><p>Yes — UVA rays penetrate windows. If you sit near a window at home or work, you are still being exposed to skin-ageing UVA radiation. Daily SPF application is recommended regardless of your plans.</p><h2>Chemical vs Mineral Sunscreen</h2><p>Chemical sunscreens absorb UV rays and convert them to heat. Mineral sunscreens (containing zinc oxide or titanium dioxide) sit on top of skin and reflect UV rays. Both are effective — choice depends on skin sensitivity and personal preference.</p><blockquote>The best sunscreen is the one you\'ll actually use every day. Find a texture you enjoy and stick with it.</blockquote>',
                'is_published'   => true,
                'published_at'   => now()->subDays(3),
                'view_count'     => 329,
                'seo_data'       => [
                    'meta_title'       => 'Complete Sunscreen Guide: SPF Explained | ForeverKids',
                    'meta_description' => 'Everything you need to know about sunscreen — SPF numbers, UVA/UVB protection, how much to apply, and why you need it daily.',
                ],
            ],
            [
                'title'          => 'The Perfect Night-time Skincare Routine',
                'slug'           => 'perfect-night-time-skincare-routine',
                'category'       => 'Skincare',
                'tags'           => ['night routine', 'skincare', 'retinol', 'night cream'],
                'featured_image' => 'blog/blog-night-routine.jpg',
                'excerpt'        => 'Your skin repairs itself while you sleep — make the most of it. This step-by-step night routine covers everything from double cleansing to applying retinol, so you wake up with visibly better skin.',
                'content'        => '<h2>Why Night Routines Matter</h2><p>Skin cell turnover peaks between 11 PM and 4 AM. This is when your skin repairs UV damage, produces collagen, and regenerates. The right products applied at night amplify this natural process.</p><h2>Step 1: Double Cleanse</h2><p>Start with an oil-based cleanser or micellar water to dissolve makeup and SPF. Follow with your regular water-based cleanser to thoroughly clean the skin. This two-step approach ensures nothing is left behind.</p><h2>Step 2: Exfoliate (2–3 Times Per Week)</h2><p>Chemical exfoliants like AHA (glycolic acid) or BHA (salicylic acid) dissolve dead skin cells and promote cell turnover. Use them at night since they can increase sun sensitivity.</p><h2>Step 3: Toner or Essence</h2><p>A hydrating toner or essence replenishes moisture after cleansing. Pat (don\'t rub) it into the skin using clean hands or a cotton pad.</p><h2>Step 4: Serum (Retinol or Niacinamide)</h2><p>Night is the best time for active ingredients. Retinol (vitamin A) is the gold-standard anti-ageing ingredient — start with a low concentration 2–3 times per week and build up gradually. Niacinamide is a gentler alternative that evens skin tone and minimises pores.</p><h2>Step 5: Eye Cream</h2><p>The skin around the eyes is the thinnest on the body and shows ageing first. Apply a dedicated eye cream with your ring finger (the weakest, causing least pressure).</p><h2>Step 6: Night Moisturiser</h2><p>A richer moisturiser than your daytime one works wonders overnight. Look for ingredients like hyaluronic acid, ceramides, and peptides to support the skin barrier.</p>',
                'is_published'   => true,
                'published_at'   => now()->subDays(1),
                'view_count'     => 156,
                'seo_data'       => [
                    'meta_title'       => 'Perfect Night-time Skincare Routine | ForeverKids Blog',
                    'meta_description' => 'Step-by-step night skincare routine: double cleanse, exfoliate, retinol serum, and more. Wake up to visibly better skin every morning.',
                ],
            ],
            [
                'title'          => 'Top Ingredients to Look for in Anti-Ageing Products',
                'slug'           => 'top-ingredients-anti-ageing-products',
                'category'       => 'Skincare',
                'tags'           => ['anti-aging', 'retinol', 'hyaluronic acid', 'vitamin C'],
                'featured_image' => null,
                'excerpt'        => 'Not all anti-ageing ingredients are created equal. Find out which ones are backed by science — retinol, vitamin C, hyaluronic acid, peptides — and how to use them effectively.',
                'content'        => '<h2>1. Retinol (Vitamin A)</h2><p>Retinol is the most researched anti-ageing ingredient in dermatology. It speeds up cell turnover, stimulates collagen production, and fades dark spots. Start with 0.025%–0.1% and use only at night.</p><h2>2. Vitamin C (L-Ascorbic Acid)</h2><p>A powerful antioxidant that neutralises free radicals, boosts collagen synthesis, and brightens skin tone. Look for concentrations of 10–20% in a stable formula (often in an amber or opaque bottle).</p><h2>3. Hyaluronic Acid</h2><p>Hyaluronic acid holds up to 1000 times its weight in water, plumping and hydrating skin from within. It works best when applied to slightly damp skin to draw moisture from the air.</p><h2>4. Niacinamide (Vitamin B3)</h2><p>This multi-tasker reduces pore appearance, fades hyperpigmentation, strengthens the skin barrier, and regulates oil production. It is gentle enough for all skin types including sensitive skin.</p><h2>5. Peptides</h2><p>Peptides are short chains of amino acids that signal the skin to produce more collagen and elastin. They are excellent for firming and plumping without the irritation associated with retinol.</p><h2>6. AHAs and BHAs</h2><p>Alpha-hydroxy acids (glycolic, lactic) exfoliate the surface; beta-hydroxy acids (salicylic) penetrate pores. Regular exfoliation reveals fresher, more even skin and helps other ingredients penetrate better.</p>',
                'is_published'   => false,
                'published_at'   => null,
                'view_count'     => 0,
                'seo_data'       => [
                    'meta_title'       => 'Best Anti-Ageing Skincare Ingredients | ForeverKids',
                    'meta_description' => 'Discover the scientifically proven anti-ageing ingredients: retinol, vitamin C, hyaluronic acid and more. Learn how to use them safely.',
                ],
            ],
        ];

        foreach ($posts as $data) {
            $data['author_id'] = $author?->id;
            BlogPost::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('Blog posts seeded: ' . count($posts) . ' posts (5 published, 1 draft)');
    }
}
