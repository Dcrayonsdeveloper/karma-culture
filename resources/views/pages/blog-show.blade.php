<x-layouts.app>
    <x-slot name="title">{{ $post->seo_data['meta_title'] ?? $post->title }}</x-slot>

    @push('meta')
        <meta name="description" content="{{ $post->seo_data['meta_description'] ?? $post->excerpt ?? Str::limit(strip_tags($post->content), 160) }}">
        <link rel="canonical" href="{{ route('blog.show', $post->slug) }}">
        <meta property="og:title" content="{{ $post->seo_data['meta_title'] ?? $post->title }}">
        <meta property="og:description" content="{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 160) }}">
        <meta property="og:type" content="article">
        <meta property="og:url" content="{{ route('blog.show', $post->slug) }}">
        @if($post->featured_image)
        <meta property="og:image" content="{{ asset('storage/' . $post->featured_image) }}">
        @endif
        <meta property="article:published_time" content="{{ $post->published_at?->toIso8601String() }}">
        <meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
        @if($post->category)
        <meta property="article:section" content="{{ $post->category }}">
        @endif
        @if($post->tags)
            @foreach($post->tags as $tag)
        <meta property="article:tag" content="{{ $tag }}">
            @endforeach
        @endif
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $post->seo_data['meta_title'] ?? $post->title }}">
        <meta name="twitter:description" content="{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 160) }}">
        @if($post->featured_image)
        <meta name="twitter:image" content="{{ asset('storage/' . $post->featured_image) }}">
        @endif

        {{-- BlogPosting JSON-LD --}}
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $post->excerpt ?? Str::limit(strip_tags($post->content), 160),
            'url' => route('blog.show', $post->slug),
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'ForeverKids'),
                'url' => url('/'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'ForeverKids'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/colorlogo.png'),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('blog.show', $post->slug),
            ],
            'image' => $post->featured_image ? asset('storage/' . $post->featured_image) : null,
            'articleSection' => $post->category,
            'keywords' => $post->tags ? implode(', ', $post->tags) : null,
            'wordCount' => str_word_count(strip_tags($post->content ?? '')),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endpush

    {{-- Breadcrumb --}}
    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[
                ['label' => 'Blog', 'url' => route('blog')],
                ['label' => $post->title, 'url' => null],
            ]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 sm:py-10">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                {{-- ── Main Content (left) ── --}}
                <div class="lg:col-span-2 min-w-0">

                    {{-- Category & Meta --}}
                    <div class="flex items-center gap-2 mb-4 flex-wrap">
                        @if($post->category)
                            <a href="{{ route('blog', ['category' => $post->category]) }}"
                               class="inline-flex px-3 py-1 text-[11px] font-bold text-primary-700 bg-primary-50 rounded-full hover:bg-primary-100 transition-colors uppercase tracking-wide">
                                {{ $post->category }}
                            </a>
                            <span class="text-neutral-300">·</span>
                        @endif
                        <span class="text-[13px] text-neutral-600">{{ $post->reading_time }} min read</span>
                        <span class="text-neutral-300">·</span>
                        <span class="text-[13px] text-neutral-600">{{ $post->published_at?->format('M d, Y') }}</span>
                        <span class="text-neutral-300">·</span>
                        <span class="text-[13px] text-neutral-600">{{ number_format($post->view_count) }} views</span>
                    </div>

                    {{-- Title --}}
                    <h1 class="text-2xl sm:text-3xl font-bold text-neutral-900 leading-snug mb-4">
                        {{ $post->title }}
                    </h1>

                    {{-- Excerpt --}}
                    @if($post->excerpt)
                        <p class="text-[15px] text-neutral-600 mb-6 leading-relaxed border-l-4 border-primary-200 pl-4">{{ $post->excerpt }}</p>
                    @endif

                    {{-- Featured Image --}}
                    @if($post->featured_image)
                        <div class="rounded-xl overflow-hidden mb-6 aspect-video bg-neutral-100">
                            <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->title }}"
                                 class="w-full h-full object-cover">
                        </div>
                    @endif

                    {{-- Content --}}
                    <div class="bg-white border border-neutral-100 rounded-xl p-5 sm:p-8 mb-6">
                        @if($post->content)
                            <div class="prose prose-neutral prose-headings:font-bold prose-a:text-primary-600 prose-img:rounded-xl max-w-none text-neutral-700 leading-relaxed">
                                {!! strip_tags($post->content, '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><a><span><div><table><tr><td><th><thead><tbody><img><blockquote><hr><figure><figcaption><pre><code>') !!}
                            </div>
                        @else
                            <p class="text-neutral-600 italic text-[13px]">No content available.</p>
                        @endif
                    </div>

                    {{-- Tags --}}
                    @if($post->tags && count($post->tags))
                        <div class="flex flex-wrap gap-2 mb-6">
                            <span class="text-[13px] text-neutral-600 font-medium self-center">Tags:</span>
                            @foreach($post->tags as $tag)
                                <span class="px-3 py-1 bg-neutral-100 text-neutral-600 rounded-full text-[13px]">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Back link --}}
                    <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 text-[13px] font-medium text-primary-600 hover:text-primary-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Blog
                    </a>
                </div>

                {{-- ── Sidebar (right) ── --}}
                <div class="lg:col-span-1">
                    <div class="lg:sticky lg:top-6 space-y-4">

                        {{-- More Articles --}}
                        <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                            <div class="px-4 py-3 border-b border-neutral-100 flex items-center justify-between">
                                <h2 class="text-[14px] font-bold text-neutral-900">More Articles</h2>
                                <a href="{{ route('blog') }}" class="text-[12px] text-primary-600 hover:text-primary-700 font-medium">View all</a>
                            </div>

                            @if($related->count())
                                <div class="divide-y divide-neutral-100">
                                    @foreach($related as $rPost)
                                        <a href="{{ route('blog.show', $rPost->slug) }}"
                                           class="flex items-start gap-3 p-3 hover:bg-neutral-50 transition-colors group">
                                            {{-- Thumbnail --}}
                                            <div class="w-16 h-12 rounded-lg overflow-hidden bg-neutral-100 shrink-0">
                                                @if($rPost->featured_image)
                                                    <img src="{{ asset('storage/' . $rPost->featured_image) }}" alt="{{ $rPost->title }}"
                                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center bg-neutral-50">
                                                        <svg class="w-5 h-5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            {{-- Info --}}
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[13px] font-semibold text-neutral-900 line-clamp-2 group-hover:text-primary-600 transition-colors leading-snug">
                                                    {{ $rPost->title }}
                                                </p>
                                                <div class="flex items-center gap-1.5 mt-1">
                                                    @if($rPost->category)
                                                        <span class="text-[11px] text-primary-600 font-medium">{{ $rPost->category }}</span>
                                                        <span class="text-neutral-300">·</span>
                                                    @endif
                                                    <span class="text-[11px] text-neutral-600">{{ $rPost->published_at?->format('M d, Y') }}</span>
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-4 text-center">
                                    <p class="text-[13px] text-neutral-600">No other articles yet.</p>
                                </div>
                            @endif
                        </div>

                        {{-- Browse by Category --}}
                        @php
                            $sidebarCategories = \App\Models\BlogPost::published()
                                ->whereNotNull('category')
                                ->distinct()
                                ->pluck('category');
                        @endphp
                        @if($sidebarCategories->count())
                            <div class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                                <div class="px-4 py-3 border-b border-neutral-100">
                                    <h2 class="text-[14px] font-bold text-neutral-900">Browse by Topic</h2>
                                </div>
                                <div class="p-3 flex flex-wrap gap-2">
                                    @foreach($sidebarCategories as $cat)
                                        <a href="{{ route('blog', ['category' => $cat]) }}"
                                           class="px-3 py-1.5 rounded-full text-[12px] font-medium bg-neutral-100 text-neutral-600 hover:bg-primary-50 hover:text-primary-700 transition-colors">
                                            {{ $cat }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>
</x-layouts.app>
