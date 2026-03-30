<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'is_published' => true,
                'published_at' => now(),
                'content' => <<<'HTML'
<h2>Information We Collect</h2>
<p>When you use our website, we may collect the following types of personal information:</p>
<ul>
    <li><strong>Contact details</strong> — name, email address, phone number, and delivery address</li>
    <li><strong>Account information</strong> — username, password (encrypted), and preferences</li>
    <li><strong>Order history</strong> — products purchased, order status, and transaction records</li>
    <li><strong>Payment information</strong> — processed securely via our payment provider; we do not store full card details</li>
    <li><strong>Device and usage data</strong> — IP address, browser type, pages visited, and time on site (via cookies)</li>
    <li><strong>Communications</strong> — enquiries, support messages, and feedback you send us</li>
</ul>

<h2>How We Use Your Information</h2>
<p>We use your personal information to:</p>
<ul>
    <li>Process and fulfil your orders and handle returns or exchanges</li>
    <li>Send order confirmations, shipping updates, and receipts</li>
    <li>Manage your account and provide customer support</li>
    <li>Personalise your experience and show relevant product recommendations</li>
    <li>Send marketing emails (only with your consent — you can unsubscribe at any time)</li>
    <li>Improve our website, detect fraud, and maintain security</li>
    <li>Comply with legal obligations</li>
</ul>

<h2>Sharing Your Information</h2>
<p>We do not sell your personal information. We may share it with:</p>
<ul>
    <li><strong>Delivery partners</strong> — to fulfil your orders</li>
    <li><strong>Payment processors</strong> — to complete transactions securely</li>
    <li><strong>Analytics providers</strong> — to understand website usage (anonymised where possible)</li>
    <li><strong>Law enforcement or regulators</strong> — where required by law</li>
</ul>
<p>All third parties we share data with are bound by appropriate data protection agreements.</p>

<h2>Data Retention</h2>
<p>We retain your personal information for as long as necessary to provide our services and comply with legal obligations. Account data is kept for the duration of your account. Order records are retained for 7 years for tax and legal purposes. You may request deletion of your data at any time, subject to legal retention requirements.</p>

<h2>Your Rights</h2>
<p>Under applicable data protection law, you have the right to:</p>
<ul>
    <li><strong>Access</strong> — request a copy of your personal data</li>
    <li><strong>Rectification</strong> — correct inaccurate or incomplete data</li>
    <li><strong>Erasure</strong> — request deletion of your data ("right to be forgotten")</li>
    <li><strong>Restriction</strong> — limit how we process your data in certain circumstances</li>
    <li><strong>Portability</strong> — receive your data in a structured, machine-readable format</li>
    <li><strong>Object</strong> — opt out of direct marketing or processing based on legitimate interests</li>
</ul>
<p>To exercise any of these rights, please <a href="/contact">contact us</a> and we will respond within 30 days.</p>

<h2>Security</h2>
<p>We implement appropriate technical and organisational measures to protect your personal information against unauthorised access, loss, or misuse. Our website uses SSL encryption, and payment data is handled by PCI-compliant processors. However, no internet transmission is completely secure, and we cannot guarantee absolute security.</p>

<h2>Cookies</h2>
<p>We use cookies and similar technologies on our website. Please read our <a href="/cookie-policy">Cookie Policy</a> for full details on the cookies we use and how you can manage them.</p>

<h2>Changes to This Policy</h2>
<p>We may update this Privacy Policy from time to time. We will notify you of significant changes by posting a notice on our website. Continued use of our website after changes are posted constitutes your acceptance of the updated policy.</p>

<h2>Contact Us</h2>
<p>If you have questions about this Privacy Policy or how we handle your data, please <a href="/contact">contact our support team</a>. You also have the right to lodge a complaint with your national data protection authority.</p>
HTML,
            ],

            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'is_published' => true,
                'published_at' => now(),
                'content' => <<<'HTML'
<h2>Acceptance of Terms</h2>
<p>By accessing or using our website or placing an order, you confirm that you have read, understood, and agree to be bound by these Terms of Service and our <a href="/privacy-policy">Privacy Policy</a>. If you do not agree, please do not use our services.</p>

<h2>Use of Our Website</h2>
<p>You are granted a limited, non-exclusive, non-transferable licence to access and use our website for personal, non-commercial purposes. You agree to:</p>
<ul>
    <li>Provide accurate, current, and complete information when creating an account or placing an order</li>
    <li>Maintain the security of your account credentials and notify us immediately of any unauthorised use</li>
    <li>Use the website in compliance with all applicable laws and regulations</li>
