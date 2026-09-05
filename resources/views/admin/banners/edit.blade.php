<x-layouts.admin>
    <x-slot name="title">Edit Banner</x-slot>

    @php
        // Same two constants the home page reads for the slide's aspect-ratio,
        // so the recommended size here cannot drift away from the box the
        // artwork actually lands in.
        [$kkDeskW, $kkDeskH] = \App\Models\Banner::HERO_DESKTOP_SIZE;
        [$kkMobW, $kkMobH] = \App\Models\Banner::HERO_MOBILE_SIZE;

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

        // A schedule that has already begun stays selectable, so editing
        // anything else on this form does not drag its dates forward. Only a
        // CHANGED date has to be in the future - the rule behind
        // V::scheduleStart() agrees, and is handed the stored value to say so.
        $kkNow = now()->format('Y-m-d\TH:i');
        $kkStartOriginal = $banner->starts_at?->format('Y-m-d\TH:i');
        $kkEndOriginal = $banner->ends_at?->format('Y-m-d\TH:i');
        $kkStartFloor = $kkStartOriginal && $kkStartOriginal < $kkNow ? $kkStartOriginal : $kkNow;
        $kkEndFloor = $kkEndOriginal && $kkEndOriginal < $kkNow ? $kkEndOriginal : $kkNow;

        $kkStateStyles = [
            'live' => 'background: #cdfee1; color: #1a7a2e;',
            'scheduled' => 'background: #e0f0ff; color: #005bd3;',
            'expired' => 'background: #fff3cd; color: #8a6d00;',
            'hidden' => 'background: #ebebeb; color: #616161;',
        ];
    @endphp

    <!-- Top bar -->
    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.banners.index') }}" class="btn-icon" style="flex-shrink: 0; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $banner->name ?: $banner->title ?: 'Banner #'.$banner->id }}</h1>
        {{-- The effective state, not the raw switch: "Active" beside a banner
             nobody can see because its window has closed was true and useless. --}}
        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; {{ $kkStateStyles[$banner->state] ?? $kkStateStyles['hidden'] }}">
            {{ $banner->state_label }}
        </span>
        <a href="{{ route('admin.banners.preview', $banner) }}" style="margin-left: auto; font-size: 13px; color: #005bd3; text-decoration: none;"
           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Preview</a>
    </div>

    <x-admin.form-errors title="The banner was not saved" />

    <form action="{{ route('admin.banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

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
                            <input type="text" name="name" id="banner-name" value="{{ old('name', $banner->name) }}" required
                                   minlength="2" maxlength="255"
                                   class="form-input" style="width: 100%;">
                            <x-field-error field="name" />
                        </div>

                        <div>
                            <label for="banner-title" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Heading Text</label>
                            <input type="text" name="title" id="banner-title" value="{{ old('title', $banner->title) }}" maxlength="255"
                                   class="form-input" style="width: 100%;" placeholder="Banner heading">
                            @error('title')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="banner-subtitle" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Subtitle</label>
                            <input type="text" name="subtitle" id="banner-subtitle" value="{{ old('subtitle', $banner->subtitle) }}" maxlength="500"
                                   class="form-input" style="width: 100%;" placeholder="Banner subtitle">
                            @error('subtitle')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="banner-button-text" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Button Text</label>
                            <input type="text" name="button_text" id="banner-button-text" value="{{ old('button_text', $banner->button_text) }}" maxlength="100"
                                   class="form-input" style="width: 100%;" placeholder="Shop Now">
                            @error('button_text')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="banner-link" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Link URL</label>
<<<<<<< HEAD
                            <input type="url" name="link" id="banner-link" value="{{ old('link', $banner->link) }}"
                                   maxlength="255" pattern="https?://.+" title="Enter a full web address starting with http:// or https://"
                                   class="form-input" style="width: 100%;" placeholder="https://example.com/page">
                            <x-field-error field="link" />
