<x-layouts.admin>
    <x-slot name="title">Roles</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.settings.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Settings
        </a>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Roles & Permissions</h1>
        <a href="{{ route('admin.settings.roles.create') }}" class="btn btn-primary" style="font-size: 13px;">Add Role</a>
    </div>

    <div class="card">
        @if($roles->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3;">
                {{ $roles->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Role</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Permissions</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem; color: #303030; font-weight: 500;">{{ $role->name }}</td>
                            <td style="padding: 0.625rem 1rem; color: #616161; text-align: right;">{{ $role->permissions_count }}</td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
                                    <a href="{{ route('admin.settings.roles.edit', $role) }}" style="font-size: 13px; font-weight: 500; color: #005bd3; text-decoration: none;">Edit</a>
                                    @if($role->name !== 'super-admin')
                                        <form action="{{ route('admin.settings.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('Delete this role?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; cursor: pointer; background: none; border: none;">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="padding: 3rem 1rem; text-align: center; color: #616161; font-size: 13px;">
                                No roles configured.
                                <a href="{{ route('admin.settings.roles.create') }}" style="color: #005bd3; font-weight: 500; margin-left: 0.25rem; text-decoration: none;">Add one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($roles->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">{{ $roles->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
