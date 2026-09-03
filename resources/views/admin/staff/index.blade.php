<x-layouts.admin>
    <x-slot name="title">Staff</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Staff</h1>
            <a href="{{ route('admin.staff.create') }}" class="btn btn-primary" style="font-size: 13px;">Add staff</a>
        </div>
    </x-slot>

    {{-- Staff card --}}
    <div class="card">
        {{-- Search bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.staff.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999;" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search staff"
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>
            @if(request('search'))
                <a href="{{ route('admin.staff.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
            @endif
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding-left: 1rem;">Name</th>
                        <th style="text-align: left;">Email</th>
                        <th style="text-align: left;">Role</th>
                        <th style="text-align: left;">Status</th>
                        <th style="text-align: left;">Joined</th>
                        <th style="text-align: right; padding-right: 1rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $member)
                        <tr style="cursor: pointer;" onclick="window.location='{{ route('admin.staff.edit', $member) }}'">
                            <td style="padding-left: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.625rem;">
                                    <div style="width: 2rem; height: 2rem; background: #f1f1f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span style="font-size: 11px; font-weight: 600; color: #616161;">{{ strtoupper(substr($member->user->first_name ?? '', 0, 1) . substr($member->user->last_name ?? '', 0, 1)) }}</span>
                                    </div>
                                    <span style="font-size: 13px; font-weight: 500; color: #303030;">{{ $member->user->full_name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #616161;">{{ $member->user->email ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $member->role ?? 'staff')) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $member->is_active ? 'badge-success' : 'badge-error' }}">
                                    {{ $member->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #616161;">{{ $member->created_at->format('M d, Y') }}</span>
                            </td>
                            <td style="text-align: right; padding-right: 1rem;">
                                <div class="kk-row-actions" style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                                    <a href="{{ route('admin.staff.edit', $member) }}" style="font-size: 13px; font-weight: 500; color: #005bd3; text-decoration: none;" onclick="event.stopPropagation()">Edit</a>
                                    <form action="{{ route('admin.staff.destroy', $member) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this staff member?')" onclick="event.stopPropagation()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; position: sticky; left: 0; max-width: calc(100vw - 4rem);">
                                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg width="20" height="20" style="color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No staff members found</h3>
                                    <p style="font-size: 13px; color: #616161;">
                                        @if(request('search'))
                                            Try adjusting your search to find what you're looking for.
                                        @else
                                            Staff members will appear here once added.
                                            <a href="{{ route('admin.staff.create') }}" style="color: #005bd3; text-decoration: none; font-weight: 500;">Add one now</a>
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($staff->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                {{ $staff->links() }}
            </div>
        @endif
    </div>
    @push('styles')
    <style>
        /* Touch: the compact Search button and the Edit / Delete row actions
           reach a 36px target without changing the row height. */
        @media (pointer: coarse) {
            .layout-admin main .btn-sm { min-height: 2.25rem; }
            .kk-row-actions > a,
            .kk-row-actions > form > button {
                display: inline-flex;
                align-items: center;
                min-height: 2.25rem;
                padding: 0 0.375rem;
                margin: -0.5rem 0;
            }
        }
    </style>
    @endpush
</x-layouts.admin>
