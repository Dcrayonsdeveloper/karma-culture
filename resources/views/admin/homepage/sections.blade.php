<x-layouts.admin>
    <x-slot name="title">Homepage Sections</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Homepage Sections</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    <div class="card">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">All Sections</h2>
            <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">Toggle visibility and edit content of homepage sections</p>
        </div>
        <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
            @foreach($sections as $section)
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: #f6f6f7; border-radius: 0.5rem; border: 1px solid #e3e3e3; {{ !$section->is_active ? 'opacity: 0.6;' : '' }}">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="cursor: move; color: #616161;">
                            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                            </svg>
                        </div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.125rem;">
                                <span style="font-size: 13px; font-weight: 600; color: #303030;">{{ $section->title }}</span>
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 10px; font-weight: 500; background: #ebebeb; color: #616161;">{{ $section->key }}</span>
                            </div>
                            <p style="font-size: 12px; color: #616161; margin: 0;">
                                @switch($section->type)
                                    @case('products')
                                        Product slider section
                                        @break
                                    @case('benefits')
                                        Feature cards ({{ is_array($section->content) ? count($section->content) : 0 }} items)
                                        @break
                                    @case('cta')
                                        Promotional banner
                                        @break
                                    @case('testimonials')
                                        Customer reviews carousel
                                        @break
                                    @case('newsletter')
                                        Email subscription form
                                        @break
                                    @case('categories')
                                        Category collections grid
                                        @break
                                    @default
                                        {{ ucfirst($section->type) }} content
                                @endswitch
                            </p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <form action="{{ route('admin.homepage.sections.toggle', $section) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('PUT')
                            @if($section->is_active)
                                <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 0.25rem 0.5rem;">
                                    <span style="display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 50%; background: #1a7a2e; margin-right: 0.25rem;"></span>
                                    Visible
                                </button>
                            @else
                                <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 0.25rem 0.5rem;">
                                    <span style="display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 50%; background: #616161; margin-right: 0.25rem;"></span>
                                    Hidden
                                </button>
                            @endif
                        </form>
                        <a href="{{ route('admin.homepage.sections.edit', $section) }}" class="btn btn-primary" style="font-size: 12px; padding: 0.25rem 0.5rem;">Edit</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
