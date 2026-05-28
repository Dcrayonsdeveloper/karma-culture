<x-layouts.app>
    <x-slot name="title">All Collections - {{ config('app.name') }}</x-slot>

    <x-slot name="styles">
        <style>
            .kc-hero {
                background: linear-gradient(180deg, #FAF5EF 0%, #F2E4D2 100%);
                padding: 36px 16px 30px; text-align: center;
            }
            .kc-hero-eyebrow {
                font-size: 11px; font-weight: 700; letter-spacing: 0.3em;
                text-transform: uppercase; color: #5E3A26; margin-bottom: 14px;
            }
            .kc-hero-title {
                font-family: 'Bricolage Grotesque', sans-serif;
                font-size: clamp(32px, 4.2vw, 56px); font-weight: 800;
                letter-spacing: -0.02em; margin: 0;
                text-transform: capitalize;
            }
            .kc-hero-sub {
                font-size: 14px; color: #595959; margin: 14px auto 0;
                max-width: 540px; line-height: 1.7;
            }

            /* Gender Section */
            .kc-section { padding: 38px 0; }
            .kc-section:nth-child(even) { background: #FAF5EF; }
            .kc-container { max-width: 100%; margin: 0 auto; padding: 0 16px; }
            .kc-section-head {
                display: flex; align-items: flex-end; justify-content: space-between;
                gap: 24px; margin-bottom: 36px; flex-wrap: wrap;
            }
            .kc-section-meta { display: flex; align-items: baseline; gap: 18px; }
            .kc-section-num {
                font-size: 13px; font-weight: 700; letter-spacing: 0.25em;
                color: #5E3A26; text-transform: uppercase;
            }
            .kc-section-title {
                font-family: 'Bricolage Grotesque', sans-serif;
                font-size: clamp(28px, 3.4vw, 44px); font-weight: 800;
                letter-spacing: -0.02em; margin: 0;
                text-transform: capitalize;
            }
            .kc-section-count {
                font-size: 12px; color: #595959; letter-spacing: 0.08em;
                text-transform: uppercase;
            }
            .kc-view-all {
                font-size: 12px; font-weight: 700; letter-spacing: 0.2em;
                text-transform: uppercase; color: #5E3A26; text-decoration: none;
                border-bottom: 1.5px solid #5E3A26; padding-bottom: 3px;
                transition: color 0.2s, border-color 0.2s;
            }
            .kc-view-all:hover { color: #1a1a1a; border-color: #1a1a1a; }

            /* Category cards grid */
            .kc-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
            }
            .kc-card {
                position: relative; display: flex; flex-direction: column;
                background: #fff; border: 1px solid #e8e3da;
                border-radius: 12px; overflow: hidden;
                text-decoration: none; color: #1a1a1a;
                transition: transform 0.35s cubic-bezier(0.2,0.8,0.3,1.2),
                            box-shadow 0.35s ease,
                            border-color 0.3s ease;
            }
            .kc-card:hover {
                transform: translateY(-6px);
                box-shadow: 0 20px 36px -16px rgba(62,42,31,0.22);
                border-color: #5E3A26;
            }
            .kc-card-thumb {
                aspect-ratio: 4/5;
                background: linear-gradient(135deg, #F2E4D2 0%, #C9A27B 100%);
                position: relative; overflow: hidden;
            }
            .kc-card-thumb img {
                width: 100%; height: 100%; object-fit: cover;
                transition: transform 0.6s ease;
            }
            .kc-card:hover .kc-card-thumb img { transform: scale(1.07); }
            .kc-card-icon {
                position: absolute; inset: 0;
                display: flex; align-items: center; justify-content: center;
                color: rgba(94,58,38,0.32);
            }
            .kc-card-count {
                position: absolute; top: 14px; right: 14px;
                background: rgba(255,255,255,0.92);
                color: #1a1a1a;
                font-size: 10px; font-weight: 700;
                letter-spacing: 0.15em; text-transform: uppercase;
                padding: 5px 10px; border-radius: 999px;
                backdrop-filter: blur(4px);
            }
            .kc-card-body {
                padding: 18px 20px 20px;
                flex: 1; display: flex; flex-direction: column;
            }
            .kc-card-name {
                font-family: 'Bricolage Grotesque', sans-serif;
                font-size: 17px; font-weight: 700; letter-spacing: -0.01em;
                margin: 0 0 10px;
                text-transform: capitalize;
                transition: color 0.2s;
            }
            .kc-card:hover .kc-card-name { color: #5E3A26; }
            .kc-subs {
                display: flex; flex-wrap: wrap; gap: 6px;
                margin-top: auto;
            }
            .kc-sub-chip {
                font-size: 10px; font-weight: 600;
                letter-spacing: 0.08em; text-transform: uppercase;
                color: #5E3A26; background: #FAF5EF;
                border: 1px solid #e8e3da;
                padding: 5px 10px; border-radius: 999px;
            }
            .kc-sub-chip:hover { background: #F2E4D2; border-color: #C9A27B; }
            .kc-card-cta {
                margin-top: 10px;
                font-size: 11px; font-weight: 700;
                letter-spacing: 0.2em; text-transform: uppercase;
                color: #5E3A26;
                display: inline-flex; align-items: center; gap: 6px;
            }
            .kc-card:hover .kc-card-cta { gap: 12px; }

            @media (max-width: 1024px) { .kc-grid { grid-template-columns: repeat(3, 1fr); } }
            @media (max-width: 720px)  { .kc-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; } }
            @media (max-width: 420px)  { .kc-grid { grid-template-columns: 1fr; } }
        </style>
    </x-slot>

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-[#e8e3da]">
        <div class="container mx-auto px-4 py-2.5">
            <x-breadcrumb :items="[['label' => 'Collections', 'url' => null]]" />
        </div>
    </div>

    {{-- ============ HERO ============ --}}
    <section class="kc-hero">
        <div class="kc-hero-eyebrow">Karmaa Kulture</div>
        <h1 class="kc-hero-title">Shop The Collections</h1>
        <p class="kc-hero-sub">A wardrobe organised by silhouette — pick a category from the men's or women's edit.</p>
    </section>

    {{-- ============ GENDER SECTIONS ============ --}}
    @php
        // Pull the two gender roots if they exist; the controller passes all roots,
        // so we filter & sort them in the order: Men's first, then Women's, then others.
        $genderOrder = ["Men's", "Women's"];
        $genderRoots = $categories->filter(fn($c) => in_array($c->name, $genderOrder))
            ->sortBy(fn($c) => array_search($c->name, $genderOrder))
            ->values();
        $otherRoots = $categories->filter(fn($c) => !in_array($c->name, $genderOrder))->values();

        $catIcons = [
            'T-Shirts'     => 'M8 4h8l3 4-3 1v11H8V9L5 8z',
            'Shirts'       => 'M7 3l5 3 5-3 4 4-3 3v11H6V10L3 7z',
            'Kurtas'       => 'M8 3l4 2 4-2 3 3-3 3v12H8V9L5 6z',
            'Trousers'     => 'M8 3h8v3l-1 15h-3l-1-12-1 12H7L6 6z',
            'Tops'         => 'M9 3l3 2 3-2 4 4-3 3v8H8v-8L5 7z',
            'Jumpsuits'    => 'M9 3l3 2 3-2 3 4-2 2v14H8V9L6 7z',
            'Co-ord Sets'  => 'M5 5h14v5H5zm0 9h14v5H5z',
            'One Piece' => 'M9 3l3 2 3-2 3 4-3 16H9L6 7z',
        ];
    @endphp

    @foreach($genderRoots as $i => $gender)
        <section class="kc-section">
            <div class="kc-container">
                <div class="kc-section-head">
                    <div class="kc-section-meta">
                        <div>
                            <div class="kc-section-num">Collection 0{{ $i + 1 }}</div>
                            <h2 class="kc-section-title">{{ $gender->name }}</h2>
                        </div>
                        <span class="kc-section-count">{{ $gender->children->count() }} categories</span>
                    </div>
                    <a href="{{ route('category.show', $gender) }}" class="kc-view-all">
                        Shop All →
                    </a>
                </div>

                <div class="kc-grid">
                    @foreach($gender->children->sortBy('position') as $cat)
                        <a href="{{ route('category.show', $cat) }}" class="kc-card">
                            <div class="kc-card-thumb">
                                @if($cat->image_url)
                                    <img src="{{ asset('storage/' . $cat->image_url) }}" alt="{{ $cat->name }}" loading="lazy">
                                @else
                                    <div class="kc-card-icon">
                                        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="{{ $catIcons[$cat->name] ?? 'M3 7h18v10H3zM7 7v3M11 7v3M15 7v3M19 7v3' }}"/>
                                        </svg>
                                    </div>
                                @endif
                                @if(($cat->products_count ?? 0) > 0)
                                    <span class="kc-card-count">{{ $cat->products_count }} styles</span>
                                @endif
                            </div>
                            <div class="kc-card-body">
                                <h3 class="kc-card-name">{{ $cat->name }}</h3>
                                @if($cat->children->count())
                                    <div class="kc-subs">
                                        @foreach($cat->children->take(5) as $sub)
                                            <span class="kc-sub-chip">{{ $sub->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="kc-card-cta">Explore →</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach

    {{-- Any non-gender roots (fallback so older categories still appear) --}}
    @if($otherRoots->count())
        <section class="kc-section">
            <div class="kc-container">
                <div class="kc-section-head">
                    <div class="kc-section-meta">
                        <div>
                            <div class="kc-section-num">Other Collections</div>
                            <h2 class="kc-section-title">More to Explore</h2>
                        </div>
                    </div>
                </div>
                <div class="kc-grid">
                    @foreach($otherRoots as $cat)
                        <a href="{{ route('category.show', $cat) }}" class="kc-card">
                            <div class="kc-card-thumb">
                                @if($cat->image_url)
                                    <img src="{{ asset('storage/' . $cat->image_url) }}" alt="{{ $cat->name }}" loading="lazy">
                                @else
                                    <div class="kc-card-icon">
                                        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M3 7h18v10H3z"/></svg>
                                    </div>
                                @endif
                                @if(($cat->products_count ?? 0) > 0)
                                    <span class="kc-card-count">{{ $cat->products_count }} styles</span>
                                @endif
                            </div>
                            <div class="kc-card-body">
                                <h3 class="kc-card-name">{{ $cat->name }}</h3>
                                @if($cat->children->count())
                                    <div class="kc-subs">
                                        @foreach($cat->children->take(5) as $sub)
                                            <span class="kc-sub-chip">{{ $sub->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.app>
