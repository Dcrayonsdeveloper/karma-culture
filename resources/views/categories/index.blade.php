<x-layouts.app>
    <x-slot name="title">All Categories - {{ config('app.name') }}</x-slot>

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-neutral-100">
        <div class="container mx-auto px-4 py-2.5">
            <x-breadcrumb :items="[['label' => 'Categories', 'url' => null]]" />
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 md:py-8">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5">
            @foreach($categories as $category)
                <a href="{{ route('category.show', $category) }}" class="group bg-white rounded-lg border border-neutral-100 overflow-hidden hover:shadow-md hover:border-neutral-200 transition-all duration-200">
                    {{-- The tiles stay 4:5 portrait so a row lines up, but the uploads are
                         not, so the image is shown whole over a blurred copy of
                         itself instead of being cropped to the frame. A path that
                         404s lands on the placeholder once and then on the frame's
                         broken state - it used to leave an empty tile. --}}
                    <x-media :src="$category->image_url ? asset_v('storage/' . $category->image_url) : null"
                             :alt="$category->name"
                             :fallback="asset_v('images/no-product-image.svg')"
                             zoom
                             class="aspect-[4/5]">
                        @unless($category->image_url)
                            <div class="absolute inset-0 flex items-center justify-center bg-neutral-50 bg-gradient-to-br from-[#6F9CA2]/5 to-[#6F9CA2]/10">
                                <svg class="w-12 h-12 text-[#6F9CA2]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                        @endunless
                        {{-- z-10 keeps the badge above the contained image, which
                             the frame lifts to z-1. --}}
                        <div class="absolute bottom-2 right-2 z-10">
                            <span class="px-2 py-0.5 bg-black/50 backdrop-blur-sm text-white text-[10px] font-medium rounded-full">{{ $category->products_count }} products</span>
                        </div>
                    </x-media>
                    <div class="p-3">
                        <h3 class="font-semibold text-sm text-neutral-900 group-hover:text-[#6F9CA2] transition-colors">
                            {{ $category->name }}
                        </h3>
                        @if($category->children->count())
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach($category->children->take(3) as $child)
                                    <span class="text-[11px] text-neutral-600 bg-neutral-50 border border-neutral-100 rounded-full px-2 py-0.5">{{ $child->name }}</span>
                                @endforeach
                                @if($category->children->count() > 3)
                                    <span class="text-[11px] text-[#6F9CA2] bg-[#6F9CA2]/5 border border-[#6F9CA2]/15 rounded-full px-2 py-0.5">+{{ $category->children->count() - 3 }} more</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.app>
