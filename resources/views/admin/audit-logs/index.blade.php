<x-layouts.admin>
    <x-slot name="title">Audit Log</x-slot>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Audit Log</h1>
    </div>

    <!-- Filters -->
    <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem;">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 12px; font-weight: 500; color: #6d7175; margin-bottom: 0.25rem;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search actions, users, URLs..."
                       style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 500; color: #6d7175; margin-bottom: 0.25rem;">Action</label>
                <select name="action" style="padding: 0.5rem 2rem 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; background: #fff;">
                    <option value="">All</option>
                    <option value="created" {{ request('action') === 'created' ? 'selected' : '' }}>Created</option>
                    <option value="updated" {{ request('action') === 'updated' ? 'selected' : '' }}>Updated</option>
                    <option value="deleted" {{ request('action') === 'deleted' ? 'selected' : '' }}>Deleted</option>
                </select>
            </div>
            <button type="submit" style="padding: 0.5rem 1rem; background: #303030; color: #fff; border: none; border-radius: 0.5rem; font-size: 13px; font-weight: 500; cursor: pointer;">
                Filter
            </button>
            @if(request()->hasAny(['search', 'action', 'user_id']))
                <a href="{{ route('admin.audit-logs.index') }}" style="padding: 0.5rem 1rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; color: #6d7175; text-decoration: none;">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Log Table -->
    <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #f6f6f7; border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 500; color: #6d7175;">Time</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 500; color: #6d7175;">User</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 500; color: #6d7175;">Action</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 500; color: #6d7175;">Description</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 500; color: #6d7175;">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr style="border-bottom: 1px solid #f1f1f1;">
                            <td style="padding: 0.625rem 1rem; white-space: nowrap; color: #6d7175;">
                                {{ $log->created_at->format('M d, H:i') }}
                            </td>
                            <td style="padding: 0.625rem 1rem; white-space: nowrap;">
                                <span style="font-weight: 500; color: #303030;">{{ $log->user?->name ?? $log->user?->first_name ?? 'System' }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @php
                                    $actionColors = [
                                        'created' => 'background:#e4f5e9;color:#1a7431;',
                                        'updated' => 'background:#e8f0fe;color:#1a56db;',
                                        'deleted' => 'background:#fee4e2;color:#b42318;',
                                    ];
                                @endphp
                                <span style="display:inline-block;padding:0.125rem 0.5rem;border-radius:9999px;font-size:11px;font-weight:600;{{ $actionColors[$log->action] ?? 'background:#f1f1f1;color:#6d7175;' }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #303030; max-width: 400px;">
                                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $log->properties['description'] ?? ($log->subject_type ? class_basename($log->subject_type) . ' #' . $log->subject_id : '-') }}
                                </div>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #6d7175; font-size: 12px;">
                                {{ $log->ip_address }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 3rem; text-align: center; color: #6d7175;">
                                No audit logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
