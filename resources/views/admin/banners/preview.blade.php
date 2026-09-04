<x-layouts.admin>
    <x-slot name="title">Preview {{ $banner->name }}</x-slot>

    <x-slot name="styles">
        <style>
            /* The desktop strip is nearly four times as wide as it is tall and the
               phone box is 3:2, so equal columns would leave one of them a sliver.
               The phone is pinned to a phone-ish width and the desktop takes what
               is left, which lands the two frames at roughly the same height. */
            .kk-pv-grid { display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr); align-items: start; }
            @media (min-width: 1100px) {
                .kk-pv-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 340px); }
            }

            /* The frame is the slide. Its height comes from `aspect-ratio` and never
               from a pixel figure, so the crop an admin sees here is the crop the
               storefront ships at whatever width this column happens to be.

               `container-type` is what makes that true of the caption as well: the
               storefront sizes the heading in vw, against the screen, and inside a
               shrunken preview box vw would report the admin's monitor and print the
               text several times too large over artwork it would clear in reality.
               Container units measure this box instead. */
            .kk-pv-frame {
                position: relative; width: 100%; overflow: hidden;
                background: var(--kk-brown-darker, #1f1109);
                container-type: inline-size;
            }
            .kk-pv-frame img, .kk-pv-frame video {
                position: absolute; inset: 0; display: block;
                width: 100%; height: 100%;
                object-fit: cover; object-position: center;
            }
            .kk-pv-frame.is-broken img, .kk-pv-frame.is-broken video { display: none; }

            /* Words rather than an empty box, for the two ways a frame can have
               nothing in it: no file chosen for this size, and a file whose row
               survived but whose upload did not. The note is last inside the frame
               and opaque, so a broken file covers the caption drawn over it rather
               than leaving half a slide showing behind the message. */
            .kk-pv-note {
                position: absolute; inset: 0; display: flex;
                flex-direction: column; align-items: center; justify-content: center;
                gap: 0.5rem; padding: 1rem; text-align: center;
                background: var(--kk-brown-darker, #1f1109);
                color: rgba(255, 255, 255, 0.86); font-size: 13px; line-height: 1.45;
            }
            .kk-pv-note--broken { display: none; }
            .kk-pv-frame.is-broken .kk-pv-note--broken { display: flex; }
            .kk-pv-note svg { width: 26px; height: 26px; opacity: 0.6; }

            /* The caption, copied from the home page's own rules so the collision
               this screen exists to reveal is the real one. Every pixel figure the
               storefront hard-codes is restated as a multiple of --kk-px, which each
               frame sets to one device pixel expressed as a share of its own width:
               a 22px floor on a 390px phone has to stay 5.6% of the box, not 22px of
               a 300px-wide preview, or a heading that wraps live looks safe here. */
            .kk-pv-overlay { position: absolute; inset: 0; pointer-events: none; }
            .kk-hero-caption {
                position: absolute; inset: 0; display: flex; flex-direction: column;
                justify-content: center; gap: calc(12 * var(--kk-px));
                padding: 0 8cqw; pointer-events: none;
            }
            .kk-hero-caption--right-dark { align-items: flex-end; text-align: right; }
            .kk-hero-caption--center-vignette,
            .kk-hero-caption--full-dark { align-items: center; text-align: center; }
            /* The storefront's own max-width: 640px caption block, which every phone
               is inside and no desktop is. */
            .kk-hero-caption--narrow { gap: calc(8 * var(--kk-px)); padding: 0 6cqw; }
            .kk-pv-title {
                font-family: var(--kk-display, Georgia, serif); font-weight: 600; color: #fff; margin: 0;
                font-size: clamp(calc(22 * var(--kk-px)), 4.2cqw, calc(52 * var(--kk-px)));
                line-height: 1.08; text-shadow: 0 2px 18px rgba(0, 0, 0, 0.35);
            }
            .kk-pv-sub {
                color: rgba(255, 255, 255, 0.92); margin: 0; max-width: 46ch;
                font-size: clamp(calc(12 * var(--kk-px)), 1.5cqw, calc(17 * var(--kk-px)));
                line-height: 1.5; text-shadow: 0 1px 12px rgba(0, 0, 0, 0.35);
            }
            /* app.css scopes .kk-btn-cream to `body:not(.layout-admin)`, so the
               storefront's button styling is deliberately absent in here and the
               preview has to draw its own or show an unstyled word. */
            .kk-hero-btn {
                margin-top: calc(4 * var(--kk-px)); align-self: flex-start;
                display: inline-flex; align-items: center; justify-content: center;
                padding: calc(10 * var(--kk-px)) calc(22 * var(--kk-px));
                background: var(--kk-cream-lighter, #fbf5e8); color: var(--kk-brown, #4a2d1a);
                border: 1px solid var(--kk-brown, #4a2d1a); border-radius: 999px;
                font-size: calc(12 * var(--kk-px)); font-weight: 600;
                letter-spacing: 0.18em; text-transform: uppercase; white-space: nowrap;
            }
            .kk-hero-caption--right-dark .kk-hero-btn { align-self: flex-end; }
            .kk-hero-caption--center-vignette .kk-hero-btn,
            .kk-hero-caption--full-dark .kk-hero-btn { align-self: center; }

            .kk-pv-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.875rem 1.25rem; }
            .kk-pv-facts dt { font-size: 12px; color: #616161; margin-bottom: 0.125rem; }
            .kk-pv-facts dd { font-size: 13px; color: #303030; margin: 0; word-break: break-word; }
        </style>
    </x-slot>

    @php
        // The two boxes the storefront draws the hero in, read from the model's
        // constants rather than retyped, so a change to either shape moves this
        // screen with the home page instead of leaving it quietly wrong.
        [$kkDeskW, $kkDeskH] = \App\Models\Banner::HERO_DESKTOP_SIZE;
        [$kkPhoneW, $kkPhoneH] = \App\Models\Banner::HERO_MOBILE_SIZE;

        // The width of the SCREEN each frame stands in for, which is not the size of
        // the artwork it holds. The hero is full-bleed, so a desktop slide is exactly
        // as wide as the window and 1426 doubles as both figures. A phone is not:
        // 1080 is how many pixels the mobile file carries, while the box it is
        // poured into is around 390 CSS pixels wide, and it is that CSS width the
        // caption is sized against.
        $kkDeskViewport = $kkDeskW;
        $kkPhoneViewport = 390;

        // One device pixel as a percentage of the frame's own width - the knob that
        // rescales every hard-coded caption size in the stylesheet above.
        $kkPx = fn (int $viewport) => round(100 / $viewport, 5).'cqw';

        // The caption is only drawn when the admin filled something in, exactly as
        // on the home page: a plain image banner must preview as a plain image.
        $kkHasCaption = filled($banner->title) || filled($banner->subtitle)
            || (filled($banner->button_text) && filled($banner->link));
        $kkOverlayStyle = $banner->overlay_style ?: 'left-dark';

        // Empty is a real answer and means "decorative", so it is passed through
        // rather than replaced - the same bargain the storefront strikes.
        $kkAlt = $banner->alt;

        // Both devices resolving to one file is the ordinary case and worth saying
        // out loud: it is the difference between "the phone has its own artwork" and
        // "the phone is being handed the desktop strip and cropping three fifths of
        // it away", which look identical until you read the two filenames.
        $kkSharedFile = $desktopFrame && $mobileFrame && $desktopFrame['src'] === $mobileFrame['src'];

        $kkDevices = [
            [
                'label' => 'Desktop',
                'frame' => $desktopFrame,
                'w' => $kkDeskW,
                'h' => $kkDeskH,
                'viewport' => $kkDeskViewport,
                'narrow' => false,
                'note' => null,
            ],
            [
                'label' => 'Phone',
                'frame' => $mobileFrame,
                'w' => $kkPhoneW,
                'h' => $kkPhoneH,
                'viewport' => $kkPhoneViewport,
                'narrow' => true,
                'note' => $kkSharedFile
                    ? 'No phone artwork of its own - this is the desktop file, cropped to '.$kkPhoneW.' x '.$kkPhoneH.'.'
                    : null,
            ],
        ];

        $kkStateChip = match ($banner->state) {
            'live' => ['bg' => '#cdfee1', 'fg' => '#1a7a2e'],
            'scheduled' => ['bg' => '#e0f0ff', 'fg' => '#005bd3'],
            'expired' => ['bg' => '#ffe9e5', 'fg' => '#d72c0d'],
            default => ['bg' => '#fff3cd', 'fg' => '#8a6d00'],
        };

        // An admin who opens a preview of something the site is not showing needs the
        // reason on the page, or the preview itself reads as proof that it is live.
        $kkWhyNotLive = match ($banner->state) {
            'scheduled' => 'Not on the site yet. It will appear on its own when the window opens.',
            'expired' => 'No longer on the site. Clear or extend the end date to bring it back.',
            'hidden' => 'Not on the site. The banner is switched off.',
            default => null,
        };
    @endphp

    <!-- Top bar -->
    <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.banners.index') }}" class="btn-icon" style="flex-shrink: 0; color: #616161; text-decoration: none;" aria-label="Back to banners">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $banner->name }}</h1>
        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: {{ $kkStateChip['bg'] }}; color: {{ $kkStateChip['fg'] }};">{{ $banner->state_label }}</span>
        <span style="flex: 1;"></span>
        <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Banners</a>
    </div>

    @if($kkWhyNotLive)
        <div style="display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.75rem 0.875rem; margin-bottom: 1rem; border-radius: 0.5rem; background: #fff8e6; border: 1px solid #ffd79d; font-size: 13px; color: #5c4200;">
            <svg style="width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <span>{{ $kkWhyNotLive }} The frames below show how it would appear.</span>
        </div>
    @endif

    @unless($banner->has_media)
        <div style="display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.75rem 0.875rem; margin-bottom: 1rem; border-radius: 0.5rem; background: #fdecea; border: 1px solid #f3b7b0; font-size: 13px; color: #7a1c0c;">
            <svg style="width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <span>This banner carries no artwork at all, so the storefront draws no slide for it whatever its state says.</span>
        </div>
    @endunless

    <div class="kk-pv-grid" style="margin-bottom: 1rem;">
        @foreach($kkDevices as $kkDevice)
            @php $kkFrame = $kkDevice['frame']; @endphp
            <div class="card" style="background: #fff; border-radius: 0.75rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
                <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 0.5rem; padding: 0.625rem 0.875rem; border-bottom: 1px solid #e3e3e3;">
                    <span style="font-size: 13px; font-weight: 600; color: #303030;">{{ $kkDevice['label'] }}</span>
                    <span style="font-size: 12px; color: #616161;">{{ $kkDevice['w'] }} &times; {{ $kkDevice['h'] }} px</span>
                </div>

                <div class="kk-pv-frame"
                     style="aspect-ratio: {{ $kkDevice['w'] }} / {{ $kkDevice['h'] }}; --kk-px: {{ $kkPx($kkDevice['viewport']) }};">
                    @if($kkFrame)
                        @if($kkFrame['kind'] === 'video')
                            {{-- Muted, looping and inline, the way the storefront plays it, so
                                 the frame an admin judges the crop on is one the clip actually
                                 reaches rather than its poster still. --}}
                            <video src="{{ $kkFrame['src'] }}"
                                   @if($kkFrame['poster']) poster="{{ $kkFrame['poster'] }}" @endif
                                   autoplay muted loop playsinline preload="metadata"
                                   aria-label="{{ $kkDevice['label'] }} preview of {{ $banner->name }}"
                                   onerror="this.closest('.kk-pv-frame').classList.add('is-broken')"></video>
                        @elseif($kkFrame['webp'])
                            <picture>
                                <source srcset="{{ $kkFrame['webp'] }}" type="image/webp">
                                <img src="{{ $kkFrame['src'] }}" alt="{{ $kkAlt }}" @if($kkAlt === '') aria-hidden="true" @endif
                                     onerror="this.closest('.kk-pv-frame').classList.add('is-broken')">
                            </picture>
                        @else
                            <img src="{{ $kkFrame['src'] }}" alt="{{ $kkAlt }}" @if($kkAlt === '') aria-hidden="true" @endif
                                 onerror="this.closest('.kk-pv-frame').classList.add('is-broken')">
                        @endif

                        @if($kkHasCaption)
                            <div class="kk-pv-overlay {{ $banner->overlay_css }}"></div>
                            <div class="kk-hero-caption kk-hero-caption--{{ $kkOverlayStyle }}{{ $kkDevice['narrow'] ? ' kk-hero-caption--narrow' : '' }}">
                                @if($banner->title)
                                    <h2 class="kk-pv-title">{{ $banner->title }}</h2>
                                @endif
                                {{-- The storefront hides the subtitle below 640px, so drawing it
                                     in the phone frame would promise text no phone is ever sent. --}}
                                @if($banner->subtitle && ! $kkDevice['narrow'])
                                    <p class="kk-pv-sub">{{ $banner->subtitle }}</p>
                                @endif
                                @if($banner->button_text && $banner->link)
                                    <span class="kk-hero-btn">{{ $banner->button_text }}</span>
                                @endif
                            </div>
                        @endif

                        {{-- Nothing in the admin layout notices a 404'd picture the way the
                             storefront's delegated handler does, so each element has to say so
                             itself. Without this a deleted upload previews as a black box,
                             which reads as a dark banner rather than as a mistake. --}}
                        <div class="kk-pv-note kk-pv-note--broken">
                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/><path stroke-linecap="round" d="M4 4l16 16"/></svg>
                            <span><strong>File missing.</strong> This banner points at a file that is no longer on the disk - upload it again before publishing.</span>
                        </div>
                    @else
                        <div class="kk-pv-note">
                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>No artwork for this size - the banner will not be shown.</span>
                        </div>
                    @endif
                </div>

                <div style="padding: 0.625rem 0.875rem; font-size: 12px; color: #616161; line-height: 1.5;">
                    @if($kkFrame)
                        <div style="color: #303030;">
                            {{ $kkFrame['kind'] === 'video' ? 'Video' : 'Image' }}:
                            <span style="word-break: break-all;">{{ basename(parse_url($kkFrame['src'], PHP_URL_PATH) ?: $kkFrame['src']) }}</span>
                        </div>
                        @if($kkFrame['webp'])
                            <div>A WebP copy exists and is served to the browsers that accept it.</div>
                        @endif
                        @if($kkDevice['note'])
                            <div>{{ $kkDevice['note'] }}</div>
                        @endif
                        @if($kkDevice['narrow'] && $banner->subtitle)
                            <div>The subtitle is hidden on phones, so it is not drawn above.</div>
                        @endif
                    @else
                        <div>Nothing resolves for this size, not even a fallback from the other one.</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="card" style="background: #fff; border-radius: 0.75rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); padding: 1rem 1.25rem;">
        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 0.875rem;">Schedule and placement</h2>
        <dl class="kk-pv-facts">
            <div>
                <dt>Shows from</dt>
                <dd>{{ $banner->starts_at ? $banner->starts_at->format('j M Y, H:i') : 'As soon as it is switched on' }}</dd>
            </div>
            <div>
                <dt>Shows until</dt>
                <dd>{{ $banner->ends_at ? $banner->ends_at->format('j M Y, H:i') : 'No end date' }}</dd>
            </div>
            <div>
                <dt>Switch</dt>
                <dd>{{ $banner->is_active ? 'On' : 'Off' }}</dd>
            </div>
            <div>
                {{-- Two columns whose names invite exactly the wrong reading of each
                     other, so both are spelled out rather than printed as headings. --}}
                <dt>Placement</dt>
                <dd>{{ $banner->position }}</dd>
            </div>
            <div>
                <dt>Sort order</dt>
                <dd>{{ $banner->priority }} <span style="color: #616161;">(lower shows first)</span></dd>
            </div>
            <div>
                <dt>Link</dt>
                <dd>{{ $banner->link ?: 'Not clickable' }}</dd>
            </div>
            <div>
                <dt>Overlay style</dt>
                <dd>{{ \App\Models\Banner::OVERLAY_STYLES[$kkOverlayStyle] ?? $kkOverlayStyle }}</dd>
            </div>
            <div>
                <dt>Alt text</dt>
                <dd>{{ $kkAlt !== '' ? $kkAlt : 'Empty - the artwork is marked decorative' }}</dd>
            </div>
        </dl>

        @if($banner->position !== 'hero')
            <p style="margin-top: 0.875rem; font-size: 12px; color: #616161; line-height: 1.5;">
                The frames above are the hero's two boxes. This banner sits in the
                {{ $banner->position }} slot, which the storefront draws at its own size, so
                treat the crop shown here as a guide rather than a promise.
            </p>
        @endif
    </div>
</x-layouts.admin>
