<x-layouts.app>
    <x-slot name="title">{{ $page->seo_data['meta_title'] ?? $page->title }} - {{ config('app.name') }}</x-slot>

    @push('meta')
        @if(!empty($page->seo_data['meta_description']))
            <meta name="description" content="{{ $page->seo_data['meta_description'] }}">
        @endif
        <link rel="canonical" href="{{ url()->current() }}">
    @endpush

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => $page->title, 'url' => null]]" />
        </div>
    </div>

    @php
        $iconBg = match($page->slug) {
            'privacy-policy'   => 'bg-[#6F9CA2]/5',
            'terms-of-service' => 'bg-neutral-100',
            'cookie-policy'    => 'bg-amber-50',
            'gdpr'             => 'bg-primary-50',
            'returns-policy'   => 'bg-warning-50',
            'size-guides'      => 'bg-[#6F9CA2]/10',
            default            => 'bg-neutral-100',
        };
        $iconColor = match($page->slug) {
            'privacy-policy'   => 'text-[#6F9CA2]',
            'terms-of-service' => 'text-neutral-600',
            'cookie-policy'    => 'text-amber-600',
            'gdpr'             => 'text-primary-600',
            'returns-policy'   => 'text-warning-600',
            'size-guides'      => 'text-[#6F9CA2]',
            default            => 'text-neutral-600',
        };

        // The clause after the date. "Please read this document carefully" is
        // the right thing to say over a privacy policy and the wrong thing to
        // say over a size chart, so the two pages that are not legal documents
        // drop it and open with their own first line instead - which lives in
        // the content, where an admin can change it.
        $subtitle = match($page->slug) {
            'returns-policy', 'size-guides' => null,
            default => 'Please read this document carefully.',
        };

        // What to offer at the foot of the page. A returns page sending readers
        // to the GDPR statement helps nobody; it belongs beside the size guide
        // and the shipping page, which is what a shopper reading it is
        // actually deciding between.
        $relatedLinks = match($page->slug) {
            'returns-policy', 'size-guides' => [
                'returns'     => 'Returns Policy',
                'size-guide'  => 'Size Guide',
                'shipping'    => 'Shipping',
            ],
            default => [
                'privacy'       => 'Privacy Policy',
                'terms'         => 'Terms of Service',
                'cookie-policy' => 'Cookie Policy',
                'gdpr'          => 'GDPR',
            ],
        };

        // Which of those links is this page itself. Keyed by route name, since
        // that is what the list above holds.
        $selfRoute = match($page->slug) {
            'privacy-policy'   => 'privacy',
            'terms-of-service' => 'terms',
            'cookie-policy'    => 'cookie-policy',
            'gdpr'             => 'gdpr',
            'returns-policy'   => 'returns',
            'size-guides'      => 'size-guide',
            default            => null,
        };

        // Carried over from the returns page, which had a "call us" button.
        // It reads the store's configured line rather than a number typed into
        // the copy, which is the drift that button was fixed to avoid - and a
        // number in an editable page body would drift again the moment the
        // store changed its phone and forgot this page.
        $supportPhone = trim((string) \App\Models\Setting::get('contact_phone', ''));
        $supportTel   = $supportPhone !== '' ? preg_replace('/[^0-9+]/', '', $supportPhone) : '';

        // A small diagram of the measurement each size-chart column asks for,
        // shown beside its heading. They are drawn here rather than stored in
        // the page body on purpose: the body is edited in CKEditor, which has
        // no image plugin and would drop an <img> on the first save without
        // saying so. This is decoration, like the page icon above, so it lives
        // in the template where an admin cannot lose it.
        //
        // All three share one body outline and differ only in the measuring
        // line - full height down the side, a band across the chest, a band
        // at the waist - so the three columns are told apart at a glance.
        // The body is deliberately a head and a plain rounded torso, with no
        // arms or legs. A first attempt drew the full figure and the chest and
        // waist icons came out indistinguishable at 18px - the limbs read as
        // the measuring band, and the two bands were only two pixels apart.
        // Stripped back, the band is the only horizontal line in the glyph and
        // sits in the top or the bottom third, which is the whole difference
        // between the two columns.
        $measureIcon = function (string $line): string {
            // Plain w-5/h-5 rather than an arbitrary size: these class names
            // live inside a PHP string, and Tailwind's scanner does not pick
            // an arbitrary value like w-[18px] out of one - it compiled to
            // nothing and the icons came out unsized.
            return '<svg class="w-5 h-5 shrink-0 text-neutral-500" viewBox="0 0 24 24" fill="none" '
                .'stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" '
                .'aria-hidden="true">'
                .'<circle cx="12" cy="4" r="2.2"/>'
                .'<rect x="8.5" y="8.5" width="7" height="11" rx="2.5"/>'
                .$line
                .'</svg>';
        };

        $measureIcons = [
            'height' => $measureIcon('<path d="M3.5 2v20M1.9 3.6 3.5 2l1.6 1.6M1.9 20.4 3.5 22l1.6-1.6"/>'),
            'chest'  => $measureIcon('<path d="M6 11.5h12M7.4 10.1 6 11.5l1.4 1.4M16.6 10.1 18 11.5l-1.4 1.4"/>'),
            'waist'  => $measureIcon('<path d="M6 17h12M7.4 15.6 6 17l1.4 1.4M16.6 15.6 18 17l-1.4 1.4"/>'),
        ];

        // Put the diagram in front of the heading it explains. Run over the
        // sanitised HTML, so the SVG is ours rather than something the page
        // body could have smuggled through. A renamed column simply misses
        // out; nothing else in the table is touched.
        $withMeasureIcons = function (string $html) use ($measureIcons): string {
            return preg_replace_callback(
                '#<th([^>]*)>\s*(Height|Chest|Waist)\b([^<]*)</th>#i',
                function (array $m) use ($measureIcons): string {
                    return '<th'.$m[1].'><span class="inline-flex items-center gap-1.5">'
                        .$measureIcons[strtolower($m[2])].$m[2].$m[3].'</span></th>';
                },
                $html
            ) ?? $html;
        };

        // Split content into separate sections at every <h2> boundary
        $sections = [];
        if ($page->content) {
            $rawParts = preg_split('/(?=<h2[\s>])/i', $page->content);
            foreach ($rawParts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $sections[] = $part;
                }
            }
        }
    @endphp

    <div class="container mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-3xl mx-auto">

            {{-- Header --}}
            <div class="text-center mb-8">
                <div class="w-14 h-14 mx-auto rounded-full {{ $iconBg }} flex items-center justify-center mb-4">
                    @if($page->slug === 'privacy-policy')
                        <svg class="w-7 h-7 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    @elseif($page->slug === 'cookie-policy')
                        <svg class="w-7 h-7 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @elseif($page->slug === 'gdpr')
                        <svg class="w-7 h-7 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    @elseif($page->slug === 'returns-policy')
                        <svg class="w-7 h-7 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    @elseif($page->slug === 'size-guides')
                        <svg class="w-7 h-7 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                    @else
                        <svg class="w-7 h-7 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    @endif
                </div>
                <h1 class="text-lg sm:text-xl font-bold text-neutral-900">{{ $page->title }}</h1>
                <p class="text-[13px] text-neutral-600 mt-2">
                    Last updated: {{ ($page->updated_at ?? $page->published_at ?? now())->format('F Y') }}@if($subtitle)
                    &middot; {{ $subtitle }}@endif
                </p>
            </div>

            {{-- Section Cards.

                 The [&_table] / [&_th] / [&_td] rules below are not decoration. A table
                 had no styling here at all, and Tailwind's preflight zeroes cell padding
                 and collapses borders, so the size chart's five columns ran together into
                 one unreadable string.

                 [&_figure]:block is load-bearing, and the reason is a name collision.
                 CKEditor wraps every table it writes in <figure class="table">, and
                 "table" is also a Tailwind display utility - so the wrapper was picking
                 up display:table and shrinking to its contents, which left the size
                 chart at 438px in an 864px card with the rest of the row empty. Setting
                 the display back beats .table on specificity without !important. The
                 figure is also what scrolls sideways on a narrow screen. --}}
            @if($sections)
                @foreach($sections as $section)
                    <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4
                                [&_h2]:text-[15px] [&_h2]:font-bold [&_h2]:text-neutral-900 [&_h2]:mb-3
                                [&_h3]:text-[13px] [&_h3]:font-semibold [&_h3]:text-neutral-900 [&_h3]:mb-2 [&_h3]:mt-3
                                [&_p]:text-[13px] [&_p]:text-neutral-600 [&_p]:leading-relaxed [&_p]:mb-2
                                [&_ul]:mt-2 [&_ul]:space-y-1.5 [&_ul]:list-disc [&_ul]:pl-5
                                [&_ol]:mt-2 [&_ol]:space-y-1.5 [&_ol]:list-decimal [&_ol]:pl-5
                                [&_li]:text-[13px] [&_li]:text-neutral-600 [&_li]:leading-relaxed [&_li]:marker:text-neutral-600
                                [&_a]:text-primary-600 [&_a]:underline [&_a]:underline-offset-2
                                [&_strong]:font-semibold [&_strong]:text-neutral-800
                                [&_blockquote]:border-l-4 [&_blockquote]:border-neutral-200 [&_blockquote]:pl-4 [&_blockquote]:italic [&_blockquote]:text-neutral-600
                                [&_figure]:block [&_figure]:my-3 [&_figure]:overflow-x-auto
                                [&_table]:w-full [&_table]:my-3 [&_table]:text-[13px]
                                [&_th]:bg-neutral-50 [&_th]:px-3 [&_th]:py-2 [&_th]:text-left [&_th]:font-semibold [&_th]:text-neutral-700 [&_th]:whitespace-nowrap [&_th]:border-b [&_th]:border-neutral-200
                                [&_td]:px-3 [&_td]:py-2 [&_td]:align-top [&_td]:text-neutral-600 [&_td]:whitespace-nowrap [&_td]:border-b [&_td]:border-neutral-100">
                        {{-- Render each section's raw HTML.

                             This list has to keep up with Admin\PageController::CONTENT_TAGS,
                             which is what the editor is allowed to save. Where the two
                             disagreed the tag was stored and then silently stripped on the way
                             out: CKEditor writes italic as <i> and never <em>, and has an
                             Underline button, so italicising or underlining a word here used
                             to survive the save and vanish from the page. <figure> is
                             CKEditor's own wrapper around a table. <img> is the one tag still
                             deliberately left out - this editor has no image plugin, so
                             nothing it saves can contain one. --}}
                        {!! $withMeasureIcons(safe_html($section, '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><span><div><figure><figcaption><table><tr><td><th><thead><tbody><blockquote><hr>')) !!}
                    </div>
                @endforeach
            @else
                <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 mb-4">
                    <p class="text-[13px] text-neutral-600 italic text-center">Content coming soon.</p>
                </div>
            @endif

            {{-- Footer / Related links --}}
            <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-6 text-center">
                <h2 class="text-[15px] font-bold text-neutral-900 mb-2">Have Questions?</h2>
                <p class="text-[13px] text-neutral-600 mb-4">Our support team is happy to help with any queries.</p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-4 py-2 min-h-10 sm:min-h-0 text-[13px] font-medium text-primary-600 border border-primary-200 rounded-lg hover:bg-primary-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Contact Us
                    </a>
                    {{-- The returns page had this button before it became editable copy, and
                         a phone number is the one thing a page body must not carry: it would
                         drift from the store's configured line the moment that changed. --}}
                    @if($supportPhone !== '')
                        <a href="tel:{{ $supportTel }}" class="inline-flex items-center gap-2 px-4 py-2 min-h-10 sm:min-h-0 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            {{ $supportPhone }}
                        </a>
                    @endif
                    @foreach($relatedLinks as $routeName => $label)
                        @continue($routeName === $selfRoute)
                        <a href="{{ route($routeName) }}" class="inline-flex items-center gap-2 px-4 py-2 min-h-10 sm:min-h-0 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-layouts.app>
