<x-layouts.admin>
    <x-slot name="title">Site Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Site Settings</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    <form action="{{ route('admin.homepage.site-settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <!-- Brand Identity -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Brand Identity</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Site Logo</label>
                        @if($settings['site_logo'])
                            <div style="margin-bottom: 0.5rem;">
                                <img src="{{ asset('storage/' . $settings['site_logo']) }}" alt="Current Logo" style="height: 4rem; object-fit: contain;">
                            </div>
                        @endif
                        <input type="file" name="site_logo" accept="image/*" class="form-input">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Recommended: PNG with transparent background, 200x60px</p>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Site Name</label>
                        <input type="text" name="site_name" value="{{ $settings['site_name'] }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Tagline</label>
                        <input type="text" name="site_tagline" value="{{ $settings['site_tagline'] }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Site Description</label>
                        <textarea name="site_description" rows="3" class="form-textarea">{{ $settings['site_description'] }}</textarea>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Announcement Bar Text</label>
                        <input type="text" name="announcement_text" value="{{ $settings['announcement_text'] }}" class="form-input" placeholder="e.g. Free Shipping on Orders Over ₹500!">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Displayed in the teal bar at the top of every page. Leave empty to hide.</p>
                    </div>
                    {{-- The About Us section shows three videos side by side, so all
                         three are editable here. Only the first used to be, which left
                         two cards that could never be filled from the admin. --}}
                    @foreach([
                        ['url' => 'about_us_video_url',   'file' => 'about_us_video_file',   'label' => 'About Us - Video 1'],
                        ['url' => 'about_us_video_url_2', 'file' => 'about_us_video_file_2', 'label' => 'About Us - Video 2'],
                        ['url' => 'about_us_video_url_3', 'file' => 'about_us_video_file_3', 'label' => 'About Us - Video 3'],
                    ] as $kkAboutVideo)
                    <div style="border-top: 1px solid #e3e3e3; padding-top: 1rem;">
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">{{ $kkAboutVideo['label'] }}</label>
                        @if($settings[$kkAboutVideo['url']] ?? '')
                            <div style="margin: 0.5rem 0;">
                                <video src="{{ str_starts_with($settings[$kkAboutVideo['url']], 'http') ? $settings[$kkAboutVideo['url']] : asset($settings[$kkAboutVideo['url']]) }}"
                                       controls muted controlsList="nodownload noplaybackrate noremoteplayback" disablepictureinpicture
                                       style="max-width: 100%; max-height: 200px; border-radius: 10px;"></video>
                            </div>
                        @endif
                        <input type="text" name="{{ $kkAboutVideo['url'] }}" value="{{ $settings[$kkAboutVideo['url']] ?? '' }}" class="form-input" placeholder="https://… or storage/storefront/about/video.mp4">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Paste a video URL above OR upload a file below. Shown in the About Us section on the home page.</p>
                        <input type="file" name="{{ $kkAboutVideo['file'] }}" accept="video/mp4,video/webm,video/quicktime" class="form-input" style="margin-top: 0.5rem;">
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Social Media -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Social Media Links</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    {{-- Displayed on the storefront in this order: Instagram, Facebook, Twitter, LinkedIn --}}
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Instagram</label>
                        <input type="url" name="social_instagram" value="{{ $settings['social_instagram'] }}" class="form-input" placeholder="https://instagram.com/...">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Facebook</label>
                        <input type="url" name="social_facebook" value="{{ $settings['social_facebook'] }}" class="form-input" placeholder="https://facebook.com/...">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Twitter / X</label>
                        <input type="url" name="social_twitter" value="{{ $settings['social_twitter'] }}" class="form-input" placeholder="https://x.com/...">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">LinkedIn</label>
                        <input type="url" name="social_linkedin" value="{{ $settings['social_linkedin'] }}" class="form-input" placeholder="https://linkedin.com/company/...">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">YouTube <span style="color:#999;font-weight:400;">(optional)</span></label>
                        <input type="url" name="social_youtube" value="{{ $settings['social_youtube'] }}" class="form-input" placeholder="https://youtube.com/...">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">TikTok</label>
                        <input type="url" name="social_tiktok" value="{{ $settings['social_tiktok'] }}" class="form-input" placeholder="https://tiktok.com/...">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Pinterest</label>
                        <input type="url" name="social_pinterest" value="{{ $settings['social_pinterest'] }}" class="form-input" placeholder="https://pinterest.com/...">
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Contact Information</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Email</label>
                        <input type="email" name="contact_email" value="{{ $settings['contact_email'] }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Phone</label>
                        <input type="text" name="contact_phone" value="{{ $settings['contact_phone'] }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Address</label>
                        <textarea name="contact_address" rows="3" class="form-textarea">{{ $settings['contact_address'] }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Footer Content</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">About Text</label>
                        <textarea name="footer_about" rows="4" class="form-textarea">{{ $settings['footer_about'] }}</textarea>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Copyright Text</label>
                        <input type="text" name="footer_copyright" value="{{ $settings['footer_copyright'] }}" class="form-input">
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Settings</button>
        </div>
    </form>
</x-layouts.admin>
