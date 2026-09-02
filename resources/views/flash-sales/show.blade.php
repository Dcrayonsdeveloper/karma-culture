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
                <div x-data="saleCountdown({{ $remainingSeconds }})" class="flex items-center justify-center gap-2 mt-5">
                    @foreach(['hours' => 'Hours', 'minutes' => 'Mins', 'secs' => 'Secs'] as $key => $label)
                        <div style="background:#2d1810; color:#efe2cb; border-radius:10px; padding:10px 14px; min-width:64px;">
                            <div class="text-xl font-bold leading-none" x-text="{{ $key }}">00</div>
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

    </div>

    @include('partials.product-listing')

</x-layouts.app>