=======
                            {{-- A relative path, a full http(s) address, mailto:, tel: or a #anchor.
                                 Anything else - javascript: above all - is refused here and by
                                 Banner::LINK_REGEX on the server, because this value is rendered
                                 straight into an href. --}}
                            <input type="text" name="link" id="banner-link" value="{{ old('link', $banner->link) }}" maxlength="255"
                                   pattern="(https?://|mailto:|tel:)\S+|/(?!/)\S*|#\S*"
                                   title="Enter a path such as /products, or a full https:// address."
                                   class="form-input" style="width: 100%;" placeholder="/products">
                            @error('link')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
>>>>>>> e3a8ce0550d8732347a02aa9589f2867ee5b491f
                        </div>

                        <div>
                            <label for="banner-alt-text" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Alt Text</label>
                            <input type="text" name="alt_text" id="banner-alt-text" value="{{ old('alt_text', $banner->alt_text) }}" maxlength="255"
                                   class="form-input" style="width: 100%;" placeholder="e.g. Model wearing the new linen shirt">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Read aloud in place of the artwork. Leave it empty and the heading is used instead &mdash; currently &ldquo;{{ $banner->alt ?: 'nothing' }}&rdquo;.</p>
                            @error('alt_text')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Artwork</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
<<<<<<< HEAD
                        <div>
                            <label for="banner-image" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Banner Image</label>
                            @if($banner->image_url)
                                <div style="margin-bottom: 0.5rem;">
                                    <img src="{{ asset_v('storage/' . $banner->image_url) }}" alt="{{ $banner->name }}"
                                         style="max-width: 100%; height: auto; max-height: 8rem; object-fit: cover; border-radius: 0.5rem; border: 1px solid #e3e3e3;">
                                </div>
                            @endif
                            {{-- accept lists the formats the server rule takes, rather than image/*,
                                 which offers the admin an SVG or a TIFF the upload will then refuse. --}}
                            <input type="file" name="image" id="banner-image"
                                   accept="image/jpeg,image/png,image/webp,image/gif" style="font-size: 13px; color: #616161;">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Leave empty to keep current image. JPG, PNG, WebP or GIF. Max 5MB.</p>
                            <x-field-error field="image" />
                        </div>

                        <div>
                            <label for="banner-mobile-image" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Mobile Image</label>
                            @if($banner->mobile_image_url)
                                <div style="margin-bottom: 0.5rem;">
                                    <img src="{{ asset_v('storage/' . $banner->mobile_image_url) }}" alt="{{ $banner->name }} (mobile)"
                                         style="max-width: 100%; height: auto; max-height: 6rem; object-fit: cover; border-radius: 0.5rem; border: 1px solid #e3e3e3;">
                                </div>
                            @endif
                            <input type="file" name="mobile_image" id="banner-mobile-image"
                                   accept="image/jpeg,image/png,image/webp,image/gif" style="font-size: 13px; color: #616161;">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Optional. Leave empty to keep current. JPG, PNG, WebP or GIF. Max 5MB.</p>
                            <x-field-error field="mobile_image" />
                        </div>
=======
                        <x-admin.banner-media-section
                            device="desktop"
                            :width="$kkDeskW" :height="$kkDeskH" :ratio="$kkDeskRatio">
                            <div>
                                <label for="banner-image" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Replace Image</label>
                                {{-- The accessor, not asset_v('storage/'.$banner->image_url): a
                                     banner whose media is an absolute URL or a web-root path - the
                                     hero clip shipped as one - resolved to /storage/https:/... and
                                     showed a broken frame on this very form. --}}
                                @if($banner->image_url)
                                    <div style="margin-bottom: 0.5rem;">
                                        <img src="{{ $banner->image }}" alt="{{ $banner->alt }}"
                                             style="max-width: 100%; height: auto; max-height: 8rem; object-fit: cover; border-radius: 0.5rem; border: 1px solid #e3e3e3;">
                                    </div>
                                @endif
                                {{-- accept lists the formats the server rule takes, rather than image/*,
                                     which offers the admin an SVG or a TIFF the upload will then refuse. --}}
                                <input type="file" name="image" id="banner-image"
                                       accept="image/jpeg,image/png,image/webp,image/gif" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    JPG, PNG, WebP or GIF &middot; max {{ \App\Rules\ValidationRules::megabytes(\App\Support\BannerMedia::MAX_IMAGE_KB) }}MB.
                                    @if($banner->image_url) Leave empty to keep the current image. @endif
                                </p>
                                @error('image')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="banner-video" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Replace Video</label>
                                @if($banner->video_url)
                                    <div style="margin-bottom: 0.5rem;">
                                        <video src="{{ $banner->video }}" muted playsinline preload="metadata" controls
                                               style="max-width: 100%; max-height: 8rem; border-radius: 0.5rem; border: 1px solid #e3e3e3;"></video>
                                    </div>
                                @endif
                                <input type="file" name="video" id="banner-video"
                                       accept="video/mp4,video/webm,video/quicktime" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    MP4, WebM or MOV &middot; max 64MB.
                                    @if($banner->video_url) Leave empty to keep the current video. @endif
                                </p>
                                @if($banner->video_url)
                                    <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 12px; color: #616161; margin-top: 0.4rem; cursor: pointer;">
                                        <input type="checkbox" name="remove_video" value="1" style="margin: 0;" @checked(old('remove_video'))>
                                        Remove the video and show the image instead
                                    </label>
                                @endif
                                @error('remove_video')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                                @error('video')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                        </x-admin.banner-media-section>

                        <x-admin.banner-media-section
                            device="mobile"
                            :width="$kkMobW" :height="$kkMobH" :ratio="$kkMobRatio">
                            <div>
                                <label for="banner-mobile-image" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Replace Mobile Image</label>
                                @if($banner->mobile_image_url)
                                    <div style="margin-bottom: 0.5rem;">
                                        <img src="{{ $banner->mobile_image }}" alt="{{ $banner->name }} on phones"
                                             style="max-width: 100%; height: auto; max-height: 8rem; object-fit: cover; border-radius: 0.5rem; border: 1px solid #e3e3e3;">
                                    </div>
                                @endif
                                <input type="file" name="mobile_image" id="banner-mobile-image"
                                       accept="image/jpeg,image/png,image/webp,image/gif" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    JPG, PNG, WebP or GIF &middot; max {{ \App\Rules\ValidationRules::megabytes(\App\Support\BannerMedia::MAX_IMAGE_KB) }}MB.
                                    @if($banner->mobile_image_url) Leave empty to keep the current one. @endif
                                </p>
                                @if($banner->mobile_image_url)
                                    <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 12px; color: #616161; margin-top: 0.4rem; cursor: pointer;">
                                        <input type="checkbox" name="remove_mobile_image" value="1" style="margin: 0;" @checked(old('remove_mobile_image'))>
                                        Remove it and use the desktop image on phones
                                    </label>
                                @endif
                                @error('mobile_image')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="banner-mobile-video" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Replace Mobile Video</label>
                                @if($banner->mobile_video_url)
                                    <div style="margin-bottom: 0.5rem;">
                                        <video src="{{ $banner->mobile_video }}" muted playsinline preload="metadata" controls
                                               style="max-width: 100%; max-height: 8rem; border-radius: 0.5rem; border: 1px solid #e3e3e3;"></video>
                                    </div>
                                @endif
                                <input type="file" name="mobile_video" id="banner-mobile-video"
                                       accept="video/mp4,video/webm,video/quicktime" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    MP4, WebM or MOV &middot; max 64MB.
                                    @if($banner->mobile_video_url) Leave empty to keep the current one. @endif
                                </p>
                                @if($banner->mobile_video_url)
                                    <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 12px; color: #616161; margin-top: 0.4rem; cursor: pointer;">
                                        <input type="checkbox" name="remove_mobile_video" value="1" style="margin: 0;" @checked(old('remove_mobile_video'))>
                                        Remove it and use the desktop banner on phones
                                    </label>
                                @endif
                                @error('mobile_video')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                        </x-admin.banner-media-section>
>>>>>>> e3a8ce0550d8732347a02aa9589f2867ee5b491f
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
                                <option value="hero" @selected(old('position', $banner->position) == 'hero')>Hero</option>
                                <option value="sidebar" @selected(old('position', $banner->position) == 'sidebar')>Sidebar</option>
                                <option value="footer" @selected(old('position', $banner->position) == 'footer')>Footer</option>
                                <option value="category" @selected(old('position', $banner->position) == 'category')>Category</option>
                                <option value="popup" @selected(old('position', $banner->position) == 'popup')>Popup</option>
                            </select>
                            <x-field-error field="position" />
                        </div>

                        <div>
                            <label for="banner-priority" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Display Order</label>
                            <input type="number" name="priority" id="banner-priority" value="{{ old('priority', $banner->priority) }}"
                                   min="0" max="65535" step="1" inputmode="numeric"
                                   title="Enter a whole number between 0 and 65535."
                                   class="form-input" style="width: 100%;">
<<<<<<< HEAD
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Lower number = higher priority</p>
                            <x-field-error field="priority" />
=======
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Lower shows first, counted only against the other banners in the same position.</p>
                            @error('priority')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
>>>>>>> e3a8ce0550d8732347a02aa9589f2867ee5b491f
                        </div>

                        <div>
                            <label for="banner-overlay" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Overlay Style</label>
                            <select name="overlay_style" id="banner-overlay" class="form-select" style="width: 100%;">
                                @foreach(\App\Models\Banner::OVERLAY_STYLES as $kkKey => $kkLabel)
                                    <option value="{{ $kkKey }}" @selected(old('overlay_style', $banner->overlay_style ?? 'left-dark') === $kkKey)>{{ $kkLabel }}</option>
                                @endforeach
                            </select>
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
                            <input type="datetime-local" name="starts_at" id="banner-starts-at"
                                   value="{{ old('starts_at', $kkStartOriginal) }}"
                                   min="{{ $kkStartFloor }}" data-schedule-start data-schedule-original="{{ $kkStartOriginal }}"
                                   class="form-input" style="width: 100%;">
                            @error('starts_at')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="banner-ends-at" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Ends At</label>
                            <input type="datetime-local" name="ends_at" id="banner-ends-at"
                                   value="{{ old('ends_at', $kkEndOriginal) }}"
                                   min="{{ $kkEndFloor }}" data-schedule-end="banner-starts-at" data-schedule-original="{{ $kkEndOriginal }}"
                                   class="form-input" style="width: 100%;">
                            @error('ends_at')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                        <p style="font-size: 12px; color: #616161; margin: 0;">Clear both to run from now until further notice.</p>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Status</h2>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               style="width: 1rem; height: 1rem; accent-color: #303030;"
                               @checked(old('is_active', $banner->is_active))>
                        <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                    </div>
                    <p style="font-size: 12px; color: #616161; margin: 0.5rem 0 0 0;">Right now: {{ $banner->state_label }}.</p>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Info</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; gap: 0.5rem;">
                            <span style="color: #616161;">Created</span>
                            <span style="font-weight: 500; color: #303030;">{{ $banner->created_at?->format('M d, Y') ?? '--' }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 0.5rem;">
                            <span style="color: #616161;">Updated</span>
                            <span style="font-weight: 500; color: #303030;">{{ $banner->updated_at?->format('M d, Y') ?? '--' }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 0.5rem;">
                            <span style="color: #616161;">On phones</span>
                            <span style="font-weight: 500; color: #303030;">
                                {{ $banner->has_mobile_media
                                    ? ($banner->has_mobile_video ? 'Own video' : 'Own image')
                                    : 'Uses desktop' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                {{-- The delete form is a sibling below, never a child: a <form>
                     inside a <form> is invalid, silently drops the inner one in
                     every browser, and AdminFormNestingTest scans for it. --}}
                <button type="submit" form="record-delete-form" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer; padding: 0.5rem 0; min-height: 2.25rem;">Delete banner</button>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>

        <form id="record-delete-form" action="{{ route('admin.banners.destroy', $banner) }}" method="POST"
              onsubmit="return confirm('Delete this banner? Its uploaded files are removed; the banner itself can be restored from View deleted.')">
            @csrf @method('DELETE')
        </form>
</x-layouts.admin>