</ul>

<h2>Prohibited Conduct</h2>
<p>You must not use our website to:</p>
<ul>
    <li>Violate any applicable law, regulation, or third-party rights</li>
    <li>Submit false, misleading, or fraudulent orders or information</li>
    <li>Attempt to gain unauthorised access to our systems or other users' accounts</li>
    <li>Transmit spam, malware, or any harmful or disruptive content</li>
    <li>Scrape, copy, or reproduce our content without written permission</li>
    <li>Impersonate any person or entity, or falsely claim affiliation with us</li>
</ul>

<h2>Orders &amp; Payments</h2>
<p>All orders are subject to availability and confirmation. We reserve the right to refuse or cancel any order for any reason, including pricing errors or suspected fraudulent activity. Full payment is required before items are dispatched. Prices are inclusive of applicable taxes unless stated otherwise.</p>

<h2>Intellectual Property</h2>
<p>All content on this website — including text, images, logos, product descriptions, and design — is our property and is protected by copyright and trademark laws. Unauthorised use or reproduction of any content is strictly prohibited.</p>

<h2>Product Information</h2>
<p>We strive for accuracy in all product descriptions and pricing. However, we do not warrant that all information is error-free. We reserve the right to correct any errors and update information at any time without prior notice.</p>

<h2>Disclaimer of Warranties</h2>
<p>Our website and services are provided "as is" without warranties of any kind, either express or implied. We do not warrant that the website will be uninterrupted, error-free, or free of viruses. To the fullest extent permitted by law, we disclaim all warranties.</p>

<h2>Limitation of Liability</h2>
<p>To the maximum extent permitted by law, we shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of our website or services. Our total liability shall not exceed the amount paid by you for the specific product or service giving rise to the claim.</p>

<h2>Governing Law</h2>
<p>These Terms shall be governed by and construed in accordance with applicable law. Any disputes shall be resolved through the courts of the applicable jurisdiction, unless otherwise agreed in writing.</p>

<h2>Changes to Terms</h2>
<p>We reserve the right to modify these Terms at any time. Changes take effect immediately upon posting to our website. Continued use of our website after changes constitutes your acceptance of the updated terms.</p>

