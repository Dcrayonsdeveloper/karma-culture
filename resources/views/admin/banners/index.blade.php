<x-layouts.admin>
    <x-slot name="title">Banners</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Banners</h1>
            <a href="{{ route('admin.banners.create') }}" class="btn btn-primary" style="font-size: 13px;">
                <svg style="width: 16px; height: 16px; margin-right: 6px; display: inline-block; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add banner
            </a>
        </div>
    </x-slot>

    {{-- Card with search + table --}}
    <div style="background: white; border-radius: 0.75rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">

        {{-- Search --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <svg style="width: 16px; height: 16px; color: #616161; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" placeholder="Search banners..."
                   style="flex: 1; border: none; outline: none; font-size: 13px; color: #303030; background: transparent;"
                   x-data x-on:input.debounce.300ms="
                       let val = $event.target.value.toLowerCase();
                       document.querySelectorAll('tbody tr[data-searchable]').forEach(row => {
                           row.style.display = row.dataset.searchable.toLowerCase().includes(val) ? '' : 'none';
                       });
                   ">
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Banner</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Position</th>
                        <th style="padding: 0.5rem 1rem; text-align: center; font-weight: 500; color: #616161; font-size: 12px;">Priority</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Schedule</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banners as $banner)
                        <tr style="border-bottom: 1px solid #e3e3e3; cursor: pointer;"
                            data-searchable="{{ $banner->name }} {{ $banner->position }} {{ $banner->link }}"
                            onmouseover="this.style.background='#f6f6f7'"
                            onmouseout="this.style.background='transparent'"
                            onclick="window.location='{{ route('admin.banners.edit', $banner) }}'">
                            <td style="padding: 0.625rem 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    @if($banner->image_url)
                                        <img src="{{ asset('storage/' . $banner->image_url) }}" alt="{{ $banner->name }}"
                                             style="width: 80px; height: 48px; object-fit: cover; border-radius: 0.375rem; border: 1px solid #e3e3e3;">
                                    @else
                                        <div style="width: 80px; height: 48px; background: #f1f1f1; border-radius: 0.375rem; border: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                                            <svg style="width: 24px; height: 24px; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div style="font-weight: 500; color: #303030;">{{ $banner->name }}</div>
                                        @if($banner->link)
                                            <div style="font-size: 12px; color: #616161; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $banner->link }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #e0f0ff; color: #005bd3;">{{ $banner->position }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: center; font-weight: 500; color: #303030;">
                                {{ $banner->priority }}
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161; font-size: 13px;">
                                @if($banner->starts_at || $banner->ends_at)
                                    @if($banner->starts_at)
                                        <div>{{ $banner->starts_at->format('M d, Y H:i') }}</div>
                                    @endif
                                    @if($banner->ends_at)
                                        <div style="font-size: 12px; color: #616161;">to {{ $banner->ends_at->format('M d, Y H:i') }}</div>
                                    @endif
                                @else
                                    <span style="color: #616161;">Always</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($banner->isActive())
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                                @else
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fff3cd; color: #8a6d00;">Inactive</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;" onclick="event.stopPropagation()">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                                    <a href="{{ route('admin.banners.edit', $banner) }}" style="color: #005bd3; font-size: 12px; font-weight: 500; text-decoration: none;"
                                       onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Edit</a>
                                    <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" onsubmit="return confirm('Delete this banner?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color: #d72c0d; font-size: 12px; font-weight: 500; background: none; border: none; cursor: pointer; padding: 0;"
                                                onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <svg style="width: 3rem; height: 3rem; color: #c9cccf; margin-bottom: 0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p style="font-weight: 500; color: #303030; margin-bottom: 0.25rem;">No banners found</p>
                                    <p style="font-size: 13px; color: #616161;">
                                        <a href="{{ route('admin.banners.create') }}" style="color: #005bd3; text-decoration: none;"
                                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Create one now</a>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($banners->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $banners->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
