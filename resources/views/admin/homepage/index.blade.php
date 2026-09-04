<x-layouts.admin>
    <x-slot name="title">Homepage Manager</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Homepage Manager</h1>
        </div>
    </x-slot>

    {{-- Quick links. auto-fit rather than a fixed 4 columns: there are six
         cards now, and a fixed track count squeezed them to unreadable slivers
         on a laptop-width screen. --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <a href="{{ route('admin.homepage.site-settings') }}" class="card" style="padding: 1rem; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; transition: box-shadow 0.15s;">
            <div style="width: 2.5rem; height: 2.5rem; background: #f0f0f0; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <div style="font-size: 13px; font-weight: 600; color: #303030;">Site Settings</div>
                <div style="font-size: 12px; color: #616161;">Logo, Brand, Social</div>
            </div>
        </a>

        <a href="{{ route('admin.homepage.hero-banners') }}" class="card" style="padding: 1rem; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; transition: box-shadow 0.15s;">
            <div style="width: 2.5rem; height: 2.5rem; background: #f0f0f0; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <div style="font-size: 13px; font-weight: 600; color: #303030;">Hero Banners</div>
                <div style="font-size: 12px; color: #616161;">{{ $banners->count() }} banners</div>
            </div>
        </a>

        <a href="{{ route('admin.homepage.sections') }}" class="card" style="padding: 1rem; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; transition: box-shadow 0.15s;">
            <div style="width: 2.5rem; height: 2.5rem; background: #f0f0f0; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
            </div>
            <div>
                <div style="font-size: 13px; font-weight: 600; color: #303030;">Sections</div>
                <div style="font-size: 12px; color: #616161;">{{ $sections->where('is_active', true)->count() }} active</div>
            </div>
        </a>

        <a href="{{ route('admin.homepage.shop-filters') }}" class="card" style="padding: 1rem; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; transition: box-shadow 0.15s;">
            <div style="width: 2.5rem; height: 2.5rem; background: #f0f0f0; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M6 12h12M10 18h4"/>
                </svg>
            </div>
            <div>
                <div style="font-size: 13px; font-weight: 600; color: #303030;">Shop Filters</div>
                <div style="font-size: 12px; color: #616161;">Size, Price, Shade, Texture &mdash; from your products</div>
            </div>
        </a>

        <a href="{{ route('admin.homepage.about-reels') }}" class="card" style="padding: 1rem; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; transition: box-shadow 0.15s;">
            <div style="width: 2.5rem; height: 2.5rem; background: #f0f0f0; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16v12H4zM10 9.5l5 2.5-5 2.5z"/>
                </svg>
            </div>
            <div>
                <div style="font-size: 13px; font-weight: 600; color: #303030;">About Us Reels</div>
                <div style="font-size: 12px; color: #616161;">{{ \App\Models\AboutReel::active()->count() }} on the home page</div>
            </div>
        </a>

        <a href="{{ route('admin.homepage.qualities') }}" class="card" style="padding: 1rem; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; transition: box-shadow 0.15s;">
            <div style="width: 2.5rem; height: 2.5rem; background: #f0f0f0; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l2.6 6 6.4.6-5 4.6 1.4 6.4L12 17l-5.4 3.6L8 14.2 3 9.6l6.4-.6L12 3z"/>
                </svg>
            </div>
            <div>
                <div style="font-size: 13px; font-weight: 600; color: #303030;">Our Qualities</div>
                <div style="font-size: 12px; color: #616161;">Dark section cards</div>
            </div>
        </a>

        {{-- Navigation (header & footer menus) is deliberately not tiled here:
             it is site-wide chrome rather than a home page block, and the store
             owner asked for it off this screen. The route and its editor are
             untouched - admin/homepage/navigation still works if linked again
             from somewhere that fits it better. --}}
    </div>

    {{-- This panel used to be headed "Section Order" and showed a big numbered
         badge per row, which promised something the site cannot do: the home page
         is hand-built markup, not a loop over this table, so the `position` column
         never changed the order of anything a visitor sees. The number was also
         just `position + 1` carried over from an old seeder, which is why a single
         remaining section was labelled "11". What the table really holds is the
         editable wording of a few home page blocks, so that is what it now says. --}}
    <div class="card">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Editable Homepage Blocks</h2>
            <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">Wording and visibility of the text blocks on the home page. The order the blocks appear in is fixed by the page design and is not editable here.</p>
        </div>
        <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
            @forelse($sections as $section)
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.75rem; background: #f6f6f7; border-radius: 0.5rem;">
                    <div style="min-width: 0;">
                        <div style="font-size: 13px; font-weight: 500; color: #303030;">{{ $section->title }}</div>
                        <div style="font-size: 12px; color: #616161; margin-top: 0.125rem;">
                            @switch($section->key)
                                @case('about_us')
                                    Heading, tagline and button of the About Us video block
                                    @break
                                @default
                                    @switch($section->type)
                                        @case('products')
                                            Heading of a product slider
                                            @break
                                        @case('benefits')
                                            Feature cards ({{ is_array($section->content) ? count($section->content) : 0 }} items)
                                            @break
                                        @case('cta')
                                            Promotional banner
                                            @break
                                        @case('newsletter')
                                            Heading of the email signup block
                                            @break
                                        @case('categories')
                                            Heading of the category grid
                                            @break
                                        @default
                                            Text block
                                    @endswitch
                            @endswitch
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                        @if($section->is_active)
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Visible</span>
                        @else
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ebebeb; color: #616161;">Hidden</span>
                        @endif
                        <a href="{{ route('admin.homepage.sections.edit', $section) }}" class="btn btn-secondary" style="font-size: 12px; padding: 0.25rem 0.5rem;">Edit</a>
                    </div>
                </div>
            @empty
                <div style="padding: 1.5rem 1rem; text-align: center; color: #616161; font-size: 13px;">
                    No editable text blocks yet. The home page is showing its built-in wording.
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.admin>
