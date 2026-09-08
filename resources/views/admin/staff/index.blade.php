<x-layouts.admin>
    <x-slot name="title">Staff</x-slot>

    @php
        // The parameters this screen filters on, in one list, so "Clear all"
        // and the empty-state copy can never fall out of step with the controls
        // above them. filled() rather than has(): submitting the search form
        // with every box empty puts ?search= in the URL, and that is not a
        // filter to offer to clear.
        $kkFilterKeys = ['search', 'from', 'to', 'role', 'status'];
        $kkFiltered = collect($kkFilterKeys)->contains(fn ($kkKey) => request()->filled($kkKey));

        // Everything currently narrowing the table, handed to the export so the
        // download is the rows on screen. `page` goes because an export is not
        // paginated; `format` goes because the two links below set their own.
        $kkExportParams = request()->except(['page', 'format']);
    @endphp

    <x-slot name="header">
        <div class="page-header">
            <h1>Staff</h1>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <a href="{{ route('admin.staff.export', $kkExportParams) }}" class="btn btn-secondary btn-sm">Export CSV</a>
                <a href="{{ route('admin.staff.export', array_merge($kkExportParams, ['format' => 'xlsx'])) }}"
                   class="btn btn-secondary btn-sm"
                   title="{{ $xlsxRowCapNotice }}">Export Excel</a>
                <a href="{{ route('admin.staff.create') }}" class="btn btn-primary" style="font-size: 13px;">Add staff</a>
            </div>
        </div>
    </x-slot>

    {{-- The filters are validated, so a role, status, page size or window this
         screen will not accept throws and redirects back. Without this the page
         came back unchanged with the search box empty and nothing said. --}}
    <x-admin.form-errors title="That filter could not be applied" />

    {{-- Staff card --}}
    <div class="card">
        {{-- Search and filter bar. flex-wrap so the five controls drop onto a
             second line on a narrow screen rather than squeezing the search box
             to nothing. --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; flex-wrap: wrap;">
            <form action="{{ route('admin.staff.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                {{-- The calendar window is set by the sibling form below. Without
                     these two, searching inside an open date range would drop
                     the range and quietly widen the table. --}}
                @if(request()->filled('from'))
                    <input type="hidden" name="from" value="{{ request('from') }}">
                @endif
                @if(request()->filled('to'))
                    <input type="hidden" name="to" value="{{ request('to') }}">
                @endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999;" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    {{-- maxlength matches the max:120 rule, so a pasted blob is
                         caught in the box rather than by a redirect that throws
                         the term away. --}}
                    <input type="text" name="search" value="{{ request('search') }}" maxlength="120"
                           placeholder="Search name, email or employee ID"
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                <select name="role" class="form-input" aria-label="Role" style="font-size: 13px; padding: 0.375rem 0.5rem; width: auto;">
                    <option value="">All roles</option>
                    @foreach($roles as $kkRole)
                        <option value="{{ $kkRole }}" @selected(request('role') === $kkRole)>{{ ucfirst($kkRole) }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-input" aria-label="Status" style="font-size: 13px; padding: 0.375rem 0.5rem; width: auto;">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>

            {{-- A sibling of the search form, never a child: the component
                 renders its own form, and a form inside a form has its fields
                 dropped by the browser. It re-emits search, role and status as
                 hidden inputs itself, so applying a range keeps them. The dates
                 cut on created_at, which is what the Joined column prints -
                 staff.joined_at is never written and is NULL on every row. --}}
            <x-admin.date-range-filter :action="route('admin.staff.index')" />

            @if($kkFiltered)
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
                                        {{-- The same predicate the Clear all link uses. Tested on
                                             the search term alone, a date-filtered empty table
                                             told the admin to add their first staff member. --}}
                                        @if($kkFiltered)
                                            Try adjusting your filters to find what you're looking for.
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
