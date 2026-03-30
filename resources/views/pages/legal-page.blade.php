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
            default            => 'bg-neutral-100',
        };
        $iconColor = match($page->slug) {
            'privacy-policy'   => 'text-[#6F9CA2]',
            'terms-of-service' => 'text-neutral-600',
            'cookie-policy'    => 'text-amber-600',
            'gdpr'             => 'text-primary-600',
            default            => 'text-neutral-600',
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
                    @else
                        <svg class="w-7 h-7 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    @endif
                </div>
                <h1 class="text-lg sm:text-xl font-bold text-neutral-900">{{ $page->title }}</h1>
                <p class="text-[13px] text-neutral-600 mt-2">
                    Last updated: {{ ($page->updated_at ?? $page->published_at ?? now())->format('F Y') }}
                    &middot; Please read this document carefully.
                </p>
            </div>

            {{-- Section Cards --}}
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
                                [&_blockquote]:border-l-4 [&_blockquote]:border-neutral-200 [&_blockquote]:pl-4 [&_blockquote]:italic [&_blockquote]:text-neutral-600">
                        {{-- Render each section's raw HTML --}}
                        {!! strip_tags($section, '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><a><span><div><table><tr><td><th><thead><tbody><blockquote><hr>') !!}
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
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-primary-600 border border-primary-200 rounded-lg hover:bg-primary-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Contact Us
                    </a>
                    @if($page->slug !== 'privacy-policy')
                        <a href="{{ route('privacy') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                            Privacy Policy
                        </a>
                    @endif
                    @if($page->slug !== 'terms-of-service')
                        <a href="{{ route('terms') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                            Terms of Service
                        </a>
                    @endif
                    @if($page->slug !== 'cookie-policy')
                        <a href="{{ route('cookie-policy') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                            Cookie Policy
                        </a>
                    @endif
                    @if($page->slug !== 'gdpr')
                        <a href="{{ route('gdpr') }}" class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-neutral-700 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
                            GDPR
                        </a>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-layouts.app>
