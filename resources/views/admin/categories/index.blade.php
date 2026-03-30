<x-layouts.admin>
    <x-slot name="title">Collections</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Collections</h1>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary" style="font-size: 13px;">
                <svg style="width: 1rem; height: 1rem; margin-right: 0.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Create collection
            </a>
        </div>
    </x-slot>

    {{-- Collections card --}}
    <div class="card">
        {{-- Tab filters --}}
        <div style="border-bottom: 1px solid #e3e3e3; display: flex; align-items: center;">
            <a href="{{ route('admin.categories.index', request()->except('status', 'parent', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') && !request('parent') ? '#303030' : 'transparent' }}; color: {{ !request('status') && !request('parent') ? '#303030' : '#616161' }}; margin-bottom: -1px;">All</a>
            <a href="{{ route('admin.categories.index', ['status' => 'active'] + request()->except('status', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'active' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'active' ? '#303030' : '#616161' }}; margin-bottom: -1px;">Active</a>
            <a href="{{ route('admin.categories.index', ['parent' => 'root'] + request()->except('parent', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('parent') === 'root' ? '#303030' : 'transparent' }}; color: {{ request('parent') === 'root' ? '#303030' : '#616161' }}; margin-bottom: -1px;">Root</a>
        </div>

        {{-- Search bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.categories.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                @if(request('parent'))<input type="hidden" name="parent" value="{{ request('parent') }}">@endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999; width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search collections"
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
                @if(request()->hasAny(['search', 'status', 'parent']))
                    <a href="{{ route('admin.categories.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding-left: 1rem;">Collection</th>
                        <th style="text-align: left;">Parent</th>
                        <th style="text-align: right;">Products</th>
                        <th style="text-align: left;">Status</th>
                        <th style="text-align: right; padding-right: 1rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr style="cursor: pointer;" onclick="if(!event.target.closest('button,a,form')) window.location='{{ route('admin.categories.edit', $category) }}'">
                            <td style="padding-left: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    @if($category->image_url)
                                        <img src="{{ asset('storage/' . $category->image_url) }}" alt=""
                                             style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; object-fit: cover; border: 1px solid #e3e3e3;">
                                    @else
                                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: #f7f7f7; border: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                                            <svg style="width: 1rem; height: 1rem; color: #c9cccf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <p style="font-size: 13px; font-weight: 500; color: #303030;">{{ $category->name }}</p>
                                        @if($category->children_count ?? false)
                                            <p style="font-size: 12px; color: #616161;">{{ $category->children_count }} subcollections</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($category->parent)
                                    <span style="font-size: 13px; color: #616161;">{{ $category->parent->name }}</span>
                                @else
                                    <span style="font-size: 13px; color: #999;">—</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <span style="font-size: 13px; color: #616161;">{{ $category->products_count }}</span>
                            </td>
                            <td>
                                @if($category->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-neutral">Draft</span>
                                @endif
                            </td>
                            <td style="text-align: right; padding-right: 1rem;" onclick="event.stopPropagation()">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem;">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn-icon" title="Edit">
                                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                                          onsubmit="return confirm('Delete {{ $category->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon" style="color: #d72c0d;" title="Delete">
                                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 1.25rem; height: 1.25rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/>
                                        </svg>
                                    </div>
                                    <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No collections found</h3>
                                    <p style="font-size: 13px; color: #616161; margin-bottom: 1rem;">Get started by creating your first collection.</p>
                                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Create collection</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
