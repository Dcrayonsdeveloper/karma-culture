<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class KarmaaLegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $brand = 'Karmaa Kulture';
        $domain = 'karmaakulture.com';
        $contact = 'support@karmaakulture.com';
        $today = now()->format('F j, Y');

        $pages = [
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'content' => <<<HTML
<p><em>Last updated: {$today}</em></p>

<p>This Privacy Policy describes how {$brand} ("we", "us", or "our") collects, uses, and shares information about you when you visit, purchase from, or otherwise interact with our website at {$domain}.</p>

<h2>1. Information We Collect</h2>
<p>We collect the following categories of information:</p>
<ul>
    <li><strong>Account &amp; order information</strong> &mdash; name, email, phone number, shipping &amp; billing addresses, password (hashed), and order history.</li>
    <li><strong>Payment information</strong> &mdash; processed by our PCI-compliant payment partners; we do not store full card numbers on our servers.</li>
    <li><strong>Usage data</strong> &mdash; pages viewed, products browsed, items added to cart, device type, IP address, and referrer.</li>
    <li><strong>Marketing preferences</strong> &mdash; whether you've subscribed to our newsletter or opted in to promotional SMS.</li>
</ul>

<h2>2. How We Use Your Information</h2>
<ul>
    <li>To fulfil orders, send shipping updates, and handle returns.</li>
    <li>To provide customer support and respond to enquiries.</li>
    <li>To send you marketing emails (only if you've opted in &mdash; you can unsubscribe at any time).</li>
    <li>To improve our products, site, and shopping experience.</li>
    <li>To detect fraud and comply with legal obligations.</li>
</ul>

<h2>3. Sharing Your Information</h2>
<p>We share your information only with:</p>
<ul>
    <li>Shipping carriers (to deliver your order).</li>
    <li>Payment processors (to handle transactions).</li>
    <li>Analytics &amp; email service providers (under strict data-processing agreements).</li>
    <li>Tax &amp; legal authorities when required by law.</li>
</ul>
<p>We <strong>never sell</strong> your personal information.</p>

<h2>4. Your Rights</h2>
<p>You may request access to, correction of, or deletion of your personal data at any time. Email us at <a href="mailto:{$contact}">{$contact}</a> with the subject "Data Request" and we'll respond within 30 days.</p>

<h2>5. Data Security</h2>
<p>We use industry-standard encryption (TLS in transit, encrypted at rest), restrict internal access on a need-to-know basis, and audit our systems regularly.</p>

<h2>6. Cookies</h2>
<p>For details about how we use cookies, please see our <a href="/cookie-policy">Cookie Policy</a>.</p>

<h2>7. Children's Privacy</h2>
<p>Our services are not directed to individuals under 18. We do not knowingly collect personal data from minors.</p>

<h2>8. Changes to This Policy</h2>
<p>We may update this policy from time to time. The "Last updated" date at the top will reflect the most recent revision.</p>

<h2>9. Contact</h2>
<p>For privacy-related questions, email <a href="mailto:{$contact}">{$contact}</a>.</p>
HTML,
            ],
            [
                'slug' => 'terms-of-service',
                'title' => 'Terms of Service',
                'content' => <<<HTML
<p><em>Last updated: {$today}</em></p>

<p>Welcome to {$brand}. By accessing or using our website at {$domain} ("the Site"), you agree to be bound by these Terms of Service. If you do not agree, please do not use the Site.</p>

<h2>1. Eligibility</h2>
<p>You must be at least 18 years old to make a purchase. By placing an order, you confirm you are of legal age and have the authority to enter into binding contracts.</p>

<h2>2. Account Responsibilities</h2>
<p>You are responsible for keeping your account credentials confidential and for all activity that happens under your account. Notify us immediately if you suspect unauthorized use.</p>

<h2>3. Products &amp; Pricing</h2>
<ul>
    <li>All prices are listed in Indian Rupees (₹) and include applicable taxes unless stated otherwise.</li>
    <li>We reserve the right to correct pricing errors, refuse or cancel any order, and limit quantities.</li>
    <li>Product colours and details may vary slightly from images due to monitor settings and fabric runs.</li>
</ul>

<h2>4. Orders &amp; Payment</h2>
<p>An order is confirmed only after we send a confirmation email. We accept major credit cards, debit cards, net banking, UPI, and cash on delivery (where available). Payment must clear before dispatch.</p>

<h2>5. Shipping &amp; Delivery</h2>
<p>Delivery estimates are provided at checkout. Title and risk of loss pass to you upon delivery to the carrier. We are not liable for delays caused by carriers or events beyond our control.</p>

<h2>6. Returns &amp; Refunds</h2>
<p>See our <a href="/returns">Returns &amp; Refunds policy</a> for full details. In short: most items are returnable within 14 days of delivery in unused, original-tag condition. Sale items are final sale unless defective.</p>

<h2>7. Intellectual Property</h2>
<p>All content on the Site &mdash; including logos, designs, photography, product descriptions, and code &mdash; is owned by {$brand} or our licensors and is protected by copyright and trademark law. You may not copy, reproduce, or use any content commercially without written permission.</p>

<h2>8. Prohibited Use</h2>
<p>You may not use the Site to: violate any law; harass, abuse, or harm any person; submit false information; attempt to access another user's account; or interfere with the Site's operation.</p>

<h2>9. Disclaimer of Warranties</h2>
<p>The Site and all products are provided "as is" and "as available". We make no warranties, express or implied, regarding merchantability, fitness for a particular purpose, or non-infringement, except as required by applicable consumer protection law.</p>

<h2>10. Limitation of Liability</h2>
<p>To the maximum extent permitted by law, {$brand} shall not be liable for indirect, incidental, or consequential damages arising from your use of the Site. Our total liability for any claim shall not exceed the amount you paid for the product in question.</p>

<h2>11. Governing Law</h2>
<p>These Terms are governed by the laws of India. Any dispute shall be resolved in the courts of New Delhi.</p>

<h2>12. Changes to These Terms</h2>
<p>We may revise these Terms at any time. Continued use of the Site after changes constitutes acceptance of the revised Terms.</p>

<h2>13. Contact</h2>
<p>Questions about these Terms? Email <a href="mailto:{$contact}">{$contact}</a>.</p>
HTML,
            ],
            [
                'slug' => 'cookie-policy',
                'title' => 'Cookie Policy',
                'content' => <<<HTML
<p><em>Last updated: {$today}</em></p>

<p>This Cookie Policy explains how {$brand} uses cookies and similar tracking technologies on {$domain}.</p>

<h2>1. What Are Cookies?</h2>
<p>Cookies are small text files that websites place on your device to remember preferences, keep you signed in, and understand how the site is used. They cannot run programs or read other files on your device.</p>

<h2>2. Types of Cookies We Use</h2>

<h3>Strictly Necessary Cookies</h3>
<p>Required for the Site to function &mdash; shopping cart, checkout, login session, and CSRF protection. The Site cannot work properly without these and they cannot be disabled.</p>

<h3>Functional Cookies</h3>
<p>Remember choices like language, currency, recently viewed products, and wishlist contents so you don't have to set them on every visit.</p>

<h3>Analytics Cookies</h3>
<p>Help us understand which pages are popular, where visitors come from, and how the site performs. We use providers like Google Analytics in aggregated, anonymised form.</p>

<h3>Marketing Cookies</h3>
<p>Set by our advertising partners (e.g. Meta, Google Ads) to show you relevant ads on other sites and measure campaign performance. These only fire if you've consented via our cookie banner.</p>

<h2>3. Third-Party Cookies</h2>
<p>Some cookies are set by services that appear on our pages (payment processors, embedded videos, social-share widgets). These are governed by the third party's own privacy policy.</p>

<h2>4. Managing Cookies</h2>
<ul>
    <li>Use the cookie banner on the Site to accept or reject non-essential categories.</li>
    <li>Most browsers let you block or delete cookies in their settings &mdash; check the Help section of your browser.</li>
    <li>Disabling strictly necessary cookies will break checkout and account features.</li>
</ul>

<h2>5. Do Not Track</h2>
<p>We honour the "Do Not Track" browser signal where technically feasible by not loading analytics or marketing cookies for that session.</p>

<h2>6. Changes</h2>
<p>We may update this Cookie Policy as the Site evolves. The "Last updated" date will reflect the most recent revision.</p>

<h2>7. Contact</h2>
<p>For cookie-related questions, email <a href="mailto:{$contact}">{$contact}</a>.</p>
HTML,
            ],
        ];

        foreach ($pages as $p) {
            Page::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'title' => $p['title'],
                    'content' => $p['content'],
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
        }

        $this->command->info('Seeded ' . count($pages) . ' legal pages (Privacy, Terms, Cookie).');
    }
}
