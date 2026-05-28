<x-layouts.admin>
    <x-slot name="title">Shipping Zones</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.settings.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Settings
        </a>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Shipping Zones</h1>
        <a href="{{ route('admin.settings.shipping-zones.create') }}" class="btn btn-primary" style="font-size: 13px;">Add Shipping Zone</a>
    </div>

    <div class="card">
        @if($zones->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3;">
                {{ $zones->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Name</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Regions</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Rates</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($zones as $zone)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem; color: #303030; font-weight: 500;">{{ $zone->name }}</td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">
                                @if($zone->regions)
                                    {{ implode(', ', array_slice($zone->regions, 0, 3)) }}{{ count($zone->regions) > 3 ? ' +' . (count($zone->regions) - 3) . ' more' : '' }}
                                @else
                                    <span style="color: #616161;">All regions</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #303030; text-align: right;">{{ $zone->rates_count }}</td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($zone->is_active)
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #e4f3e6; color: #1a7a2e;">Active</span>
                                @else
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #f0f0f0; color: #616161;">Inactive</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
                                    <a href="{{ route('admin.settings.shipping-zones.edit', $zone) }}" style="font-size: 13px; font-weight: 500; color: #005bd3; text-decoration: none;">Edit</a>
                                    <form action="{{ route('admin.settings.shipping-zones.destroy', $zone) }}" method="POST" onsubmit="return confirm('Delete this zone?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; cursor: pointer; background: none; border: none;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 3rem 1rem; text-align: center; color: #616161; font-size: 13px;">
                                No shipping zones configured.
                                <a href="{{ route('admin.settings.shipping-zones.create') }}" style="color: #005bd3; font-weight: 500; margin-left: 0.25rem; text-decoration: none;">Add one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($zones->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">{{ $zones->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
