<x-layouts.admin>
    <x-slot name="title">SEO Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Settings</h1>
        </div>
    </x-slot>

    @include('admin.settings.partials.nav', ['active' => 'seo'])

    @include('admin.settings.partials.errors')

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
                        <input type="url" name="og_image" value="{{ old('og_image', $settings['og_image'] ?? '') }}" placeholder="https://karmaakulture.com/images/og-image.jpg" class="form-input">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Recommended size: 1200&times;630 pixels</p>
                    </div>

                    <div>
                        <label class="form-label">Twitter / X Site Handle</label>
                        <input type="text" name="twitter_site" value="{{ old('twitter_site', $settings['twitter_site'] ?? '') }}" placeholder="@KarmaaKulture" class="form-input">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Used for Twitter Card meta tags</p>
                    </div>
                </div>
            </div>

            <!-- Analytics & Tracking -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Analytics & Tracking</h2>
                        <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">IDs are injected after cookie consent.</p>
                    </div>
                    <div style="padding: 1rem;">
                        @include('admin.partials.env-managed', [
                            'vars' => [
                                'GA4_MEASUREMENT_ID'    => (string) config('services.ga4.measurement_id', '') !== '',
                                'GOOGLE_TAG_MANAGER_ID' => (string) config('services.gtm.id', '') !== '',
                                'FB_PIXEL_ID'           => (string) config('services.facebook.pixel_id', '') !== '',
                            ],
                            'configured' => (string) config('services.ga4.measurement_id', '') !== ''
                                || (string) config('services.gtm.id', '') !== ''
                                || (string) config('services.facebook.pixel_id', '') !== '',
                            'help' => 'These load third-party scripts on every page, so they are pinned to the deployment rather than editable here.',
                        ])
                    </div>
                </div>

                <!-- Search Console Verification -->
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Google Search Console</h2>
                        <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">The ownership meta tag is rendered from the server's configuration.</p>
                    </div>
                    <div style="padding: 1rem;">
                        @include('admin.partials.env-managed', [
                            'vars' => ['GOOGLE_SITE_VERIFICATION' => (string) config('services.google.site_verification', '') !== ''],
                            'help' => 'In Search Console → Settings → Ownership verification → HTML tag, copy only the content="..." value.',
                        ])
                    </div>
                </div>

                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Robots.txt</h2>
                        <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">
                            @if($robotsIsCustom)
                                Served from <code style="background: #f6f6f7; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 12px;">public/robots.txt</code>. Clear the box to go back to the automatic version.
                            @else
                                Currently generated automatically from your site URL. Editing this box saves a fixed <code style="background: #f6f6f7; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 12px;">public/robots.txt</code> that replaces it; clear the box to switch back.
                            @endif
                            HTML is stripped automatically.
                        </p>
                    </div>
                    <div style="padding: 1rem;">
                        <textarea name="robots_txt" rows="10" class="form-textarea" style="font-family: monospace; font-size: 12px;" placeholder="User-agent: *&#10;Allow: /&#10;Disallow: /admin/&#10;Disallow: /account/">{{ old('robots_txt', $robotsTxt) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Settings</button>
        </div>
    </form>
</x-layouts.admin>