<h2>Contact Us</h2>
<p>If you have questions about these Terms of Service, please <a href="/contact">contact our support team</a>.</p>
HTML,
            ],

            [
                'title' => 'Cookie Policy',
                'slug' => 'cookie-policy',
                'is_published' => true,
                'published_at' => now(),
                'content' => <<<'HTML'
<h2>What Are Cookies?</h2>
<p>Cookies are small text files that are placed on your device when you visit a website. They are widely used to make websites work, improve user experience, and provide information to the website owner. Cookies are not harmful — they cannot carry viruses or install malware. They simply store small pieces of information that help the website remember your preferences and activity.</p>

<h2>Essential Cookies</h2>
<p>These cookies are required for the website to function properly and cannot be disabled:</p>
<ul>
    <li><strong>Session cookies</strong> — keep you logged in during your visit</li>
    <li><strong>Shopping cart</strong> — preserve your selected items between pages</li>
    <li><strong>CSRF security tokens</strong> — protect you from cross-site request forgery attacks</li>
    <li><strong>Cookie consent preference</strong> — remember your cookie choices</li>
</ul>

<h2>Functional Cookies</h2>
<p>These optional cookies enhance your experience by remembering your preferences and settings:</p>
<ul>
    <li>Language and region preferences</li>
    <li>Recently viewed products</li>
    <li>Wishlist between sessions</li>
</ul>

<h2>Analytics Cookies</h2>
<p>These optional cookies help us understand how visitors use our website so we can improve it. Data is anonymised where possible:</p>
<ul>
    <li>Pages visited and time spent on site</li>
    <li>Traffic sources and conversion tracking</li>
    <li>Error monitoring and diagnostics</li>
</ul>

<h2>Marketing Cookies</h2>
<p>These optional cookies are used to show relevant advertisements and measure campaign effectiveness:</p>
<ul>
    <li>Retargeting ads on third-party platforms</li>
    <li>Social media pixel tracking</li>
    <li>Ad campaign attribution and measurement</li>
</ul>

<h2>Third-Party Cookies</h2>
<p>Some cookies are set by third-party services embedded on our pages (such as analytics tools, payment processors, or social media widgets). We do not control these cookies — please refer to each third party's privacy policy for details on how they collect and use your data.</p>

<h2>Managing Cookies</h2>
<p>You can control and manage cookies through your browser settings. Most browsers allow you to view, block, or delete cookies at any time. You can also browse in private or incognito mode to prevent cookies being saved after your session ends.</p>
<p>Please note: disabling certain cookies may affect the functionality of our website. Features such as staying logged in, keeping items in your cart, and saving your preferences may not work without essential cookies enabled.</p>

<h2>Changes to This Policy</h2>
<p>We may update this Cookie Policy from time to time. Please check back periodically for the latest version. Continued use of our website after changes constitutes acceptance of the updated policy.</p>

<h2>Contact Us</h2>
<p>If you have questions about our use of cookies, please <a href="/contact">contact our support team</a>.</p>
HTML,
            ],

            [
                'title' => 'GDPR Compliance',
                'slug' => 'gdpr',
                'is_published' => true,
                'published_at' => now(),
                'content' => <<<'HTML'
<h2>Our Commitment</h2>
<p>We are committed to protecting your personal data and complying with the General Data Protection Regulation (GDPR) and applicable data protection law. This page explains how we act as a data controller, the legal bases for processing your data, and the rights you have under GDPR.</p>

<h2>Data Controller</h2>
<p>We act as the data controller for the personal information we collect on this website. This means we are responsible for determining how and why your data is processed. For any data protection enquiries, please <a href="/contact">contact us</a>.</p>

<h2>Legal Basis for Processing</h2>
<p>We process your personal data on the following legal bases:</p>
<ul>
    <li><strong>Contract</strong> — processing is necessary to fulfil your orders and manage your account</li>
    <li><strong>Legal obligation</strong> — processing is required to comply with tax, financial, and regulatory requirements</li>
    <li><strong>Legitimate interests</strong> — processing is necessary for fraud prevention, security, and improving our services, where your fundamental rights do not override these interests</li>
    <li><strong>Consent</strong> — for marketing communications and optional cookies; you may withdraw consent at any time</li>
</ul>

<h2>Your Rights Under GDPR</h2>
<p>As a data subject, you have the following rights:</p>
<ul>
    <li><strong>Right of access</strong> — request a copy of the personal data we hold about you</li>
    <li><strong>Right to rectification</strong> — request correction of inaccurate or incomplete data</li>
    <li><strong>Right to erasure</strong> — request deletion of your personal data ("right to be forgotten"), subject to legal obligations</li>
    <li><strong>Right to restriction</strong> — request that we limit how we process your data in certain circumstances</li>
    <li><strong>Right to data portability</strong> — receive your data in a structured, commonly used, machine-readable format</li>
    <li><strong>Right to object</strong> — object to processing based on legitimate interests or direct marketing</li>
    <li><strong>Rights related to automated decision-making</strong> — not be subject to decisions made solely by automated processing that significantly affects you</li>
</ul>
<p>To exercise any of your rights, please <a href="/contact">contact us</a>. We will respond within 30 days of receiving your request.</p>

<h2>Data Retention</h2>
<p>We retain personal data only as long as necessary for the purposes it was collected:</p>
<ul>
    <li><strong>Account data</strong> — retained for the duration of your account, plus 12 months after closure</li>
    <li><strong>Order records</strong> — retained for 7 years for accounting and legal compliance</li>
    <li><strong>Marketing data</strong> — retained until you withdraw consent or unsubscribe</li>
    <li><strong>Support communications</strong> — retained for 3 years</li>
</ul>

<h2>International Data Transfers</h2>
<p>Where we transfer personal data outside your country, we ensure appropriate safeguards are in place — such as standard contractual clauses approved by the relevant data protection authority — to protect your data to an equivalent standard.</p>

<h2>Cookies &amp; Tracking</h2>
<p>Our use of cookies is detailed in our <a href="/cookie-policy">Cookie Policy</a>. We use a consent management approach to ensure we obtain valid consent before placing non-essential cookies on your device.</p>

<h2>Data Security</h2>
<p>We implement appropriate technical and organisational security measures to protect your personal data against unauthorised access, disclosure, alteration, or destruction. These measures include SSL encryption, access controls, and regular security reviews.</p>

<h2>Complaints</h2>
<p>If you have concerns about how we handle your personal data, we encourage you to <a href="/contact">contact us</a> first so we can resolve the issue directly. You also have the right to lodge a complaint with your national data protection supervisory authority.</p>
HTML,
            ],
        ];

        foreach ($pages as $data) {
            Page::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('Legal pages seeded: Privacy Policy, Terms of Service, Cookie Policy, GDPR');
    }
}
