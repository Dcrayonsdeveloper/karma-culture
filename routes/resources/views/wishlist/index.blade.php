<x-layouts.app>
    <x-slot name="title">My Wishlist</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8 max-w-4xl">
            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
                <a href="{{ route('home') }}" class="hover:text-primary-600 transition-colors">Home</a>
                <svg class="w-3.5 h-3.5 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-neutral-900 font-medium">Wishlist</span>
            </nav>

            @if($wishlistItems->count())
                {{-- Header --}}
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h1 class="text-2xl font-bold text-neutral-900">My Wishlist</h1>
                        <p class="text-sm text-neutral-600 mt-1">{{ $wishlistItems->total() }} {{ Str::plural('item', $wishlistItems->total()) }} saved</p>
                    </div>
                </div>

                {{-- List --}}
                <div class="bg-white rounded-xl border border-neutral-200 divide-y divide-neutral-100">
                    @foreach($wishlistItems as $item)
                        @php
                            $product = $item->product;
                            $hasDiscount = $product->price < $product->mrp;
                            $discount = $product->discount_percentage ?? 0;
                            $outOfStock = !$product->isInStock();
                        @endphp
                        <div class="flex items-center gap-4 p-4 group {{ $outOfStock ? 'opacity-60' : '' }}">
                            {{-- Image --}}
                            <a href="{{ route('product.show', $product) }}" class="shrink-0">
                                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-lg overflow-hidden bg-neutral-50 ring-1 ring-neutral-200">
                                    <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                                </div>
                            </a>

                            {{-- Details --}}
                            <div class="flex-1 min-w-0">
                                @if($product->brand)
                                    <p class="text-[11px] text-neutral-600 uppercase tracking-wide mb-0.5">{{ $product->brand->name }}</p>
                                @endif
                                <a href="{{ route('product.show', $product) }}" class="text-sm font-semibold text-neutral-900 hover:text-primary-600 transition-colors line-clamp-2">
                                    {{ $product->name }}
                                </a>

                                {{-- Price --}}
                                <div class="flex items-baseline gap-2 mt-1.5">
                                    <span class="text-base font-bold text-neutral-900">@price($product->price)</span>
                                    @if($hasDiscount)
                                        <span class="text-xs text-neutral-600 line-through">@price($product->mrp)</span>
                                        <span class="text-xs font-semibold text-success-600">{{ round($discount) }}% off</span>
                                    @endif
                                </div>

                                {{-- Stock status --}}
                                @if($outOfStock)
                                    <p class="text-xs font-medium text-error-500 mt-1">Out of Stock</p>
                                @else
                                    <p class="text-xs text-success-600 mt-1">In Stock</p>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-2 shrink-0">
                                @unless($outOfStock)
                                    <button @click="$store.cart.add({{ $product->id }})"
                                            class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                        Add to Cart
                                    </button>
                                    {{-- Mobile: icon-only cart button --}}
                                    <button @click="$store.cart.add({{ $product->id }})"
                                            class="sm:hidden w-9 h-9 flex items-center justify-center text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors"
                                            aria-label="Add to cart">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                    </button>
                                @endunless

                                {{-- Remove --}}
                                <button @click="$store.wishlist.toggle({{ $product->id }}); $el.closest('.divide-y > div').remove()"
                                        class="w-9 h-9 flex items-center justify-center text-neutral-600 hover:text-error-500 hover:bg-error-50 rounded-lg transition-colors"
                                        aria-label="Remove from wishlist">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($wishlistItems->hasPages())
                    <div class="mt-8">
                        {{ $wishlistItems->links() }}
                    </div>
                @endif
            @else
                {{-- Empty State --}}
                <div class="max-w-md mx-auto text-center py-20">
                    <div class="w-20 h-20 mx-auto mb-6 bg-neutral-100 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-neutral-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-neutral-900 mb-2">Your wishlist is empty</h2>
                    <p class="text-neutral-600 mb-6">Save items you love to your wishlist and come back to them anytime.</p>
                    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Browse Products
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
