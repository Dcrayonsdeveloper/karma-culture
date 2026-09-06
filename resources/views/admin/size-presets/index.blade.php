<x-layouts.admin>
    <x-slot name="title">Sizes</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Sizes</h1>
            <a href="{{ route('admin.size-presets.create') }}" class="btn btn-primary" style="font-size: 13px;">Add size</a>
        </div>
    </x-slot>

    <div class="card" style="overflow: hidden;">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <p style="font-size: 13px; color: #616161; margin: 0;">Reusable size labels you can tick on a product. Price, MRP, stock and SKU stay per-product; only the name and any default measurements are copied in.</p>
        </div>

        @if($presets->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e3e3e3;">
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Size</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Default measurements</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Status</th>
                            <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($presets as $preset)
                            <tr onclick="window.location='{{ route('admin.size-presets.edit', $preset) }}'" style="cursor: pointer; border-bottom: 1px solid #e3e3e3;" onmouseover="this.style.backgroundColor='#f6f6f7'" onmouseout="this.style.backgroundColor='transparent'">
                                <td style="padding: 0.5rem 1rem;"><span style="color: #005bd3; font-weight: 500;">{{ $preset->name }}</span></td>
                                <td style="padding: 0.5rem 1rem; color: #616161;">{{ $preset->measurements ?: '—' }}</td>
                                <td style="padding: 0.5rem 1rem;">
                                    @if($preset->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-neutral">Inactive</span>
                                    @endif
                                </td>
                                <td style="padding: 0.5rem 1rem;" onclick="event.stopPropagation()">
                                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem;">
                                        <a href="{{ route('admin.size-presets.edit', $preset) }}" class="btn-icon" title="Edit">
                                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        <form action="{{ route('admin.size-presets.destroy', $preset) }}" method="POST" onsubmit="return confirm('Remove &quot;{{ $preset->name }}&quot; from the library?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Delete" style="color: #b71c1c;">
                                                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($presets->hasPages())
                <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">{{ $presets->links() }}</div>
            @endif
        @else
            <div style="padding: 3rem 1rem; text-align: center;">
                <p style="color: #303030; font-size: 14px; font-weight: 500; margin: 0 0 0.25rem;">No sizes yet</p>
                <p style="color: #616161; font-size: 13px; margin: 0 0 1rem;">Add sizes here and they become tick-boxes on the product form.</p>
                <a href="{{ route('admin.size-presets.create') }}" class="btn btn-primary" style="font-size: 13px;">Add size</a>
            </div>
        @endif
    </div>
</x-layouts.admin>
