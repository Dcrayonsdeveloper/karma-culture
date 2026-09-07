<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * /returns-policy and /size-guides were the last two storefront pages whose
 * words lived in a Blade file, so correcting a return window or a chest
 * measurement meant a code change and a deploy. Every other page of that kind
 * - privacy, terms, cookies, GDPR - is a `pages` row the admin edits under
 * Online Store -> Pages, and these two now are as well.
 *
 * The bodies below are those two views' copy carried over verbatim: the same
 * sentences, the same list items, the same seventeen rows of the size chart.
 * What could not come with them is the decoration - the tick and cross icons
 * beside each bullet, the three numbered step cards, the amber "damaged item"
 * panel. Stored HTML cannot carry an SVG through the render allowlist, so the
 * lists are plain lists and the steps are an ordered one.
 *
 * A migration rather than a seeder, for two reasons. Seeders do not run on a
 * deploy, and both routes answer 404 without their row. And the legal pages'
 * seeder writes through `updateOrCreate`, which would overwrite an admin's
 * edits every time someone re-ran it; this inserts only what is missing and
 * never touches a row that already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->pages() as $page) {
            if (DB::table('pages')->where('slug', $page['slug'])->exists()) {
                continue;
            }

            DB::table('pages')->insert($page + [
                'is_published' => true,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Left in place, like the About Us section this is modelled on. By the
        // time this rolls back an admin may have rewritten the return window
        // or the size chart, and dropping the row would take their wording
        // with it - and both routes with it, since each firstOrFail()s on the
        // slug.
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pages(): array
    {
        return [
            [
                'slug' => 'returns-policy',
                'title' => 'Returns Policy',
                // The brand is spelled out rather than interpolated from
                // config('app.name'): this is stored copy, and an admin
                // editing the page has to be able to see and change every
                // word of it.
                'seo_data' => json_encode([
                    'meta_title' => 'Returns Policy',
                    'meta_description' => "Returns and exchange policy for Karmaa Kulture. Easy returns on kids' clothing within the return window.",
                ]),
                'content' => <<<'HTML'
                    <p>We want you to be completely satisfied with your purchase.</p>

                    <h2>7-Day Return Policy</h2>
                    <p>We offer a 7-day return policy on most items. You can return products within 7 days of delivery for a full refund or exchange.</p>

                    <h2>Return Eligibility</h2>
                    <p>To be eligible for a return, your item must be:</p>
                    <ul>
                        <li>In the same condition that you received it</li>
                        <li>Unused and unworn</li>
                        <li>In its original packaging with all tags attached</li>
                        <li>Accompanied by the receipt or proof of purchase</li>
                    </ul>

                    <h2>Non-Returnable Items</h2>
                    <p>The following items cannot be returned:</p>
                    <ul>
                        <li>Gift cards</li>
                        <li>Downloaded software</li>
                        <li>Personal care items (for hygiene reasons)</li>
                        <li>Custom or personalized items</li>
                        <li>Sale items marked as final sale</li>
                        <li>Items damaged through misuse</li>
                    </ul>

                    <h2>How to Return an Item</h2>
                    <ol>
                        <li><strong>Request Return</strong> - Log into your account and go to your <a href="/account/orders">order history</a> to request a return.</li>
                        <li><strong>Ship Item</strong> - Pack the item securely and ship it using the provided return label.</li>
                        <li><strong>Get Refund</strong> - Once received and inspected, your refund will be processed within 5-7 days.</li>
                    </ol>

                    <h2>Refunds</h2>
                    <p>Once your return is received and inspected, we will send you an email to notify you of the approval or rejection of your refund.</p>
                    <p>If approved, your refund will be processed, and a credit will automatically be applied to your original payment method within 5-7 business days.</p>

                    <h2>Return Shipping</h2>
                    <p>For defective or incorrect items, we will provide a prepaid return shipping label. For other returns, the customer is responsible for return shipping costs.</p>

                    <h2>Exchanges</h2>
                    <p>If you need a different size or color, we recommend returning the item for a refund and placing a new order for the fastest delivery.</p>

                    <h2>Damaged or Defective Items</h2>
                    <p>If you receive a damaged or defective item, please <a href="/contacts">contact us</a> within 48 hours of delivery with photos of the damage. We will arrange for a replacement or refund.</p>
                    HTML,
            ],
            [
                // The slug matches the path, as every other page of this kind
                // does - the route is /size-guides, plural.
                'slug' => 'size-guides',
                'title' => 'Size Guide',
                'seo_data' => json_encode([
                    'meta_title' => "Kids' Clothing Size Guide",
                    'meta_description' => "Kids' clothing size guide at Karmaa Kulture. Find the perfect fit for boys and girls with our sizing charts.",
                ]),
                // The chart is written the way CKEditor 5 emits a table - one
                // line, wrapped in <figure class="table"> - so an admin's
                // first save produces no structural diff against this.
                'content' => <<<'HTML'
                    <p>Find the perfect fit for your little one. Use the chart below to match your child's measurements with our size numbers.</p>

                    <h2>Size Chart</h2>

                    <figure class="table"><table><thead><tr><th>Size</th><th>Age</th><th>Height (cm)</th><th>Chest (cm)</th><th>Waist (cm)</th></tr></thead><tbody><tr><td><strong>18</strong></td><td>0 - 3 months</td><td>50 - 56</td><td>36 - 38</td><td>36 - 38</td></tr><tr><td><strong>20</strong></td><td>3 - 6 months</td><td>56 - 62</td><td>38 - 40</td><td>38 - 40</td></tr><tr><td><strong>22</strong></td><td>6 - 9 months</td><td>62 - 68</td><td>40 - 42</td><td>40 - 42</td></tr><tr><td><strong>24</strong></td><td>9 - 12 months</td><td>68 - 74</td><td>42 - 44</td><td>42 - 44</td></tr><tr><td><strong>26</strong></td><td>1 - 1.5 years</td><td>74 - 80</td><td>44 - 46</td><td>44 - 45</td></tr><tr><td><strong>28</strong></td><td>1.5 - 2 years</td><td>80 - 86</td><td>46 - 48</td><td>45 - 47</td></tr><tr><td><strong>30</strong></td><td>2 - 3 years</td><td>86 - 92</td><td>48 - 50</td><td>47 - 48</td></tr><tr><td><strong>32</strong></td><td>3 - 4 years</td><td>92 - 98</td><td>50 - 52</td><td>48 - 50</td></tr><tr><td><strong>34</strong></td><td>4 - 5 years</td><td>98 - 104</td><td>52 - 54</td><td>50 - 52</td></tr><tr><td><strong>36</strong></td><td>5 - 6 years</td><td>104 - 110</td><td>54 - 56</td><td>52 - 53</td></tr><tr><td><strong>38</strong></td><td>6 - 7 years</td><td>110 - 116</td><td>56 - 58</td><td>53 - 55</td></tr><tr><td><strong>40</strong></td><td>7 - 8 years</td><td>116 - 122</td><td>58 - 61</td><td>55 - 57</td></tr><tr><td><strong>42</strong></td><td>8 - 9 years</td><td>122 - 128</td><td>61 - 64</td><td>57 - 59</td></tr><tr><td><strong>44</strong></td><td>9 - 10 years</td><td>128 - 134</td><td>64 - 67</td><td>59 - 61</td></tr><tr><td><strong>46</strong></td><td>10 - 12 years</td><td>134 - 146</td><td>67 - 72</td><td>61 - 64</td></tr><tr><td><strong>48</strong></td><td>12 - 13 years</td><td>146 - 152</td><td>72 - 76</td><td>64 - 66</td></tr><tr><td><strong>50</strong></td><td>13 - 14 years</td><td>152 - 158</td><td>76 - 80</td><td>66 - 68</td></tr></tbody></table></figure>

                    <h2>How to Measure</h2>

                    <h3>Height</h3>
                    <p>Stand your child against a wall without shoes. Measure from the top of the head to the floor.</p>

                    <h3>Chest</h3>
                    <p>Wrap a measuring tape around the fullest part of the chest, keeping it level under the arms.</p>

                    <h3>Waist</h3>
                    <p>Measure around the natural waistline (the narrowest point), keeping the tape snug but not tight.</p>

                    <blockquote><p><strong>Tip:</strong> If your child's measurements fall between two sizes, we recommend choosing the larger size for a more comfortable fit and room to grow.</p></blockquote>
                    HTML,
            ],
        ];
    }
};
