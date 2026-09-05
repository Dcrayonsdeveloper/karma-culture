@props([
    'device' => 'desktop',
    'width' => 0,
    'height' => 0,
    'ratio' => '',
    // The caps the server enforces, read from the server's own constants so
    // the sentence under the field and the rule that refuses the file cannot
    // say different numbers. Stated here as well as under each input because
    // an oversized file is otherwise refused only once it has finished
    // uploading, and on a home connection that is several minutes spent to be
    // told a limit that was readable up front.
    'imageMaxMb' => null,
    'videoMaxMb' => null,
])

@php
    $kkImageMaxKb = \App\Support\BannerMedia::MAX_IMAGE_KB;
    $kkVideoMaxKb = \App\Support\BannerMedia::MAX_VIDEO_KB;
    $kkImageMaxMb = $imageMaxMb ?? \App\Rules\ValidationRules::megabytes($kkImageMaxKb);
    $kkVideoMaxMb = $videoMaxMb ?? \App\Rules\ValidationRules::megabytes($kkVideoMaxKb);
@endphp

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

    {{-- The same formats the file inputs below filter the OS picker on, and the
         same limits the server enforces. Said once here as well, because the
         per-input notes are only read after a file has been chosen and the size
         cap is the one rule that costs a whole upload to discover. --}}
    <p style="font-size: 12px; color: #616161; margin: -0.55rem 0 0.85rem 0; line-height: 1.5;">
        Images JPG, PNG, WebP or GIF, up to {{ $kkImageMaxMb }} MB &middot;
        video MP4, WebM or MOV, up to {{ $kkVideoMaxMb }} MB.
    </p>

    <div style="display: flex; flex-direction: column; gap: 0.85rem;"
         data-kk-upload-limits
         data-kk-image-max="{{ $kkImageMaxKb }}"
         data-kk-video-max="{{ $kkVideoMaxKb }}">
        {{ $slot }}
    </div>
</div>

@once
    {{-- Say no before the upload, not after it.

         The server refuses an oversized file, but only once every byte of it
         has arrived - so an admin on a home connection watches a 20 MB photo
         upload for a minute and is then told the number in kilobytes, with the
         rest of the form reset around them. This checks the size the moment the
         file is chosen, names the limit in the unit their file manager uses,
         and clears the field so the form cannot be submitted with it.

         Delegated from the document, so it covers the add form and every row's
         edit form including any added after this script ran. --}}
    <script>
        (function () {
            function mb(kb) {
                var v = kb / 1024;
                return (Math.round(v * 10) / 10).toString();
            }

            document.addEventListener('change', function (event) {
                var input = event.target;

                if (! input.matches || ! input.matches('input[type="file"]')) return;

                var scope = input.closest('[data-kk-upload-limits]');
                if (! scope) return;

                var note = scope.parentElement.querySelector('[data-kk-upload-error]');
                if (note) { note.remove(); }

                var file = input.files && input.files[0];
                if (! file) return;

                var isVideo = (input.accept || '').indexOf('video/') !== -1;
                var maxKb = Number(scope.getAttribute(isVideo ? 'data-kk-video-max' : 'data-kk-image-max'));
                if (! maxKb || file.size <= maxKb * 1024) return;

                input.value = '';

                var p = document.createElement('p');
                p.setAttribute('data-kk-upload-error', '');
                p.setAttribute('role', 'alert');
                p.style.cssText = 'margin: 0.5rem 0 0; font-size: 12px; color: #8e1f0b;';
                p.textContent = 'That file is ' + mb(file.size / 1024) + ' MB. The limit is '
                    + mb(maxKb) + ' MB - export it smaller and choose it again.';
                scope.parentElement.appendChild(p);
            });
        }());
    </script>
@endonce
