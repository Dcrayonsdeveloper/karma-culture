<x-layouts.app>
    <x-slot name="title">Our Story — {{ config('app.name', 'Karmaa Kulture') }}</x-slot>

    @push('meta')
        <meta name="description" content="The story behind {{ config('app.name', 'Karmaa Kulture') }} — premium tailored essentials, crafted with care for the modern individual.">
        <link rel="canonical" href="{{ route('about') }}">
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    @endpush

    <x-slot name="styles">
    <style>
        .ab { --kk-cream:#efe2cb; --kk-cream-light:#f7eedb; --kk-cream-lighter:#fbf5e8; --kk-cream-dark:#e3d2b3;
              --kk-tan-dark:#8c5c34; --kk-brown:#4a2d1a; --kk-brown-dark:#2d1810; --kk-text:#2d1810; --kk-text-muted:#7a6555;
              --kk-display:'Playfair Display',Georgia,serif;
              background:var(--kk-cream); color:var(--kk-text); }
        .ab-eyebrow { font-size:11px; letter-spacing:.32em; text-transform:uppercase; color:var(--kk-tan-dark); font-weight:700; }
        .ab-hero { padding:76px 0 52px; text-align:center; }
        .ab-title { font-family:var(--kk-display); font-size:46px; line-height:1.08; color:var(--kk-text); margin:12px 0 0; font-weight:700; }
        .ab-lead { max-width:640px; margin:18px auto 0; color:var(--kk-text-muted); font-size:16px; line-height:1.75; }
        .ab-section { padding:36px 0; }
        .ab-story { max-width:760px; margin:0 auto; }
        .ab-story h2 { font-family:var(--kk-display); font-size:28px; color:var(--kk-text); margin:0 0 16px; font-weight:600; }
        .ab-story p { color:var(--kk-text-muted); font-size:15px; line-height:1.85; margin:0 0 16px; }
        .ab-values { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
        .ab-card { background:var(--kk-cream-lighter); border:1px solid var(--kk-cream-dark); border-radius:14px; padding:28px 22px; text-align:center; }
        .ab-card-ic { width:48px; height:48px; margin:0 auto 14px; border-radius:50%; background:var(--kk-brown); color:var(--kk-cream); display:flex; align-items:center; justify-content:center; }
        .ab-card-ic svg { width:22px; height:22px; }
        .ab-card h3 { font-family:var(--kk-display); font-size:19px; color:var(--kk-text); margin:0 0 8px; font-weight:600; }
        .ab-card p { color:var(--kk-text-muted); font-size:13.5px; line-height:1.65; margin:0; }
        .ab-banner { background:var(--kk-brown-dark); color:var(--kk-cream); border-radius:16px; padding:44px 28px; text-align:center; }
        .ab-banner h2 { font-family:var(--kk-display); font-size:30px; margin:0 0 10px; font-weight:600; color:var(--kk-cream); }
        .ab-banner p { color:#c9b393; font-size:15px; line-height:1.7; max-width:520px; margin:0 auto 22px; }
        .ab-btn { display:inline-flex; align-items:center; gap:8px; padding:13px 30px; background:var(--kk-brown); color:var(--kk-cream)!important;
                  border-radius:999px; font-size:12px; letter-spacing:.18em; text-transform:uppercase; font-weight:600; text-decoration:none; transition:background .2s; }
        .ab-btn:hover { background:var(--kk-brown-dark); }
        .ab-banner .ab-btn { background:var(--kk-cream); color:var(--kk-brown)!important; }
        .ab-banner .ab-btn:hover { background:var(--kk-cream-dark); }
        .ab-cta { text-align:center; padding:44px 0 88px; }
        @media (max-width:768px) { .ab-title { font-size:34px; } .ab-values { grid-template-columns:1fr; } .ab-hero { padding:52px 0 40px; } }
    </style>
    </x-slot>

    <div class="ab">
        {{-- Breadcrumb --}}
        <div class="container mx-auto px-4 pt-4">
            <x-breadcrumb :items="[['label' => 'Our Story', 'url' => null]]" />
        </div>

        {{-- Hero --}}
        <section class="ab-hero">
            <div class="container mx-auto px-4">
                <span class="ab-eyebrow">Our Story</span>
                <h1 class="ab-title">{{ config('app.name', 'Karmaa Kulture') }}</h1>
                <p class="ab-lead">Premium tailored essentials, made for the modern individual. We craft timeless pieces with devotion to detail, quality cloth, and the culture we carry forward.</p>
            </div>
        </section>

        {{-- Story --}}
        <section class="ab-section">
            <div class="container mx-auto px-4">
                <div class="ab-story">
                    <h2>Crafted to last</h2>
                    <p>{{ config('app.name', 'Karmaa Kulture') }} began with a simple belief — that everyday wear should feel considered, comfortable and enduring. Every shirt, polo and trouser is designed around fit, fabric and finish, so it looks as good on the hundredth wear as the first.</p>
                    <p>We work with premium fabrics and refined construction to build a wardrobe of essentials that transcend trends. Less noise, more intention — pieces you reach for again and again.</p>
                    <p>More than a label, {{ config('app.name', 'Karmaa Kulture') }} is a way of dressing with meaning — where good karma, good craft and good culture come together.</p>
                </div>
            </div>
        </section>

        {{-- Values --}}
        <section class="ab-section">
            <div class="container mx-auto px-4">
                <div class="ab-values">
                    <div class="ab-card">
                        <div class="ab-card-ic">
                            <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h3>Premium Craft</h3>
                        <p>Considered cuts and quality fabrics, finished with care in every seam.</p>
                    </div>
                    <div class="ab-card">
                        <div class="ab-card-ic">
                            <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.9 5.9H20l-4.9 3.6L17 18l-5-3.7L7 18l1.9-5.5L4 8.9h6.1L12 3z"/></svg>
                        </div>
                        <h3>Timeless Design</h3>
                        <p>Versatile essentials built to outlast trends and seasons.</p>
                    </div>
                    <div class="ab-card">
                        <div class="ab-card-ic">
                            <svg fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </div>
                        <h3>Made with Intention</h3>
                        <p>Thoughtfully created, responsibly delivered — culture you can wear.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Banner CTA --}}
        <section class="ab-section">
            <div class="container mx-auto px-4">
                <div class="ab-banner">
                    <h2>Wear your culture</h2>
                    <p>Discover essentials designed to move with you — from the everyday to the occasion.</p>
                    <a href="{{ route('new-arrivals') }}" class="ab-btn">Shop New Arrivals <span aria-hidden="true">&rarr;</span></a>
                </div>
            </div>
        </section>

        {{-- Final CTA --}}
        <section class="ab-cta">
            <div class="container mx-auto px-4">
                <a href="{{ route('home') }}" class="ab-btn">Explore the Collection <span aria-hidden="true">&rarr;</span></a>
            </div>
        </section>
    </div>
</x-layouts.app>
