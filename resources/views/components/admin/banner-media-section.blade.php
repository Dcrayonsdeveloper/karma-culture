@props([
    'device' => 'desktop',
    'width' => 0,
    'height' => 0,
    'ratio' => '',
])

{{-- One breakpoint's worth of hero uploads, headed by the size that breakpoint
     actually draws at. The two devices are separated because the answer to
     "what size should this be?" is genuinely different for each: the desktop
     hero is a wide strip, the phone one a squarer 3:2 box, and a single number
     covering both was wrong for one of them whichever number it was.

     The size is now advice worth following rather than a suggestion: the slide
     is a fixed box at each breakpoint, so artwork of another shape is filled in
     and centre-cropped, not shrunk to fit. --}}

@php
    $isMobile = $device === 'mobile';
    $heading = $isMobile ? 'Mobile - phones' : 'Desktop - website';
@endphp

<div style="border: 1px solid #e3e3e3; border-radius: 8px; padding: 0.85rem;">
    <div style="display: flex; align-items: center; gap: 0.45rem; margin-bottom: 0.15rem;">
        @if($isMobile)
            <svg style="width: 1rem; height: 1rem; color: #616161; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                <rect x="7" y="2" width="10" height="20" rx="2"/><path stroke-linecap="round" d="M11 18.5h2"/>
            </svg>
        @else
            <svg style="width: 1rem; height: 1rem; color: #616161; flex-shrink: 0;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                <rect x="2.5" y="4" width="19" height="12.5" rx="2"/><path stroke-linecap="round" d="M8 20.5h8M12 16.5v4"/>
            </svg>
        @endif
        <span style="font-size: 12px; font-weight: 700; color: #303030; text-transform: uppercase; letter-spacing: 0.04em;">{{ $heading }}</span>
    </div>

    <p style="font-size: 12px; color: #616161; margin: 0 0 0.85rem 0; line-height: 1.5;">
        Recommended size <strong style="color: #303030;">{{ $width }} &times; {{ $height }}px</strong>
        <span style="color: #8a8a8a;">({{ $ratio }})</span> &mdash;
        {{ $isMobile
            ? 'the shape a phone gives the slide.'
            : 'the shape the hero plays at, taken from the hero video.' }}
        Anything at the same proportions works. A different shape still fills the
        slide edge to edge, but it is centred and cropped to get there, so keep
        anything that must be read - a headline, a face - away from the edges.
    </p>

    <div style="display: flex; flex-direction: column; gap: 0.85rem;">
        {{ $slot }}
    </div>
</div>
