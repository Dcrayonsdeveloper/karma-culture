<x-layouts.admin>
    <x-slot name="title">Pages</x-slot>

    {{-- Session Success --}}
    @if(session('success'))
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1rem; background: #cdfee1; border: 1px solid #1a7a2e33; border-radius: 0.5rem; font-size: 13px; color: #1a7a2e;">
            <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Page Header --}}
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Pages</h1>
            <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">Manage static content pages</p>
        </div>
        <a href="{{ route('admin.pages.create') }}" style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; background: #303030; color: #fff; border-radius: 0.5rem; font-size: 13px; font-weight: 500; text-decoration: none;">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Create page
        </a>
    </div>

    {{-- Stats --}}
    @php
        $totalPages     = \App\Models\Page::count();
        $publishedPages = \App\Models\Page::where('is_published', true)->count();
        $draftPages     = $totalPages - $publishedPages;
    @endphp
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Total Pages</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ $totalPages }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Published</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ $publishedPages }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Drafts</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #8a6d00;">{{ $draftPages }}</div>
        </div>
    </div>

    {{-- Single Card: Tabs + Search + Table + Pagination --}}
    <div class="card" style="overflow: hidden;">

        {{-- Tab Filters --}}
        <div style="display: flex; border-bottom: 1px solid #e3e3e3; padding: 0 1rem;">
            <a href="{{ route('admin.pages.index', request()->only('search')) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }};">
                All
            </a>
            <a href="{{ route('admin.pages.index', array_merge(request()->only('search'), ['status' => 'published'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'published' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'published' ? '#303030' : '#616161' }};">
                Published
            </a>
            <a href="{{ route('admin.pages.index', array_merge(request()->only('search'), ['status' => 'draft'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'draft' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'draft' ? '#303030' : '#616161' }};">
                Draft
            </a>
        </div>

        {{-- Search --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.pages.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999; width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search pages..."
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem;">
                </div>
            </form>
            @if(request('search'))
                <a href="{{ route('admin.pages.index', request()->only('status')) }}" style="font-size: 13px; color: #005bd3; text-decoration: none;">Clear</a>
            @endif
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Title</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Slug</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Published</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr style="cursor: pointer; border-bottom: 1px solid #e3e3e3;"
                            onclick="window.location='{{ route('admin.pages.edit', $page) }}'"
                            onmouseover="this.style.backgroundColor='#f6f6f7'"
                            onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 0.5rem 1rem;">
                                <a href="{{ route('admin.pages.edit', $page) }}" style="color: #005bd3; text-decoration: none; font-weight: 500;" onclick="event.stopPropagation();">{{ $page->title }}</a>
                            </td>
                            <td style="padding: 0.5rem 1rem; color: #616161; font-family: monospace; font-size: 12px;">/{{ $page->slug }}</td>
                            <td style="padding: 0.5rem 1rem;">
                                @if($page->is_published)
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Published</span>
                                @else
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fff3cd; color: #8a6d00;">Draft</span>
                                @endif
                            </td>
                            <td style="padding: 0.5rem 1rem; color: #616161;">
                                @if($page->published_at)
                                    {{ $page->published_at->format('M d, Y') }}
                                @else
                                    <span style="color: #999;">&mdash;</span>
                                @endif
                            </td>
                            <td style="padding: 0.5rem 1rem; text-align: right;" onclick="event.stopPropagation();">
                                <div style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                    @if($page->is_published)
                                        <a href="{{ route('page.show', $page->slug) }}" target="_blank" title="View Page"
                                           style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 0.375rem; color: #616161; text-decoration: none;"
                                           onmouseover="this.style.backgroundColor='#e3e3e3'" onmouseout="this.style.backgroundColor='transparent'">
                                            <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.pages.edit', $page) }}" title="Edit"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 0.375rem; color: #616161; text-decoration: none;"
                                       onmouseover="this.style.backgroundColor='#e3e3e3'" onmouseout="this.style.backgroundColor='transparent'">
                                        <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" onsubmit="return confirm('Delete this page?')" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete"
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 0.375rem; color: #d72c0d; background: transparent; border: none; cursor: pointer;"
                                                onmouseover="this.style.backgroundColor='#ffe0db'" onmouseout="this.style.backgroundColor='transparent'">
                                            <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 3rem; height: 3rem; background: #f1f1f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 1.5rem; height: 1.5rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <p style="font-weight: 500; color: #303030; margin: 0 0 0.25rem 0;">No pages found</p>
                                    <p style="font-size: 13px; color: #616161; margin: 0 0 0.75rem 0;">
                                        @if(request()->hasAny(['search', 'status']))
                                            No pages match your current filters.
                                        @else
                                            You haven't created any pages yet.
                                        @endif
                                    </p>
                                    @if(request()->hasAny(['search', 'status']))
                                        <a href="{{ route('admin.pages.index') }}" style="padding: 0.25rem 0.75rem; background: #fff; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; color: #303030; text-decoration: none;">Clear Filters</a>
                                    @else
                                        <a href="{{ route('admin.pages.create') }}" style="padding: 0.25rem 0.75rem; background: #303030; border-radius: 0.5rem; font-size: 13px; color: #fff; text-decoration: none;">Create First Page</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($pages->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $pages->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
