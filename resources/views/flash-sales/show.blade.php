<x-layouts.app>
    <x-slot name="title">{{ $flashSale->name }} - {{ config('app.name') }}</x-slot>
    <x-slot name="meta">
        <meta name="description" content="{{ Str::limit($flashSale->description ?: $flashSale->name . ' at Karmaa Kulture - limited-time prices while the sale runs.', 155) }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:title" content="{{ $flashSale->name }} - {{ config('app.name') }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        {{-- A sale is temporary; keeping it out of the index avoids stale
             offers ranking long after they end. --}}
        <meta name="robots" content="noindex, follow">
    </x-slot>

    <div class="container mx-auto px-4 py-8">

        {{-- Header --}}
        <div class="text-center mb-8">
            <p class="text-[11px] tracking-[0.22em] uppercase font-bold" style="color:#8C5C34;">Limited time offer</p>
            <h1 class="kk-display text-3xl lg:text-4xl mt-1" style="color:#2d1810;">{{ $flashSale->name }}</h1>
            @if($flashSale->description)
                <p class="text-sm text-neutral-600 mt-2 max-w-xl mx-auto">{{ $flashSale->description }}</p>
            @endif

            @if($isLive && $remainingSeconds > 0)
                <div x-data="kkSaleCountdown({{ $remainingSeconds }})" x-init="start()" class="flex items-center justify-center gap-2 mt-5">
                    @foreach(['hours' => 'Hours', 'minutes' => 'Mins', 'seconds' => 'Secs'] as $key => $label)
                        <div style="background:#2d1810; color:#efe2cb; border-radius:10px; padding:10px 14px; min-width:64px;">
                            <div class="text-xl font-bold leading-none" x-text="String({{ $key }}).padStart(2, '0')">00</div>
                            <div class="text-[10px] tracking-widest uppercase mt-1" style="opacity:.7;">{{ $label }}</div>
                        </div>
                        @if(! $loop->last)<span class="text-xl" style="color:#8C5C34;">:</span>@endif
                    @endforeach
                </div>
            @elseif($hasEnded)
                <p class="mt-4 inline-block text-xs font-semibold px-3 py-1.5 rounded-full" style="background:#f1f1f1; color:#616161;">
                    This sale has ended - prices are back to normal.
                </p>
            @else
                <p class="mt-4 inline-block text-xs font-semibold px-3 py-1.5 rounded-full" style="background:#fdf3d7; color:#9a7016;">
                    Starts {{ $flashSale->starts_at?->diffForHumans() }}
                </p>
            @endif
        </div>

        {{-- Products --}}
        @if($flashSale->products->isEmpty())
            <div class="text-center py-16">
                <p class="text-sm font-medium text-neutral-800">No products in this sale yet</p>
                <p class="text-xs text-neutral-600 mt-1">Check back shortly.</p>
                <a href="{{ route('home') }}" class="inline-block mt-4 text-sm font-semibold" style="color:#8C5C34;">Continue shopping</a>
            </div>
        @else
            <p class="text-sm text-neutral-600 mb-4">
                {{ $flashSale->products->count() }} {{ Str::plural('product', $flashSale->products->count()) }} on sale
            </p>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($flashSale->products as $product)
                    @php
                        $salePrice = $isLive ? $product->flashSalePrice() : null;
                        $limit = $product->pivot->stock_limit;
                        $sold = (int) ($product->pivot->sold_count ?? 0);
                        $left = $limit !== null ? max(0, (int) $limit - $sold) : null;
                    @endphp
                    <div class="relative">
                        @if($salePrice)
                            <span class="absolute top-2 left-2 z-10 text-[10px] font-bold px-2 py-1 rounded-full text-white" style="background:#8C5C34;">
                                {{ (int) round((1 - $salePrice / max(0.01, (float) $product->price)) * 100) }}% OFF
                            </span>
                        @endif

                        <x-product-card :product="$product" />

                        @if($salePrice)
                            <p class="text-xs mt-1" style="color:#8C5C34;">
                                <strong>@price($salePrice)</strong>
                                <span class="text-neutral-500 line-through ml-1">@price($product->price)</span>
                            </p>
                            @if($left !== null)
                                <p class="text-[11px] text-neutral-600 mt-0.5">
                                    {{ $left > 0 ? $left . ' left at this price' : 'Sale price sold out' }}
                                </p>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function kkSaleCountdown(seconds) {
            return {
                remaining: seconds,
                hours: 0, minutes: 0, seconds: 0,
                start() {
                    this.tick();
                    setInterval(() => {
                        if (this.remaining > 0) { this.remaining--; this.tick(); }
                    }, 1000);
                },
                tick() {
                    this.hours = Math.floor(this.remaining / 3600);
                    this.minutes = Math.floor((this.remaining % 3600) / 60);
                    this.seconds = this.remaining % 60;
                },
            };
        }
    </script>
    @endpush
</x-layouts.app>
