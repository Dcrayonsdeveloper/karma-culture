<x-layouts.admin>
    <x-slot name="title">Returns</x-slot>

    @php
        // What every link on this screen carries, built from the filters the
        // controller could actually honour rather than from the raw query
        // string. Two reasons: a value the controller threw out must not come
        // back on the next click, and a query string can hold arrays - ?status[]
        // - which a Blade echo cannot print (htmlspecialchars() throws on one)
        // and which route() would rebuild into the next link as ?status[0]=.
        $kept = array_filter([
            'status' => $filters['status'],
            'search' => $filters['search'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'per_page' => $filters['per_page'],
        ], fn ($value) => $value !== null && $value !== '');

        $keptWithoutStatus = \Illuminate\Support\Arr::except($kept, 'status');

        // Whether anything is actually narrowing the table. Off $kept, which is
        // already filtered of empties, rather than request()->hasAny(): the
        // search form submits ?search= when the box is empty, and hasAny() calls
        // a present-but-empty key a filter - so a shop with no returns yet was
        // offered a "Clear all" link and told to adjust filters it had never
        // set. per_page is a page size, not a filter.
        $kkFiltered = \Illuminate\Support\Arr::except($kept, 'per_page') !== [];
    @endphp

    <x-slot name="header">
        {{-- .page-header is already display:flex; space-between, and app.css
             collapses it on a phone by matching `> [style*="display: flex"]` -
             so the space after that colon is load-bearing. --}}
        <div class="page-header">
            <h1>Returns</h1>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                {{-- The filters currently on screen ride along, so a download is
                     always the list that was asked for. No `page` - a file has
                     no page 2 - and no `format` except on the link that means
                     it; the trait reads anything else as CSV. --}}
                <a href="{{ route('admin.returns.export', $kept) }}" class="btn btn-secondary btn-sm">Export CSV</a>
                <a href="{{ route('admin.returns.export', $kept + ['format' => 'xlsx']) }}"
                   class="btn btn-secondary btn-sm"
                   title="{{ $xlsxNotice }}">Export Excel</a>
            </div>
        </div>
    </x-slot>

    {{-- A filter the controller could not honour - a date that does not parse,
         a per_page outside the clamp, a status that is not in the enum - is
         dropped rather than thrown back at the admin, so this is what stops it
         being dropped silently. Renders nothing when there is nothing to say. --}}
    <x-admin.form-errors title="Some filters were ignored" />

    {{-- Stats row --}}
    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Total</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Requested</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ number_format($stats['requested']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Approved</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #005bd3;">{{ number_format($stats['approved']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Received</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #7c3aed;">{{ number_format($stats['received']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Completed</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['completed']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Rejected</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #d72c0d;">{{ number_format($stats['rejected']) }}</p>
        </div>
    </div>

    {{-- Returns card --}}
    <div class="card">
        {{-- Tab filters --}}
        <div style="border-bottom: 1px solid #e3e3e3; display: flex; align-items: center;">
            <a href="{{ route('admin.returns.index', $keptWithoutStatus) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !$filters['status'] ? '#303030' : 'transparent' }}; color: {{ !$filters['status'] ? '#303030' : '#616161' }}; margin-bottom: -1px;">All</a>
            @foreach(['requested', 'approved', 'received', 'completed', 'rejected'] as $st)
                <a href="{{ route('admin.returns.index', ['status' => $st] + $keptWithoutStatus) }}"
                   style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ $filters['status'] === $st ? '#303030' : 'transparent' }}; color: {{ $filters['status'] === $st ? '#303030' : '#616161' }}; margin-bottom: -1px;">{{ ucfirst($st) }}</a>
            @endforeach
        </div>

        {{-- Search bar. flex-wrap so the calendar drops to its own line on a
             narrow screen rather than squeezing the search box. --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.returns.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if($filters['status'])<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
                {{-- The window has to survive a search, the way the tab does -
                     hidden inputs, not a nested form. --}}
                @if($filters['from'])<input type="hidden" name="from" value="{{ $filters['from'] }}">@endif
                @if($filters['to'])<input type="hidden" name="to" value="{{ $filters['to'] }}">@endif
                @if($filters['per_page'])<input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">@endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999;" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    {{-- maxlength matches the max:120 rule: over it the term is
                         dropped and only the banner above says so. --}}
                    <input type="text" name="search" value="{{ $filters['search'] }}" maxlength="120"
                           placeholder="Search returns"
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>

            {{-- A SIBLING of the search form, never a child: the component
                 renders its own <form>, and a nested one silently drops the
                 inner fields in a browser. It carries status, search and
                 per_page across as hidden inputs itself, so the two forms
                 preserve each other in both directions. --}}
            <x-admin.date-range-filter :action="route('admin.returns.index')" />

            @if($kkFiltered)
                <a href="{{ route('admin.returns.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
            @endif
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding-left: 1rem;">Return</th>
                        <th style="text-align: left;">Customer</th>
                        <th style="text-align: center;">Type</th>
                        <th style="text-align: left;">Reason</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right;">Refund</th>
                        <th style="text-align: right; padding-right: 1rem;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $return)
                        <tr style="cursor: pointer;" onclick="window.location='{{ route('admin.returns.show', $return) }}'">
                            <td style="padding-left: 1rem;">
                                <span style="font-size: 13px; font-weight: 500; color: #005bd3;">{{ $return->return_number }}</span>
                                <p style="font-size: 12px; color: #616161; margin-top: 2px;">
                                    Order: <a href="{{ route('admin.orders.show', $return->order_id) }}" style="color: #616161; text-decoration: none;" onmouseover="this.style.color='#005bd3'" onmouseout="this.style.color='#616161'" onclick="event.stopPropagation()">{{ $return->order->order_number ?? 'N/A' }}</a>
                                </p>
                                <p style="font-size: 12px; color: #616161; margin-top: 1px;">{{ $return->created_at->format('M d, Y h:i A') }}</p>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.625rem;">
                                    <div style="width: 2rem; height: 2rem; background: #f1f1f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span style="font-size: 11px; font-weight: 600; color: #616161;">{{ strtoupper(substr($return->order->user->first_name ?? 'G', 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <p style="font-size: 13px; font-weight: 500; color: #303030;">{{ $return->order->user->full_name ?? 'N/A' }}</p>
                                        <p style="font-size: 12px; color: #616161;">{{ $return->order->user->email ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                @php
                                    $typeBadge = match($return->type ?? 'return') {
                                        'return' => 'badge-info',
                                        'refund' => 'badge-warning',
                                        'exchange' => 'badge-neutral',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                <span class="badge {{ $typeBadge }}">{{ ucfirst($return->type ?? 'Return') }}</span>
                            </td>
                            <td>
                                <p style="font-size: 13px; color: #616161; max-width: 12rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $return->reason ?? '-' }}</p>
                            </td>
                            <td style="text-align: center;">
                                @php
                                    $statusBadge = match($return->status) {
                                        'requested' => 'badge-warning',
                                        'approved', 'pickup_scheduled', 'picked_up' => 'badge-info',
                                        'received', 'processed' => 'badge-info',
                                        'rejected' => 'badge-error',
                                        'completed' => 'badge-success',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                <span class="badge {{ $statusBadge }}">
                                    {{ $return->status === 'processed' ? 'Refund Processed' : ucfirst(str_replace('_', ' ', $return->status)) }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                @if((float) $return->refund_amount > 0)
                                    <span style="font-size: 13px; font-weight: 600; color: #1a7a2e;">@price($return->refund_amount)</span>
                                @else
                                    <span style="font-size: 13px; color: #616161;">-</span>
                                @endif
                            </td>
                            <td style="text-align: right; padding-right: 1rem;">
                                <a href="{{ route('admin.returns.show', $return) }}" style="font-size: 13px; font-weight: 500; color: #005bd3; text-decoration: none;" onclick="event.stopPropagation()">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; position: sticky; left: 1rem; max-width: calc(100vw - 4rem);">
                                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg width="20" height="20" style="color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/>
                                        </svg>
                                    </div>
                                    <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No returns found</h3>
                                    <p style="font-size: 13px; color: #616161;">
                                        @if($kkFiltered)
                                            Try adjusting your filters to find what you're looking for.
                                        @else
                                            Return requests will appear here when customers submit them.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($returns->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                {{ $returns->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
