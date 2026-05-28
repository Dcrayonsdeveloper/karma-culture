<x-layouts.admin>
    <x-slot name="title">Homepage Manager</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Homepage Manager</h1>
        </div>
    </x-slot>

    <!-- Quick Links -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
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

        <a href="{{ route('admin.homepage.testimonials') }}" class="card" style="padding: 1rem; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; transition: box-shadow 0.15s;">
            <div style="width: 2.5rem; height: 2.5rem; background: #f0f0f0; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 1.25rem; height: 1.25rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <div>
                <div style="font-size: 13px; font-weight: 600; color: #303030;">Testimonials</div>
                <div style="font-size: 12px; color: #616161;">{{ $testimonials->count() }} reviews</div>
            </div>
        </a>
    </div>

    <!-- Current Homepage Preview -->
    <div class="card">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Section Order</h2>
        </div>
        <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
            @foreach($sections->sortBy('position') as $section)
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: #f6f6f7; border-radius: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="width: 2rem; height: 2rem; background: white; border-radius: 0.375rem; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; color: #616161; border: 1px solid #e3e3e3;">
                            {{ $section->position + 1 }}
                        </span>
                        <div>
                            <span style="font-size: 13px; font-weight: 500; color: #303030;">{{ $section->title }}</span>
                            <span style="font-size: 12px; color: #616161; margin-left: 0.5rem;">{{ $section->type }}</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        @if($section->is_active)
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                        @else
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ebebeb; color: #616161;">Hidden</span>
                        @endif
                        <a href="{{ route('admin.homepage.sections.edit', $section) }}" class="btn btn-secondary" style="font-size: 12px; padding: 0.25rem 0.5rem;">Edit</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
