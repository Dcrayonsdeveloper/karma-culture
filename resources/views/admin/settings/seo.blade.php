<x-layouts.admin>
    <x-slot name="title">SEO Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Settings</h1>
        </div>
    </x-slot>

    @include('admin.settings.partials.nav', ['active' => 'seo'])

    <form action="{{ route('admin.settings.seo.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <!-- Meta Tags -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Default Meta Tags</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Used when pages don't have specific meta tags</p>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $settings['meta_title'] ?? '') }}" maxlength="70" class="form-input">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Max 70 characters</p>
                    </div>

                    <div>
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" rows="3" maxlength="160" class="form-textarea">{{ old('meta_description', $settings['meta_description'] ?? '') }}</textarea>
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Max 160 characters</p>
                    </div>

                    <div>
                        <label class="form-label">Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $settings['meta_keywords'] ?? '') }}" placeholder="keyword1, keyword2, keyword3" class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Open Graph Image URL</label>
                        <input type="url" name="og_image" value="{{ old('og_image', $settings['og_image'] ?? '') }}" placeholder="https://foreverkids.dcrayons.app/images/og-image.jpg" class="form-input">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Recommended size: 1200&times;630 pixels</p>
                    </div>

                    <div>
                        <label class="form-label">Twitter / X Site Handle</label>
                        <input type="text" name="twitter_site" value="{{ old('twitter_site', $settings['twitter_site'] ?? '') }}" placeholder="@ForeverKids" class="form-input">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Used for Twitter Card meta tags</p>
                    </div>
                </div>
            </div>

            <!-- Analytics & Tracking -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Analytics & Tracking</h2>
                        <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">IDs are injected after cookie consent. Format is validated.</p>
                    </div>
                    <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label">Google Analytics 4 ID</label>
                            <input type="text" name="google_analytics_id" value="{{ old('google_analytics_id', $settings['google_analytics_id'] ?? '') }}" placeholder="G-XXXXXXXXXX" class="form-input" pattern="G-[A-Z0-9]+" title="Must be in format G-XXXXXXXXXX">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Format: <code style="background: #f6f6f7; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 12px;">G-XXXXXXXXXX</code></p>
                        </div>

                        <div>
                            <label class="form-label">Google Tag Manager ID</label>
                            <input type="text" name="google_tag_manager_id" value="{{ old('google_tag_manager_id', $settings['google_tag_manager_id'] ?? '') }}" placeholder="GTM-XXXXXXX" class="form-input" pattern="GTM-[A-Z0-9]+" title="Must be in format GTM-XXXXXXX">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Format: <code style="background: #f6f6f7; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 12px;">GTM-XXXXXXX</code></p>
                        </div>

                        <div>
                            <label class="form-label">Facebook Pixel ID</label>
                            <input type="text" name="facebook_pixel_id" value="{{ old('facebook_pixel_id', $settings['facebook_pixel_id'] ?? '') }}" placeholder="1234567890123456" class="form-input" pattern="[0-9]+" title="Pixel ID is numeric only">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Numeric digits only</p>
                        </div>
                    </div>
                </div>

                <!-- Search Console Verification -->
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Google Search Console</h2>
                        <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Paste only the <code style="background: #f6f6f7; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 12px;">content</code> value from the verification meta tag</p>
                    </div>
                    <div style="padding: 1rem;">
                        <label class="form-label">Verification Code</label>
                        <input type="text" name="google_search_console_verification"
                               value="{{ old('google_search_console_verification', $settings['google_search_console_verification'] ?? '') }}"
                               placeholder="AbCdEfGhIjKlMnOpQrStUvWxYz1234567890-_="
                               class="form-input" style="font-family: monospace; font-size: 13px;">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                            In Search Console &rarr; Settings &rarr; Ownership verification &rarr; HTML tag &rarr; copy only the <code style="background: #f6f6f7; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 12px;">content="&hellip;"</code> value
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Robots.txt</h2>
                        <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Saved to <code style="background: #f6f6f7; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 12px;">public/robots.txt</code> on submit. HTML is stripped automatically.</p>
                    </div>
                    <div style="padding: 1rem;">
                        <textarea name="robots_txt" rows="10" class="form-textarea" style="font-family: monospace; font-size: 12px;" placeholder="User-agent: *&#10;Allow: /&#10;Disallow: /admin/&#10;Disallow: /account/">{{ old('robots_txt', $settings['robots_txt'] ?? file_get_contents(public_path('robots.txt'))) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Settings</button>
        </div>
    </form>
</x-layouts.admin>
