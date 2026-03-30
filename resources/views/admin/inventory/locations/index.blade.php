<x-layouts.admin>
    <x-slot name="title">Inventory Locations</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Inventory Locations</h1>
            <a href="{{ route('admin.inventory.locations.create') }}" class="btn btn-primary" style="font-size: 13px;">Add Location</a>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.inventory.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Inventory
        </a>
    </div>

    <div style="margin-bottom: 0.25rem;">
        <p style="font-size: 13px; color: #616161; margin: 0;">Manage warehouse and storage locations</p>
    </div>

    <div class="card" style="margin-top: 1rem;">
        @if($locations->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3;">
                {{ $locations->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Name</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Code</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Address</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Items</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $location)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem; font-weight: 500; color: #303030;">{{ $location->name }}</td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $location->code }}</td>
                            <td style="padding: 0.625rem 1rem; color: #616161; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $location->address ?? '-' }}</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; font-weight: 500; color: #303030;">{{ $location->stocks_count }}</td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($location->is_active)
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                                @else
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">Inactive</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                                    <a href="{{ route('admin.inventory.locations.edit', $location) }}" style="color: #005bd3; font-size: 13px; font-weight: 500; text-decoration: none;">Edit</a>
                                    <form action="{{ route('admin.inventory.locations.destroy', $location) }}" method="POST" onsubmit="return confirm('Delete this location?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color: #d72c0d; font-size: 13px; font-weight: 500; background: none; border: none; cursor: pointer;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 3rem 1rem; text-align: center; color: #616161; font-size: 13px;">
                                No locations found.
                                <a href="{{ route('admin.inventory.locations.create') }}" style="color: #005bd3; font-weight: 500; text-decoration: none; margin-left: 0.25rem;">Add one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($locations->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $locations->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
