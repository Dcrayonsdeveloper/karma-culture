<x-layouts.admin>
    <x-slot name="title">Hero Banners</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Hero Banners</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <x-admin.form-errors title="The banner was not saved" />

    @php
        // One source of truth for "what size should I upload?": the same two
        // constants set the slide's aspect-ratio on the home page, so the advice
        // here cannot drift away from the box the artwork lands in.
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

        // Four states, only one of which reaches a shopper. The badge used to
        // read straight off the Active switch, so a banner whose window had not
        // opened yet - or had already closed - said Active while the home page
        // showed nothing, and the screen offered no way to tell which. That
        // disagreement is the reason scheduling was taken off this table once
        // before, so the badge answers Banner::$state now and not the switch.
        $kkStateChips = [
            'live' => ['Live', '#cdfee1', '#1a7a2e'],
            'scheduled' => ['Scheduled', '#ffeacc', '#8a5300'],
            'expired' => ['Expired', '#ffe9e8', '#8e1f0b'],
            'hidden' => ['Hidden', '#ebebeb', '#616161'],
        ];

        // Now, floored to the minute the way the datetime-local inputs are, so
        // the min= floor below matches what NotPastDateTime will accept.
        $kkNow = now()->format('Y-m-d\TH:i');
    @endphp

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    <p style="font-size: 12px; color: #616161; margin: 0 0 1rem 0;">
        The slides that run across the top of the home page. Every banner needs an image or a video; the heading, subtitle and button are optional overlays drawn on top of it. Drag a card, or use the arrows, to change the order they play in.
    </p>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
        <!-- Add New Banner -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Add New Banner</h2>
            </div>
            <div style="padding: 1rem;">
                <form action="{{ route('admin.homepage.hero-banners.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        {{-- for/id pairs matter beyond accessibility here: the inline validator
                             names the field from its own <label>, so an unlabelled input reports
                             "This field is required" instead of naming it. --}}
                        <div>
                            <label for="hero-new-name" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Name</label>
                            <input type="text" name="name" id="hero-new-name" maxlength="255" class="form-input" placeholder="Banner name">
                        </div>
                        <x-admin.banner-media-section
                            device="desktop"
                            :width="$kkDeskW" :height="$kkDeskH" :ratio="$kkDeskRatio">
                            <div>
                                <label for="hero-new-image" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Image</label>
                                <input type="file" name="image" id="hero-new-image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">JPG, PNG, WebP or GIF &middot; max {{ \App\Rules\ValidationRules::megabytes(\App\Support\BannerMedia::MAX_IMAGE_KB) }}MB</p>
                            </div>
                            <div>
                                <label for="hero-new-video" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Video</label>
                                <input type="file" name="video" id="hero-new-video" accept="video/mp4,video/webm,video/quicktime" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">MP4, WebM or MOV &middot; max 64MB. Plays muted and looped, with no controls.</p>
                            </div>
                            <p style="font-size: 12px; color: #616161; margin: 0; padding: 0.5rem 0.65rem; background: #f6f6f7; border-radius: 6px; line-height: 1.5;">
                                Provide an <strong>image or a video</strong> - at least one is required. Supplying both
                                shows the image first while the video loads, and keeps it as the fallback where a video
                                cannot autoplay.
                            </p>
                        </x-admin.banner-media-section>

                        <x-admin.banner-media-section
                            device="mobile"
                            :width="$kkMobW" :height="$kkMobH" :ratio="$kkMobRatio">
                            <div>
                                <label for="hero-new-mobile-image" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Mobile Image</label>
                                <input type="file" name="mobile_image" id="hero-new-mobile-image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">JPG, PNG, WebP or GIF &middot; max {{ \App\Rules\ValidationRules::megabytes(\App\Support\BannerMedia::MAX_IMAGE_KB) }}MB</p>
                            </div>
                            <div>
                                <label for="hero-new-mobile-video" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Mobile Video</label>
                                <input type="file" name="mobile_video" id="hero-new-mobile-video" accept="video/mp4,video/webm,video/quicktime" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">MP4, WebM or MOV &middot; max 64MB</p>
                            </div>
                            <p style="font-size: 12px; color: #616161; margin: 0; padding: 0.5rem 0.65rem; background: #f6f6f7; border-radius: 6px; line-height: 1.5;">
                                Both optional. Leave them empty and phones show the desktop banner instead - which is
                                the right choice for artwork that reads at any width, and the wrong one for a wide
                                strip that a phone would shrink to a sliver.
                            </p>
                        </x-admin.banner-media-section>
                        <div>
                            <label for="hero-new-alt" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Image Description</label>
                            <input type="text" name="alt_text" id="hero-new-alt" maxlength="255" class="form-input" placeholder="Model wearing the linen co-ord set">
                            {{-- Read out instead of the artwork by a screen reader, and shown
                                 in its place when the file fails to load. Optional: left empty
                                 the heading is used, which is what was read out before this
                                 field existed. --}}
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">What the artwork shows. Leave empty to use the heading.</p>
                        </div>
                        <div>
                            <label for="hero-new-title" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Heading Text</label>
                            <input type="text" name="title" id="hero-new-title" maxlength="255" class="form-input" placeholder="Banner heading">
                        </div>
                        <div>
                            <label for="hero-new-subtitle" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Subtitle</label>
                            <input type="text" name="subtitle" id="hero-new-subtitle" maxlength="500" class="form-input" placeholder="Banner subtitle">
                        </div>
                        <div>
                            <label for="hero-new-button-text" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Button Text</label>
                            <input type="text" name="button_text" id="hero-new-button-text" maxlength="100" class="form-input" placeholder="Shop Now">
                        </div>
                        <div>
                            <label for="hero-new-link" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Link URL</label>
                            {{-- A relative path, a full http(s) address, mailto:, tel: or a #anchor.
                                 Anything else - javascript: above all - is refused here and on the
                                 server, because this value is rendered straight into an href. --}}
                            <input type="text" name="link" id="hero-new-link" maxlength="255"
                                   pattern="(https?://|mailto:|tel:)\S+|/(?!/)\S*|#\S*"
                                   title="Enter a path such as /products, or a full https:// address."
                                   class="form-input" placeholder="/products">
                        </div>
                        <div>
                            <label for="hero-new-overlay" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Overlay Style</label>
                            <select name="overlay_style" id="hero-new-overlay" class="form-select">
                                @foreach(\App\Models\Banner::OVERLAY_STYLES as $key => $label)
                                    <option value="{{ $key }}" {{ $key === 'left-dark' ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Both ends optional. Left empty the banner runs from the moment
                             it is switched on until it is switched off again, which is what
                             every banner made before this field existed already does. --}}
                        <div>
                            <label for="hero-new-starts-at" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Starts</label>
                            <input type="datetime-local" name="starts_at" id="hero-new-starts-at" value="{{ old('starts_at') }}"
                                   min="{{ $kkNow }}" data-schedule-start
                                   class="form-input" style="width: 100%;">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Leave empty to go live as soon as it is switched on.</p>
                        </div>
                        <div>
                            <label for="hero-new-ends-at" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Ends</label>
                            <input type="datetime-local" name="ends_at" id="hero-new-ends-at" value="{{ old('ends_at') }}"
                                   min="{{ $kkNow }}" data-schedule-end="hero-new-starts-at"
                                   class="form-input" style="width: 100%;">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Leave empty to run until it is hidden.</p>
                        </div>
                        <button type="submit" class="btn btn-primary" style="font-size: 13px; width: 100%;">Add Banner</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Existing Banners -->
        <div x-data="bannerSorter()">
            @php
                // The badge is a promise about the storefront, so it has to count the
                // way the storefront does. That means Banner::$is_visible and not the
                // Active switch: a banner switched on for a campaign that starts next
                // Monday is not in this week's running order, and numbering it #2 told
                // the admin a slide was playing that nobody could see.
                $kkLivePositions = [];
                $kkLiveSlot = 0;
                foreach ($banners as $kkBanner) {
                    if ($kkBanner->is_visible) {
                        $kkLivePositions[$kkBanner->id] = ++$kkLiveSlot;
                    }
                }
            @endphp
            <div style="display: flex; flex-direction: column; gap: 0.75rem;" x-ref="list"
                 x-on:dragover.prevent x-on:drop.prevent="onDrop()">
                @forelse($banners as $index => $banner)
                    <div class="card" style="padding: 1rem; transition: all 0.15s;"
                         x-data="{ editing: false }"
                         draggable="true"
                         data-id="{{ $banner->id }}"
                         {{-- Read by updateBadges() after a drag, so the renumbering it does
                              on screen counts the same rows the server counted above. --}}
                         data-live="{{ $banner->is_visible ? '1' : '0' }}"
                         x-on:dragstart="onDragStart($event)"
                         x-on:dragover.prevent="onDragOver($event)"
                         x-on:dragend="onDragEnd()"
                         {{-- Object form, not a string. Alpine binds a string :style with
                              setAttribute('style', value), which replaces the whole attribute -
                              and this expression is '' whenever nothing is being dragged, so the
                              static padding above was wiped off every card on first render. The
                              object form writes one property at a time and leaves the rest alone. --}}
                         :style="{
                             opacity: draggingId == {{ $banner->id }} ? '0.5' : '',
                             transform: draggingId == {{ $banner->id }} ? 'scale(0.98)' : '',
                             borderTop: dropTargetId == {{ $banner->id }} && draggingId != {{ $banner->id }} ? '2px solid #005bd3' : ''
                         }">

                        <!-- Display Mode -->
                        <div x-show="!editing">
                            <div style="display: flex; flex-wrap: wrap; align-items: flex-start; gap: 0.75rem;">
                                <!-- Drag Handle + Position -->
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem; flex-shrink: 0; padding-top: 0.25rem;">
                                    <span class="pos-badge" title="{{ $banner->is_visible ? 'Position on the storefront' : $banner->state_label.' - not shown on the storefront' }}" style="font-size: 12px; font-weight: 700; color: #616161; width: 1.5rem; height: 1.5rem; display: flex; align-items: center; justify-content: center; background: #f6f6f7; border-radius: 0.25rem;">{{ isset($kkLivePositions[$banner->id]) ? '#'.$kkLivePositions[$banner->id] : '--' }}</span>
                                    <div style="cursor: grab; color: #616161;" title="Drag to reorder">
                                        <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                        </svg>
                                    </div>
                                    {{-- Disabled while a reorder is in flight. A second click used to
                                         start a second POST describing a different order, and the two
                                         then raced: whichever answered last decided what the page
                                         claimed had happened, so a stale rejection could report "not
                                         saved" for an order the later request had just stored. The
                                         :style is the object form on purpose - a string :style
                                         replaces the whole attribute and would wipe the static
                                         padding and colour above. --}}
                                    <button type="button" @click="moveUp($el)" class="kk-nudge" :disabled="saving"
                                            :style="{ opacity: saving ? '0.4' : '', cursor: saving ? 'progress' : '' }"
                                            style="color: #616161; background: none; border: none; cursor: pointer; padding: 0.125rem;" title="Move up">
                                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                    <button type="button" @click="moveDown($el)" class="kk-nudge" :disabled="saving"
                                            :style="{ opacity: saving ? '0.4' : '', cursor: saving ? 'progress' : '' }"
                                            style="color: #616161; background: none; border: none; cursor: pointer; padding: 0.125rem;" title="Move down">
                                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                </div>

                                <!-- Thumbnail: a banner may be video-only, so do not assume an image exists -->
                                {{-- Contained, deliberately unlike the slide. The storefront
                                     crops a banner to fill its box; this thumbnail is how the
                                     admin identifies which banner a row is, and a crop of a
                                     wide banner cuts off the headline baked into the artwork -
                                     the one thing worth recognising here. The admin layout
                                     carries no delegated media-error handler, so a banner whose
                                     file has gone marks its own frame rather than sitting as an
                                     empty grey box. --}}
                                @php
                                    // A banner saved with neither file leaves the same hole a 404
                                    // does, so it gets the same designed "missing" surface up front.
                                    $kkThumbClass = 'kk-media'
                                        . ($banner->image_url ? '' : ' kk-media--dark')
                                        . ($banner->image_url || $banner->video_url ? '' : ' is-broken');
                                @endphp
                                <div class="{{ $kkThumbClass }}" style="width: 11rem; max-width: 100%; height: 6rem; border-radius: 0.5rem; flex-shrink: 0;">
                                    @if($banner->image_url)
                                        <img class="kk-media__fill" src="{{ $banner->image }}" alt="" aria-hidden="true" onerror="this.remove()">
                                        <img src="{{ $banner->image }}" alt="{{ $banner->name }}" onerror="this.closest('.kk-media').classList.add('is-broken')">
                                    @elseif($banner->video_url)
                                        <video class="kk-media__fill" src="{{ $banner->video }}" muted playsinline preload="metadata" aria-hidden="true" tabindex="-1" onerror="this.remove()"></video>
                                        <video src="{{ $banner->video }}" muted playsinline preload="metadata" onerror="this.closest('.kk-media').classList.add('is-broken')"></video>
                                    @endif
                                    <span class="kk-media__fallback" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                                            <circle cx="8.5" cy="9.5" r="1.5"/>
                                            <path d="M21 15l-5-5L5 20"/>
                                        </svg>
                                        File missing
                                    </span>
                                    @if($banner->video_url)
                                        {{-- z-index 3: above the frame's own fallback layer (2). --}}
                                        <span style="position: absolute; z-index: 3; bottom: 0.25rem; left: 0.25rem; display: inline-flex; align-items: center; gap: 0.2rem; padding: 0.1rem 0.4rem; border-radius: 0.75rem; font-size: 11px; font-weight: 600; background: rgba(0,0,0,0.72); color: #fff;">
                                            <svg style="width: 0.7rem; height: 0.7rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>Video
                                        </span>
                                    @endif
                                </div>

                                <!-- Info -->
                                <div style="flex: 1 1 14rem; min-width: 0;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem;">
                                        <span style="font-size: 13px; font-weight: 600; color: #303030;">{{ $banner->name ?: $banner->title ?: 'Banner #' . $banner->id }}</span>
                                        {{-- A block assignment, deliberately. Blade's one-line
                                             directive form does not survive a destructuring
                                             assignment: it emits an opening PHP tag with no
                                             closing one, which turns the rest of the file into
                                             PHP source and 500s this screen on any shop that
                                             actually has a banner. It compiled and passed
                                             locally because an empty banners table never enters
                                             this loop.

                                             Note also that a directive name must not be written
                                             inside a Blade comment: statements are compiled
                                             BEFORE comments are stripped, so the name is turned
                                             into real PHP first and the half-eaten comment then
                                             swallows the markup below it. Both halves of that
                                             lesson were learned here, one after the other. --}}
                                        @php
                                            [$kkChipText, $kkChipBg, $kkChipFg] = $kkStateChips[$banner->state] ?? $kkStateChips['hidden'];
                                        @endphp
                                        <span title="{{ $banner->state_label }}" style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: {{ $kkChipBg }}; color: {{ $kkChipFg }};">{{ $kkChipText }}</span>
                                    </div>
                                    @if($banner->title)
                                        <p style="font-size: 13px; color: #303030; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $banner->title }}</p>
                                    @endif
                                    @if($banner->subtitle)
                                        <p style="font-size: 12px; color: #616161; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $banner->subtitle }}</p>
                                    @endif
                                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; row-gap: 0.125rem; margin-top: 0.25rem; font-size: 12px; color: #616161;">
                                        @if($banner->button_text)
                                            <span>Button: {{ $banner->button_text }}</span>
                                        @endif
                                        @if($banner->link)
                                            <span style="min-width: 0; overflow-wrap: anywhere;">Link: {{ $banner->link }}</span>
                                        @endif
                                        <span>Overlay: {{ \App\Models\Banner::OVERLAY_STYLES[$banner->overlay_style] ?? 'Default' }}</span>
                                        {{-- Which breakpoint a banner has artwork for is otherwise
                                             invisible until someone opens Edit, and the thumbnail
                                             only ever shows the desktop file. --}}
                                        <span style="{{ $banner->has_mobile_media ? '' : 'color: #8a8a8a;' }}">
                                            Mobile: {{ $banner->has_mobile_media
                                                ? ($banner->has_mobile_video ? 'own video' : 'own image')
                                                : 'uses desktop' }}
                                        </span>
                                        @php
                                            // The chip says the state in one word; this says when.
                                            // A row reading "Scheduled" and nothing else gives no
                                            // way to find out what it is waiting for short of
                                            // opening the edit form.
                                            $kkWhen = null;
                                            if ($banner->state === 'scheduled' || $banner->state === 'expired') {
                                                $kkWhen = $banner->state_label;
                                            } elseif ($banner->starts_at?->isFuture()) {
                                                $kkWhen = 'Starts '.$banner->starts_at->format('j M Y, H:i');
                                            } elseif ($banner->ends_at) {
                                                $kkWhen = 'Ends '.$banner->ends_at->format('j M Y, H:i');
                                            }
                                        @endphp
                                        @if($kkWhen)
                                            <span style="color: #8a5300;">{{ $kkWhen }}</span>
                                        @endif
                                    </div>
                                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-top: 0.75rem;">
                                        <button @click="editing = true" type="button" class="btn btn-primary" style="font-size: 12px; padding: 0.25rem 0.5rem;">Edit</button>
                                        {{-- The one way to see a scheduled or expired banner as a
                                             shopper would: the home page will not show it, and the
                                             thumbnail here is the desktop still, cropped and small. --}}
                                        <a href="{{ route('admin.banners.preview', $banner) }}" target="_blank" rel="noopener" class="btn btn-secondary" style="font-size: 12px; padding: 0.25rem 0.5rem; text-decoration: none;">Preview</a>
                                        <form action="{{ route('admin.homepage.hero-banners.toggle', $banner) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 0.25rem 0.5rem;">
                                                {{ $banner->is_active ? 'Hide' : 'Show' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.homepage.hero-banners.destroy', $banner) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this banner?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn" style="font-size: 12px; padding: 0.25rem 0.5rem; background: none; border: 1px solid #d72c0d; color: #d72c0d; border-radius: 0.375rem; cursor: pointer;">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Mode -->
                        <div x-show="editing" x-cloak>
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                                <span style="font-size: 13px; font-weight: 600; color: #303030;">Edit Banner</span>
                                <button @click="editing = false" type="button" class="kk-nudge" style="color: #616161; background: none; border: none; cursor: pointer; padding: 0.25rem;">
                                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <form action="{{ route('admin.homepage.hero-banners.update', $banner) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div>
                                        <label for="hero-{{ $banner->id }}-name" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Name</label>
                                        <input type="text" name="name" id="hero-{{ $banner->id }}-name" value="{{ $banner->name }}" maxlength="255" class="form-input">
                                    </div>
                                    <div>
                                        <label for="hero-{{ $banner->id }}-link" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Link URL</label>
                                        <input type="text" name="link" id="hero-{{ $banner->id }}-link" value="{{ $banner->link }}" maxlength="255"
                                               pattern="(https?://|mailto:|tel:)\S+|/(?!/)\S*|#\S*"
                                               title="Enter a path such as /products, or a full https:// address."
                                               class="form-input" placeholder="/products">
                                    </div>
                                    <div>
                                        <label for="hero-{{ $banner->id }}-title" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Heading Text</label>
                                        <input type="text" name="title" id="hero-{{ $banner->id }}-title" value="{{ $banner->title }}" maxlength="255" class="form-input" placeholder="Banner heading">
                                    </div>
                                    <div>
                                        <label for="hero-{{ $banner->id }}-subtitle" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Subtitle</label>
                                        <input type="text" name="subtitle" id="hero-{{ $banner->id }}-subtitle" value="{{ $banner->subtitle }}" maxlength="500" class="form-input" placeholder="Banner subtitle">
                                    </div>
                                    <div>
                                        <label for="hero-{{ $banner->id }}-button-text" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Button Text</label>
                                        <input type="text" name="button_text" id="hero-{{ $banner->id }}-button-text" value="{{ $banner->button_text }}" maxlength="100" class="form-input" placeholder="Shop Now">
                                    </div>
                                    <div>
                                        <label for="hero-{{ $banner->id }}-overlay" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Overlay Style</label>
                                        <select name="overlay_style" id="hero-{{ $banner->id }}-overlay" class="form-select">
                                            @foreach(\App\Models\Banner::OVERLAY_STYLES as $key => $label)
                                                <option value="{{ $key }}" {{ $banner->overlay_style === $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div style="grid-column: 1 / -1;">
                                        <label for="hero-{{ $banner->id }}-alt" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Image Description</label>
                                        <input type="text" name="alt_text" id="hero-{{ $banner->id }}-alt" value="{{ $banner->alt_text }}" maxlength="255" class="form-input" placeholder="Model wearing the linen co-ord set">
                                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Read out in place of the artwork. Leave empty to use the heading.</p>
                                    </div>
                                    @php
                                        // A date the banner already holds stays acceptable, so
                                        // renaming a banner that went live last week does not
                                        // demand its start be dragged into the future first. The
                                        // floor below matches what V::scheduleStart() enforces.
                                        $kkStart = $banner->starts_at?->format('Y-m-d\TH:i');
                                        $kkEnd = $banner->ends_at?->format('Y-m-d\TH:i');
                                        $kkStartFloor = $kkStart && $kkStart < $kkNow ? $kkStart : $kkNow;
                                        $kkEndFloor = $kkEnd && $kkEnd < $kkNow ? $kkEnd : $kkNow;
                                    @endphp
                                    <div>
                                        <label for="hero-{{ $banner->id }}-starts-at" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Starts</label>
                                        <input type="datetime-local" name="starts_at" id="hero-{{ $banner->id }}-starts-at" value="{{ $kkStart }}"
                                               min="{{ $kkStartFloor }}" data-schedule-start data-schedule-original="{{ $kkStart }}"
                                               class="form-input" style="width: 100%;">
                                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Empty means live as soon as it is switched on.</p>
                                    </div>
                                    <div>
                                        <label for="hero-{{ $banner->id }}-ends-at" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Ends</label>
                                        <input type="datetime-local" name="ends_at" id="hero-{{ $banner->id }}-ends-at" value="{{ $kkEnd }}"
                                               min="{{ $kkEndFloor }}" data-schedule-end="hero-{{ $banner->id }}-starts-at" data-schedule-original="{{ $kkEnd }}"
                                               class="form-input" style="width: 100%;">
                                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Empty means it runs until it is hidden.</p>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                                    <x-admin.banner-media-section
                                        device="desktop"
                                        :width="$kkDeskW" :height="$kkDeskH" :ratio="$kkDeskRatio">
                                        <div>
                                            <label for="hero-{{ $banner->id }}-image" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Replace Image</label>
                                            <input type="file" name="image" id="hero-{{ $banner->id }}-image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input">
                                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                                JPG, PNG, WebP or GIF &middot; max {{ \App\Rules\ValidationRules::megabytes(\App\Support\BannerMedia::MAX_IMAGE_KB) }}MB.
                                                @if($banner->image_url) Leave empty to keep the current image. @endif
                                            </p>
                                        </div>
                                        <div>
                                            <label for="hero-{{ $banner->id }}-video" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Replace Video</label>
                                            <input type="file" name="video" id="hero-{{ $banner->id }}-video" accept="video/mp4,video/webm,video/quicktime" class="form-input">
                                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                                MP4, WebM or MOV &middot; max 64MB.
                                                @if($banner->video_url) Leave empty to keep the current video. @endif
                                            </p>
                                            @if($banner->video_url)
                                                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 12px; color: #616161; margin-top: 0.4rem; cursor: pointer;">
                                                    <input type="checkbox" name="remove_video" value="1" style="margin: 0;">
                                                    Remove the video and show the image instead
                                                </label>
                                            @endif
                                        </div>
                                    </x-admin.banner-media-section>

                                    <x-admin.banner-media-section
                                        device="mobile"
                                        :width="$kkMobW" :height="$kkMobH" :ratio="$kkMobRatio">
                                        <div>
                                            <label for="hero-{{ $banner->id }}-mobile-image" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Replace Mobile Image</label>
                                            <input type="file" name="mobile_image" id="hero-{{ $banner->id }}-mobile-image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-input">
                                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                                JPG, PNG, WebP or GIF &middot; max {{ \App\Rules\ValidationRules::megabytes(\App\Support\BannerMedia::MAX_IMAGE_KB) }}MB.
                                                @if($banner->mobile_image_url) Leave empty to keep the current one. @endif
                                            </p>
                                            @if($banner->mobile_image_url)
                                                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 12px; color: #616161; margin-top: 0.4rem; cursor: pointer;">
                                                    <input type="checkbox" name="remove_mobile_image" value="1" style="margin: 0;">
                                                    Remove it and use the desktop image on phones
                                                </label>
                                            @endif
                                        </div>
                                        <div>
                                            <label for="hero-{{ $banner->id }}-mobile-video" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Replace Mobile Video</label>
                                            <input type="file" name="mobile_video" id="hero-{{ $banner->id }}-mobile-video" accept="video/mp4,video/webm,video/quicktime" class="form-input">
                                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                                MP4, WebM or MOV &middot; max 64MB.
                                                @if($banner->mobile_video_url) Leave empty to keep the current one. @endif
                                            </p>
                                            @if($banner->mobile_video_url)
                                                <label style="display: inline-flex; align-items: center; gap: 0.4rem; font-size: 12px; color: #616161; margin-top: 0.4rem; cursor: pointer;">
                                                    <input type="checkbox" name="remove_mobile_video" value="1" style="margin: 0;">
                                                    Remove it and use the desktop banner on phones
                                                </label>
                                            @endif
                                        </div>
                                    </x-admin.banner-media-section>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Changes</button>
                                    <button @click="editing = false" type="button" class="btn btn-secondary" style="font-size: 13px;">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="card" style="padding: 3rem; text-align: center;">
                        <p style="font-size: 13px; color: #616161; margin: 0;">No hero banners yet. Add your first banner.</p>
                    </div>
                @endforelse
            </div>

            <!-- Status indicators -->
            <div x-show="saving" x-cloak style="margin-top: 0.75rem; font-size: 13px; color: #005bd3; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                <svg style="width: 1rem; height: 1rem; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24"><circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Saving order...
            </div>
            <div x-show="saved" x-cloak x-transition style="margin-top: 0.75rem; font-size: 13px; color: #1a7a2e; font-weight: 500;">Order saved.</div>
            {{-- A rejected reorder used to leave the cards in their new places on
                 screen with no message, so the page disagreed with the database
                 until the next refresh silently put everything back.

                 The sentence is no longer baked in. One fixed line stood in for an
                 expired session, a revoked permission, a payload the server refused
                 and a dropped connection alike, so it told the admin to "reload and
                 try again" when reloading was exactly what would not help - it is a
                 sign-in, or a wait, that the situation calls for. saveOrder() now
                 maps the status through kkApiError and puts the result here.

                 role="alert" because nothing moves focus when a reorder fails: the
                 message simply appears below a list the admin is already looking
                 away from. --}}
            <div x-show="failed" x-cloak x-transition role="alert" x-text="failedMessage"
                 style="margin-top: 0.75rem; font-size: 13px; color: #8e1f0b; font-weight: 500;"></div>
        </div>
    </div>

    @push('scripts')
    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        /* The reorder arrows and the close-edit cross are 18-28px icons; on a touch
           screen they are the only reorder path (HTML5 drag does not fire there),
           so they grow to a finger-sized box. A mouse keeps the compact look. */
        @media (pointer: coarse) {
            .kk-nudge { min-width: 2.25rem; min-height: 2.25rem; display: inline-flex; align-items: center; justify-content: center; }
        }
    </style>
    <script>
        function bannerSorter() {
            return {
                draggingId: null,
                dropTargetId: null,
                // dragend cannot tell a real drop from Escape or a drop on the page
                // background; only a drop event inside the list can.
                dropped: false,
                saving: false,
                saved: false,
                failed: false,
                // The sentence shown when a reorder is rejected, mapped from the
                // status by kkApiError rather than written here.
                failedMessage: '',
                // Every reorder takes a ticket, and only the newest one is allowed
                // to report. The arrows are disabled while a save is in flight, but
                // a drag has no control to disable and a click can still land in the
                // instant before Alpine paints the disabled state, so the guard is
                // repeated here where it is cheap and certain.
                saveTicket: 0,

                getItems() {
                    return Array.from(this.$refs.list.querySelectorAll('[data-id]'));
                },

                getCard(el) {
                    return el.closest('[data-id]');
                },

                indexOf(card) {
                    return this.getItems().indexOf(card);
                },

                onDragStart(e) {
                    const card = this.getCard(e.target);
                    this.draggingId = card.dataset.id;
                    this.dropped = false;
                    e.dataTransfer.effectAllowed = 'move';
                },

                onDrop() {
                    this.dropped = true;
                },

                onDragOver(e) {
                    const card = this.getCard(e.target);
                    if (!card || this.draggingId === null || card.dataset.id === this.draggingId) {
                        return;
                    }
                    this.dropTargetId = card.dataset.id;
                },

                onDragEnd() {
                    if (this.dropped && this.draggingId && this.dropTargetId && this.draggingId !== this.dropTargetId) {
                        const items = this.getItems();
                        const fromEl = items.find(el => el.dataset.id === this.draggingId);
                        const toEl = items.find(el => el.dataset.id === this.dropTargetId);
                        if (fromEl && toEl) {
                            this.moveDom(fromEl, toEl);
                        }
                    }
                    this.draggingId = null;
                    this.dropTargetId = null;
                    this.dropped = false;
                },

                moveUp(btnEl) {
                    const card = this.getCard(btnEl);
                    const index = this.indexOf(card);
                    if (index <= 0) return;
                    const items = this.getItems();
                    this.moveDom(card, items[index - 1]);
                },

                moveDown(btnEl) {
                    const card = this.getCard(btnEl);
                    const items = this.getItems();
                    const index = items.indexOf(card);
                    if (index >= items.length - 1) return;
                    this.moveDom(card, items[index + 1]);
                },

                moveDom(fromEl, toEl) {
                    // A save in flight owns the list. The POST already travelling
                    // describes the order as the cards stand right now, so shuffling
                    // them again before it answers would send a second, competing
                    // order - two requests deciding between them what the database
                    // ends up holding. Refusing the move leaves the DOM untouched,
                    // which is the honest state: what is on screen is still what was
                    // sent. A dropped card snaps back for the moment the save takes.
                    if (this.saving) return;

                    const list = this.$refs.list;
                    const fromIndex = this.indexOf(fromEl);
                    const toIndex = this.indexOf(toEl);

                    if (fromIndex < toIndex) {
                        list.insertBefore(fromEl, toEl.nextSibling);
                    } else {
                        list.insertBefore(fromEl, toEl);
                    }

                    this.updateBadges();
                    this.saveOrder();
                },

                // Counts the way the server did when it rendered the badges: only
                // banners a shopper can actually see hold a slot. Numbering every
                // card 1..n put a scheduled or hidden banner back into the running
                // order the moment anything was dragged, contradicting the page
                // that had just been loaded.
                updateBadges() {
                    let slot = 0;
                    this.getItems().forEach((el) => {
                        const badge = el.querySelector('.pos-badge');
                        if (!badge) return;
                        badge.textContent = el.dataset.live === '1' ? '#' + (++slot) : '--';
                    });
                },

                /**
                 * The end of one reorder, and the only place the status line is
                 * written. An empty message means it saved.
                 *
                 * The ticket check is the point of it: a reply that arrives after a
                 * newer reorder has already started describes an order that is no
                 * longer on screen, so letting it write here is how "The new order
                 * was not saved." used to appear over an order that had saved
                 * perfectly well - the older request simply answered last.
                 */
                settleSave(ticket, message) {
                    if (ticket !== this.saveTicket) return;

                    this.saving = false;
                    this.saved = message === '';
                    this.failed = message !== '';
                    this.failedMessage = message;

                    // Guarded for the same reason: by the time this fires another
                    // save may own the line, and clearing its "Order saved." two
                    // seconds after ours would hide a result the admin never saw.
                    if (this.saved) {
                        setTimeout(() => {
                            if (ticket === this.saveTicket) this.saved = false;
                        }, 2000);
                    }
                },

                async saveOrder() {
                    const order = this.getItems().map(el => parseInt(el.dataset.id));
                    const ticket = ++this.saveTicket;

                    this.saving = true;
                    this.saved = false;
                    this.failed = false;
                    this.failedMessage = '';

                    // Read before the request, and defensively: `.content` off a
                    // missing meta tag throws straight out of here, and `saving`
                    // would be left true forever - which now disables the arrows and
                    // freezes moveDom, so a missing token would lock reordering for
                    // the rest of the page's life instead of failing once.
                    const csrf = document.querySelector('meta[name="csrf-token"]');
                    if (!csrf) {
                        this.settleSave(ticket, 'This page has been open a while and your session expired. Please refresh and try again.');
                        return;
                    }

                    try {
                        const res = await fetch('{{ route("admin.homepage.hero-banners.reorder") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf.content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ order }),
                        });

                        // Tolerated, not assumed: a 419 redirected to the login page
                        // and a 500 behind the proxy both answer with HTML, and
                        // parsing that must not become the reason the failure is
                        // reported as a dead connection.
                        const data = await res.json().catch(() => null);

                        if (res.ok && data && data.success === true) {
                            this.settleSave(ticket, '');
                            return;
                        }

                        // A 200 that does not say success: the endpoint answered and
                        // stored nothing, and no status code describes that, so this
                        // is the one sentence still written by hand.
                        if (res.ok) {
                            this.settleSave(ticket, 'The new order was not saved. Reload the page and try again.');
                            return;
                        }

                        // Everything else goes through the one mapper. It is what
                        // separates "your session expired, sign in again" from "you
                        // do not have permission" from "try again in a moment", all
                        // of which used to arrive as the same "reload and try again"
                        // - and it is what keeps a 5xx exception's own text, class
                        // names and file paths included, off this page.
                        const failure = window.kkApiError(res, data);

                        // The 422 here is never about a field the admin can see: the
                        // payload is a list of banner ids this page built itself, so
                        // "check the highlighted fields" would point at nothing. The
                        // server's first complaint says far more.
                        this.settleSave(ticket, Object.values(failure.fields)[0] || failure.message);
                    } catch (e) {
                        // No response at all - the request never reached the server.
                        this.settleSave(ticket, window.kkApiError(e).message);
                    }
                },
            };
        }
    </script>
    @endpush
</x-layouts.admin>
