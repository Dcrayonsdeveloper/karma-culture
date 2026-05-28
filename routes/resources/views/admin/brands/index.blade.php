<x-layouts.admin>
    <x-slot name="title">Brands</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Brands</h1>
            <a href="{{ route('admin.brands.create') }}" class="btn btn-primary" style="font-size: 13px;">Add brand</a>
        </div>
    </x-slot>

    <div class="card" style="overflow: hidden;">
        {{-- Search bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.brands.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999; width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search brands" style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem;">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>
            @if(request('search'))
                <a href="{{ route('admin.brands.index') }}" style="font-size: 13px; color: #005bd3; text-decoration: none; white-space: nowrap;">Clear all</a>
            @endif
        </div>

        {{-- Table --}}
        @if($brands->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e3e3e3;">
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Brand</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Status</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Products</th>
                            <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($brands as $brand)
                            <tr onclick="window.location='{{ route('admin.brands.edit', $brand) }}'" style="cursor: pointer; border-bottom: 1px solid #e3e3e3;" onmouseover="this.style.backgroundColor='#f6f6f7'" onmouseout="this.style.backgroundColor='transparent'">
                                <td style="padding: 0.5rem 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.625rem;">
                                        @if($brand->logo_url)
                                            <img src="{{ Storage::url($brand->logo_url) }}" alt="{{ $brand->name }}" style="width: 2.5rem; height: 2.5rem; border-radius: 0.375rem; object-fit: contain; border: 1px solid #e3e3e3; padding: 2px; background: #fff;">
                                        @else
                                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #e3e3e3; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                <span style="font-size: 11px; font-weight: 600; color: #616161;">{{ strtoupper(substr($brand->name, 0, 2)) }}</span>
                                            </div>
                                        @endif
                                        <div>
                                            <span style="color: #005bd3; font-weight: 500;">{{ $brand->name }}</span>
                                            @if($brand->description)
                                                <p style="color: #616161; font-size: 12px; margin: 0; max-width: 20rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $brand->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 0.5rem 1rem;">
                                    @if($brand->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-neutral">Inactive</span>
                                    @endif
                                </td>
                                <td style="padding: 0.5rem 1rem; color: #303030;">
                                    {{ $brand->products_count }}
                                </td>
                                <td style="padding: 0.5rem 1rem;" onclick="event.stopPropagation()">
                                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem;">
                                        <a href="{{ route('admin.brands.edit', $brand) }}" class="btn-icon" title="Edit">
                                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.brands.destroy', $brand) }}" method="POST" onsubmit="return confirm('Delete &quot;{{ $brand->name }}&quot;? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Delete" style="color: #b71c1c;">
                                                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($brands->hasPages())
                <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                    {{ $brands->links() }}
                </div>
            @endif
        @else
            {{-- Empty state --}}
            <div style="padding: 3rem 1rem; text-align: center;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 50%; background: #f6f6f7; margin-bottom: 1rem;">
                    <svg style="width: 1.5rem; height: 1.5rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <p style="color: #303030; font-size: 14px; font-weight: 500; margin: 0 0 0.25rem;">No brands found</p>
                <p style="color: #616161; font-size: 13px; margin: 0 0 1rem;">
                    @if(request('search'))
                        Try changing the search term or filters.
                    @else
                        Get started by creating your first brand.
                    @endif
                </p>
                @if(!request('search'))
                    <a href="{{ route('admin.brands.create') }}" class="btn btn-primary" style="font-size: 13px;">Add brand</a>
                @endif
            </div>
        @endif
    </div>
</x-layouts.admin>
