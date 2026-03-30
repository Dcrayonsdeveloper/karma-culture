<x-layouts.admin>
    <x-slot name="title">Credit Notes</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Credit Notes</h1>
        </div>
    </x-slot>

    {{-- Stats row --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Total Notes</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Active</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['active']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Total Issued</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #005bd3;">@price($stats['total_amount'])</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Outstanding</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #b98900;">@price($stats['total_remaining'])</p>
        </div>
    </div>

    {{-- Credit Notes card --}}
    <div class="card" style="overflow: hidden;">
        {{-- Tab filters --}}
        <div style="border-bottom: 1px solid #e3e3e3; display: flex; align-items: center;">
            <a href="{{ route('admin.credit-notes.index', request()->except('status', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }}; margin-bottom: -1px;">All</a>
            <a href="{{ route('admin.credit-notes.index', ['status' => 'active'] + request()->except('status', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'active' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'active' ? '#303030' : '#616161' }}; margin-bottom: -1px;">Active</a>
            <a href="{{ route('admin.credit-notes.index', ['status' => 'partially_used'] + request()->except('status', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'partially_used' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'partially_used' ? '#303030' : '#616161' }}; margin-bottom: -1px;">Partially Used</a>
            <a href="{{ route('admin.credit-notes.index', ['status' => 'fully_used'] + request()->except('status', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'fully_used' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'fully_used' ? '#303030' : '#616161' }}; margin-bottom: -1px;">Fully Used</a>
            <a href="{{ route('admin.credit-notes.index', ['status' => 'expired'] + request()->except('status', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'expired' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'expired' ? '#303030' : '#616161' }}; margin-bottom: -1px;">Expired</a>
        </div>

        {{-- Search bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.credit-notes.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999;" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Credit note #, customer name, email or order #..."
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.credit-notes.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
            @endif
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Credit Note</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Customer</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Order / Return</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Amount</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Remaining</th>
                        <th style="padding: 0.5rem 1rem; text-align: center; font-weight: 500; color: #616161; font-size: 12px;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Expires</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($creditNotes as $note)
                        <tr onclick="window.location='{{ route('admin.credit-notes.show', $note) }}'" style="cursor: pointer; border-bottom: 1px solid #e3e3e3;" onmouseover="this.style.backgroundColor='#f6f6f7'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 0.625rem 1rem;">
                                <a href="{{ route('admin.credit-notes.show', $note) }}" style="font-weight: 500; color: #005bd3; text-decoration: none;">
                                    {{ $note->credit_note_number }}
                                </a>
                                <p style="font-size: 12px; color: #616161; margin: 2px 0 0 0;">{{ $note->created_at->format('M d, Y h:i A') }}</p>
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 1.75rem; height: 1.75rem; background: #e0f0ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #e3e3e3;">
                                        <span style="font-size: 11px; font-weight: 600; color: #005bd3;">{{ strtoupper(substr($note->user->first_name ?? 'G', 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $note->user->full_name ?? 'N/A' }}</p>
                                        <p style="font-size: 12px; color: #616161; margin: 0;">{{ $note->user->email ?? '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($note->order)
                                    <a href="{{ route('admin.orders.show', $note->order) }}" style="font-size: 13px; color: #005bd3; text-decoration: none;" onclick="event.stopPropagation();">{{ $note->order->order_number }}</a>
                                @else
                                    <span style="font-size: 13px; color: #616161;">-</span>
                                @endif
                                @if($note->return)
                                    <p style="font-size: 12px; color: #616161; margin: 2px 0 0 0;">Return: <a href="{{ route('admin.returns.show', $note->return) }}" style="color: #005bd3; text-decoration: none;" onclick="event.stopPropagation();">{{ $note->return->return_number }}</a></p>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right; font-weight: 500; color: #303030;">@price($note->amount)</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; font-weight: 600; color: {{ $note->remaining_amount > 0 ? '#1a7a2e' : '#616161' }};">
                                @price($note->remaining_amount)
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: center;">
                                @php
                                    $statusClass = match($note->status) {
                                        'active' => 'badge-success',
                                        'partially_used' => 'badge-info',
                                        'fully_used' => 'badge-neutral',
                                        'expired' => 'badge-error',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst(str_replace('_', ' ', $note->status)) }}
                                </span>
                            </td>
                            <td style="padding: 0.625rem 1rem; font-size: 13px;">
                                @if($note->expires_at)
                                    <span style="color: {{ $note->expires_at->isPast() ? '#d72c0d' : '#616161' }};">
                                        {{ $note->expires_at->format('M d, Y') }}
                                    </span>
                                @else
                                    <span style="color: #616161;">Never</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <a href="{{ route('admin.credit-notes.show', $note) }}" class="btn-icon" title="View details" onclick="event.stopPropagation();">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 4rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                                        <svg width="24" height="24" fill="none" stroke="#616161" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                    <p style="font-size: 14px; font-weight: 600; color: #303030; margin: 0 0 0.25rem 0;">No credit notes found</p>
                                    <p style="font-size: 13px; color: #616161; margin: 0;">
                                        @if(request()->hasAny(['search', 'status']))
                                            Try adjusting your filters to find what you're looking for.
                                        @else
                                            Credit notes will appear here when refunds are processed.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($creditNotes->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $creditNotes->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
