<x-layouts.admin>
    <x-slot name="title">Site Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Site Settings</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <x-admin.form-errors title="Site settings were not saved" />

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
                        {{-- for/id pairs matter beyond accessibility here: the inline validator
                             names the field from its own <label>, so an unlabelled input reports
                             "This field is required" instead of "Site Name is required". --}}
                        <label for="site-logo" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Site Logo</label>
                        @if($settings['site_logo'])
                            <div style="margin-bottom: 0.5rem;">
                                <img src="{{ asset_v('storage/' . $settings['site_logo']) }}" alt="Current Logo" style="height: 4rem; max-width: 100%; object-fit: contain;">
                            </div>
                        @endif
                        {{-- accept lists exactly what the server rule takes. image/* offered SVG,
                             which is a script container, and the upload was not checked at all. --}}
                        <input type="file" name="site_logo" id="site-logo" accept="image/jpeg,image/png,image/webp" class="form-input">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">JPG, PNG or WebP. Max 2MB. Recommended: PNG with transparent background, 200x60px</p>
                    </div>
                    <div>
                        <label for="site-name" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Site Name</label>
                        <input type="text" name="site_name" id="site-name" value="{{ old('site_name', $settings['site_name']) }}" required minlength="2" maxlength="100" class="form-input">
                    </div>
                    <div>
                        <label for="site-tagline" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Tagline</label>
                        <input type="text" name="site_tagline" id="site-tagline" value="{{ old('site_tagline', $settings['site_tagline']) }}" maxlength="150" class="form-input">
                    </div>
                    <div>
                        <label for="site-description" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Site Description</label>
                        <textarea name="site_description" id="site-description" rows="3" maxlength="500" class="form-textarea">{{ old('site_description', $settings['site_description']) }}</textarea>
                    </div>
                    <div>
                        <label for="announcement-text" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Announcement Bar Text</label>
                        <input type="text" name="announcement_text" id="announcement-text" value="{{ old('announcement_text', $settings['announcement_text']) }}" maxlength="255" class="form-input" placeholder="e.g. Free Shipping on Orders Over ₹500!">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Displayed in the teal bar at the top of every page. Leave empty to hide.</p>
                    </div>
                    {{-- The About Us section shows three videos side by side, so all
                         three are editable here. Only the first used to be, which left
                         two cards that could never be filled from the admin. --}}
                    @foreach([
                        ['url' => 'about_us_video_url',   'file' => 'about_us_video_file',   'remove' => 'about_us_video_remove',   'label' => 'About Us - Video 1'],
                        ['url' => 'about_us_video_url_2', 'file' => 'about_us_video_file_2', 'remove' => 'about_us_video_remove_2', 'label' => 'About Us - Video 2'],
                        ['url' => 'about_us_video_url_3', 'file' => 'about_us_video_file_3', 'remove' => 'about_us_video_remove_3', 'label' => 'About Us - Video 3'],
                    ] as $kkAboutVideo)
                    <div style="border-top: 1px solid #e3e3e3; padding-top: 1rem;">
                        <label for="{{ $kkAboutVideo['url'] }}" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">{{ $kkAboutVideo['label'] }}</label>
                        @if($settings[$kkAboutVideo['url']] ?? '')
                            <div style="margin: 0.5rem 0;">
                                <video src="{{ str_starts_with($settings[$kkAboutVideo['url']], 'http') ? $settings[$kkAboutVideo['url']] : asset_v($settings[$kkAboutVideo['url']]) }}"
                                       controls muted controlsList="nodownload noplaybackrate noremoteplayback" disablepictureinpicture
                                       style="max-width: 100%; max-height: 200px; border-radius: 10px;"></video>
                            </div>
                        @endif
                        {{-- The path is written by the upload below, never typed: a hand-edited
                             one only ever pointed at a file that was not there. Still submitted
                             (readonly inputs are), so an unchanged slot round-trips as-is. --}}
                        <input type="text" name="{{ $kkAboutVideo['url'] }}" id="{{ $kkAboutVideo['url'] }}"
                               value="{{ old($kkAboutVideo['url'], $settings[$kkAboutVideo['url']] ?? '') }}" maxlength="255"
                               readonly
                               class="form-input" placeholder="No video yet - upload one below"
                               style="background-color: #f7f7f7; color: #616161;">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Set by the upload below. MP4, WebM or MOV, max 64MB. Shown in the About Us section on the home page.</p>
                        <input type="file" name="{{ $kkAboutVideo['file'] }}" id="{{ $kkAboutVideo['file'] }}"
                               aria-label="{{ $kkAboutVideo['label'] }} upload"
                               accept="video/mp4,video/webm,video/quicktime" class="form-input" style="margin-top: 0.5rem;">

                        {{-- The path above is readonly, so before this there was no way to
                             take a clip down at all - only to replace it with another.
                             A removed slot leaves the row behind holding an empty value,
                             which is how the home page knows the admin cleared it rather
                             than never having set it. --}}
                        @if($settings[$kkAboutVideo['url']] ?? '')
                            <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.6rem; font-size: 13px; color: #303030; cursor: pointer;">
                                <input type="checkbox" name="{{ $kkAboutVideo['remove'] }}" value="1"
                                       style="width: 0.9rem; height: 0.9rem; accent-color: #d72c0d;">
                                Remove this video
                            </label>
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                The card disappears from the home page and the file is deleted.
                                Choosing a new file above replaces it instead.
                            </p>
                        @endif
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
                    {{-- Displayed on the storefront in this order: Instagram, Facebook, Twitter, LinkedIn.
                         Each is rendered into an href, so the scheme is pinned to http/https on both
                         sides: type="url" alone would accept javascript: quite happily. --}}
                    @foreach([
                        ['key' => 'social_instagram', 'label' => 'Instagram',  'placeholder' => 'https://instagram.com/...'],
                        ['key' => 'social_facebook',  'label' => 'Facebook',   'placeholder' => 'https://facebook.com/...'],
                        ['key' => 'social_twitter',   'label' => 'Twitter / X','placeholder' => 'https://x.com/...'],
                        ['key' => 'social_linkedin',  'label' => 'LinkedIn',   'placeholder' => 'https://linkedin.com/company/...'],
                        ['key' => 'social_youtube',   'label' => 'YouTube',    'placeholder' => 'https://youtube.com/...'],
                        ['key' => 'social_tiktok',    'label' => 'TikTok',     'placeholder' => 'https://tiktok.com/...'],
                        ['key' => 'social_pinterest', 'label' => 'Pinterest',  'placeholder' => 'https://pinterest.com/...'],
                    ] as $kkSocial)
                        <div>
                            <label for="{{ $kkSocial['key'] }}" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">
                                {{ $kkSocial['label'] }}
                            </label>
                            <input type="url" name="{{ $kkSocial['key'] }}" id="{{ $kkSocial['key'] }}"
                                   value="{{ old($kkSocial['key'], $settings[$kkSocial['key']]) }}" maxlength="255"
                                   pattern="https?://.+" title="Enter a full web address starting with http:// or https://"
                                   class="form-input" placeholder="{{ $kkSocial['placeholder'] }}">
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Contact Info -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Contact Information</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label for="contact-email" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Email</label>
                        {{-- type="email" on its own accepts "hello@karmaa" - a bare host with no
                             TLD - which the server rule then rejects. The pattern closes that gap
                             so the mismatch is caught in the field rather than after submitting. --}}
                        <input type="email" name="contact_email" id="contact-email" value="{{ old('contact_email', $settings['contact_email']) }}"
                               maxlength="255" autocomplete="email"
                               pattern=".+@.+\..+" title="Enter a full email address, like hello@example.com"
                               class="form-input">
                    </div>
                    <div>
                        <label for="contact-phone" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Phone</label>
                        {{-- Pattern mirrors App\Rules\IndianMobile: an optional +91 or 0 prefix,
                             then ten digits opening 6-9, spacing and hyphens tolerated. Anything
                             narrower would reject numbers the server accepts. --}}
                        <input type="tel" name="contact_phone" id="contact-phone" value="{{ old('contact_phone', $settings['contact_phone']) }}"
                               inputmode="numeric" autocomplete="tel" maxlength="20"
                               pattern="(\+?91[\s\-]?)?0?[6-9][0-9\s\-]{9,}"
                               title="Enter a 10-digit Indian mobile number starting with 6, 7, 8 or 9."
                               class="form-input">
                    </div>
                    <div>
                        <label for="whatsapp-number" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">WhatsApp Number</label>
                        {{-- The floating chat button on every storefront page reads this
                             setting, and there was no field for it anywhere in the admin -
                             so the button could only be pointed at a number by editing the
                             database directly. --}}
                        <input type="tel" name="whatsapp_number" id="whatsapp-number" value="{{ old('whatsapp_number', $settings['whatsapp_number']) }}"
                               inputmode="numeric" autocomplete="tel" maxlength="20"
                               pattern="(\+?91[\s\-]?)?0?[6-9][0-9\s\-]{9,}"
                               title="Enter a 10-digit Indian mobile number starting with 6, 7, 8 or 9."
                               class="form-input" placeholder="Leave empty to hide the chat button">
                    </div>
                    <div>
                        <label for="contact-address" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Address</label>
                        <textarea name="contact_address" id="contact-address" rows="3" maxlength="500" class="form-textarea">{{ old('contact_address', $settings['contact_address']) }}</textarea>
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
                        <label for="footer-about" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">About Text</label>
                        <textarea name="footer_about" id="footer-about" rows="4" maxlength="1000" class="form-textarea">{{ old('footer_about', $settings['footer_about']) }}</textarea>
                    </div>
                    <div>
                        <label for="footer-copyright" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Copyright Text</label>
                        <input type="text" name="footer_copyright" id="footer-copyright" value="{{ old('footer_copyright', $settings['footer_copyright']) }}" maxlength="255" class="form-input">
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Settings</button>
        </div>
    </form>
</x-layouts.admin>
