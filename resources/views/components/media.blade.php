@props([
    'src' => null,
    'alt' => '',
    'video' => false,
    'poster' => null,
    'ratio' => null,
    'dark' => false,
    'zoom' => false,
    'cover' => false,
    'fallback' => null,
    'loading' => 'lazy',
    'autoplay' => true,
])

{{-- Card media well. Renders the subject *contained* so the whole image or
     video is visible, over a blurred copy of itself that fills the frame, so
     an off-ratio file neither gets cropped nor leaves empty bars. Media that
     fails to load is caught by the delegated handler in the layout, which
     marks the frame .is-broken rather than leaving a blank rectangle.

     `cover` opts a frame out of that and crops instead. It is for the places
     where the FRAME's shape is the point rather than the file's - a listing
     grid reads as a row of matching portraits, and contain leaves every tile a
     different size of blurred margin. Cropping there is not a loss of
     information: the whole photo is one click away, because the product page's
     zoom is contain.

     It is opt-in, not the default, and some product images deliberately decline
     it - the assistant's tiles show size charts and flat-lays, which a crop
     would cut the product out of. See the note beside .kk-media--cover in
     resources/css/app.css.

     A covered frame renders no backdrop at all. There is nothing left for it to
     fill, and it is a second full-size decode of the same file - twenty of them
     on a listing page. --}}

@php
    $frameClass = 'kk-media'
        . ($dark ? ' kk-media--dark' : '')
        . ($zoom ? ' kk-media--zoom' : '')
        . ($cover ? ' kk-media--cover' : '');
    $style = $ratio ? 'aspect-ratio: ' . $ratio . ';' : null;
@endphp

<div {{ $attributes->merge(['class' => $frameClass, 'style' => $style]) }}>
    @if($src)
        {{-- Decorative backdrop; the subject below carries the alt text.

             Never a second playing <video>: a rail of clips would then hold two
             decoders per tile, and browsers cap concurrent hardware decoders -
             past the cap clips silently stop painting, which is the blank tile
             this component exists to prevent. A video backdrop is drawn from
             the poster still, or skipped entirely when there is none (the frame
             background then carries the margin). --}}
        @unless($cover)
            @if($video)
                @if($poster)
                    <img class="kk-media__fill" src="{{ $poster }}" alt="" aria-hidden="true"
                         loading="{{ $loading }}" decoding="async">
                @endif
            @else
                <img class="kk-media__fill" src="{{ $src }}" alt="" aria-hidden="true"
                     loading="{{ $loading }}" decoding="async">
            @endif
        @endunless

        @if($video)
            <video muted playsinline loop @if($autoplay) autoplay @endif
                   preload="metadata"
                   @if($poster) poster="{{ $poster }}" @endif
                   @if($fallback) data-fallback="{{ $fallback }}" @endif
                   src="{{ $src }}"></video>
        @else
            <img src="{{ $src }}" alt="{{ $alt }}"
                 loading="{{ $loading }}" decoding="async"
                 @if($fallback) data-fallback="{{ $fallback }}" @endif>
        @endif
    @endif

    <span class="kk-media__fallback" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="4" width="18" height="16" rx="2"/>
            <circle cx="8.5" cy="9.5" r="1.5"/>
            <path d="M21 15l-5-5L5 20"/>
        </svg>
    </span>

    {{ $slot }}
</div>
