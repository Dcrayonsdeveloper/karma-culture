<x-layouts.admin>
    <x-slot name="title">Add Banner</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.banners.index') }}" class="btn-icon" style="flex-shrink: 0; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add banner</h1>
    </div>

    <x-admin.form-errors title="The banner was not saved" />

    @php
        // One source of truth for "what size should I upload?": the same two
        // constants set the slide's aspect-ratio on the home page, so the advice
        // here cannot drift away from the box the artwork lands in. This screen
        // used to advise 1920x600 and 768x400, neither of which was the shape
        // the storefront had drawn for months.
        [$kkDeskW, $kkDeskH] = \App\Models\Banner::HERO_DESKTOP_SIZE;
        [$kkMobW, $kkMobH] = \App\Models\Banner::HERO_MOBILE_SIZE;

        // "3:2" reads better than "1.5:1", but reducing 1426x370 gives 713:185,
        // which reads worse than "3.85:1" - so the tidy form is used only when
        // it actually is tidy.
        $kkRatio = function (int $w, int $h): string {
            $divisor = 1;
            for ($i = 2; $i <= min($w, $h); $i++) {
                if ($w % $i === 0 && $h % $i === 0) {
                    $divisor = $i;
                }
            }
            $short = intdiv($h, $divisor);

            return $short <= 20
                ? intdiv($w, $divisor).':'.$short
                : round($w / $h, 2).':1';
        };
        $kkDeskRatio = $kkRatio($kkDeskW, $kkDeskH);
        $kkMobRatio = $kkRatio($kkMobW, $kkMobH);

        // The earliest moment a new schedule may be set to. The picker greys out
        // everything before it; the rule behind V::scheduleStart() is what
        // enforces it once posted.
        $kkScheduleFloor = now()->format('Y-m-d\TH:i');
    @endphp

    <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Banner Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            {{-- for/id pairs matter beyond accessibility here: the inline validator
                                 names the field from its own <label>, so an unlabelled input reports
                                 "This field is required" instead of "Name is required". --}}
                            <label for="banner-name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Name <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="name" id="banner-name" value="{{ old('name') }}" required
                                   minlength="2" maxlength="255"
                                   class="form-input" style="width: 100%;" placeholder="e.g. Summer Sale Hero Banner">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Internal only &mdash; how you will find this banner in the list.</p>
                            @error('name')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="banner-title" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Heading Text</label>
                            <input type="text" name="title" id="banner-title" value="{{ old('title') }}" maxlength="255"
                                   class="form-input" style="width: 100%;" placeholder="Banner heading">
                            @error('title')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="banner-subtitle" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Subtitle</label>
                            <input type="text" name="subtitle" id="banner-subtitle" value="{{ old('subtitle') }}" maxlength="500"
                                   class="form-input" style="width: 100%;" placeholder="Banner subtitle">
                            @error('subtitle')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="banner-button-text" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Button Text</label>
                            <input type="text" name="button_text" id="banner-button-text" value="{{ old('button_text') }}" maxlength="100"
                                   class="form-input" style="width: 100%;" placeholder="Shop Now">
                            @error('button_text')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="banner-link" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Link URL</label>
                            {{-- A relative path, a full http(s) address, mailto:, tel: or a #anchor.
                                 Anything else - javascript: above all - is refused here and by
                                 Banner::LINK_REGEX on the server, because this value is rendered
                                 straight into an href. The field used to be type="url", which
                                 refused /products - the shape most banners actually use. --}}
                            <input type="text" name="link" id="banner-link" value="{{ old('link') }}" maxlength="255"
                                   pattern="(https?://|mailto:|tel:)\S+|/(?!/)\S*|#\S*"
                                   title="Enter a path such as /products, or a full https:// address."
                                   class="form-input" style="width: 100%;" placeholder="/products">
                            @error('link')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="banner-alt-text" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Alt Text</label>
                            <input type="text" name="alt_text" id="banner-alt-text" value="{{ old('alt_text') }}" maxlength="255"
                                   class="form-input" style="width: 100%;" placeholder="e.g. Model wearing the new linen shirt">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Read aloud in place of the artwork. Leave it empty and the heading is used instead.</p>
                            @error('alt_text')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Artwork</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <x-admin.banner-media-section
                            device="desktop"
                            :width="$kkDeskW" :height="$kkDeskH" :ratio="$kkDeskRatio">
                            <div>
                                <label for="banner-image" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Image</label>
                                {{-- accept lists the formats the server rule takes, rather than image/*,
                                     which offers the admin an SVG or a TIFF the upload will then refuse. --}}
                                <input type="file" name="image" id="banner-image"
                                       accept="image/jpeg,image/png,image/webp,image/gif" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">JPG, PNG, WebP or GIF &middot; max 5MB</p>
                                @error('image')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="banner-video" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Video</label>
                                <input type="file" name="video" id="banner-video"
                                       accept="video/mp4,video/webm,video/quicktime" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">MP4, WebM or MOV &middot; max 64MB. Plays muted and looped, with no controls.</p>
                                @error('video')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <p style="font-size: 12px; color: #616161; margin: 0; padding: 0.5rem 0.65rem; background: #f6f6f7; border-radius: 6px; line-height: 1.5;">
                                Provide an <strong>image or a video</strong> &mdash; at least one is required. Supplying both
                                shows the image first while the video loads, and keeps it as the fallback where a video
                                cannot autoplay.
                            </p>
                        </x-admin.banner-media-section>

                        <x-admin.banner-media-section
                            device="mobile"
                            :width="$kkMobW" :height="$kkMobH" :ratio="$kkMobRatio">
                            <div>
                                <label for="banner-mobile-image" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Mobile Image</label>
                                <input type="file" name="mobile_image" id="banner-mobile-image"
                                       accept="image/jpeg,image/png,image/webp,image/gif" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">JPG, PNG, WebP or GIF &middot; max 5MB</p>
                                @error('mobile_image')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="banner-mobile-video" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Mobile Video</label>
                                <input type="file" name="mobile_video" id="banner-mobile-video"
                                       accept="video/mp4,video/webm,video/quicktime" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">MP4, WebM or MOV &middot; max 64MB</p>
                                @error('mobile_video')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <p style="font-size: 12px; color: #616161; margin: 0; padding: 0.5rem 0.65rem; background: #f6f6f7; border-radius: 6px; line-height: 1.5;">
                                Both optional. Leave them empty and phones show the desktop banner instead &mdash; which is
                                the right choice for artwork that reads at any width, and the wrong one for a wide
                                strip that a phone would shrink to a sliver.
                            </p>
                        </x-admin.banner-media-section>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Placement</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label for="banner-position" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Position <span style="color: #d72c0d;">*</span></label>
                            <select name="position" id="banner-position" required class="form-select" style="width: 100%;">
                                <option value="">Select position</option>
                                <option value="hero" @selected(old('position') == 'hero')>Hero</option>
                                <option value="sidebar" @selected(old('position') == 'sidebar')>Sidebar</option>
                                <option value="footer" @selected(old('position') == 'footer')>Footer</option>
                                <option value="category" @selected(old('position') == 'category')>Category</option>
                                <option value="popup" @selected(old('position') == 'popup')>Popup</option>
                            </select>
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Where on the site this banner is drawn.</p>
                            @error('position')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="banner-priority" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Display Order</label>
                            <input type="number" name="priority" id="banner-priority" value="{{ old('priority') }}"
                                   min="0" max="65535" step="1" inputmode="numeric"
                                   title="Enter a whole number between 0 and 65535."
                                   class="form-input" style="width: 100%;" placeholder="Last in its position">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Lower shows first, counted only against the other banners in the same position. Leave it empty to go last, then drag the list into order.</p>
                            @error('priority')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="banner-overlay" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Overlay Style</label>
                            <select name="overlay_style" id="banner-overlay" class="form-select" style="width: 100%;">
                                @foreach(\App\Models\Banner::OVERLAY_STYLES as $kkKey => $kkLabel)
                                    <option value="{{ $kkKey }}" @selected(old('overlay_style', 'left-dark') === $kkKey)>{{ $kkLabel }}</option>
                                @endforeach
                            </select>
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Darkens the artwork behind the heading so the text stays readable.</p>
                            @error('overlay_style')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Schedule</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label for="banner-starts-at" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Starts At</label>
                            <input type="datetime-local" name="starts_at" id="banner-starts-at" value="{{ old('starts_at') }}"
                                   min="{{ $kkScheduleFloor }}" data-schedule-start class="form-input" style="width: 100%;">
                            @error('starts_at')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="banner-ends-at" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Ends At</label>
                            <input type="datetime-local" name="ends_at" id="banner-ends-at" value="{{ old('ends_at') }}"
                                   min="{{ $kkScheduleFloor }}" data-schedule-end="banner-starts-at" class="form-input" style="width: 100%;">
                            @error('ends_at')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                        <p style="font-size: 12px; color: #616161; margin: 0;">Both optional. Left empty the banner runs from now until further notice, which is what every banner without a window does.</p>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Status</h2>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               style="width: 1rem; height: 1rem; accent-color: #303030;"
                               @checked(old('is_active', true))>
                        <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                    </div>
                    <p style="font-size: 12px; color: #616161; margin: 0.5rem 0 0 0;">The switch, not the whole answer: a banner is only live when it is switched on <em>and</em> inside its schedule.</p>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save banner</button>
            </div>
    </form>
</x-layouts.admin>
