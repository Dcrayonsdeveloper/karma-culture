<x-layouts.admin>
    <x-slot name="title">Discounts</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Discounts</h1>
            <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary" style="font-size: 13px;">Create discount</a>
        </div>
    </x-slot>

    @if(session('success'))
        <div style="margin-bottom: 1rem; padding: 0.625rem 1rem; background: #e3f4e8; border: 1px solid #b3d8c0; border-radius: 0.5rem; font-size: 13px; color: #1a7a2e; display: flex; align-items: center; gap: 0.5rem;">
            <svg style="width: 1rem; height: 1rem; flex-shrink: 0;" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Discounts list card --}}
    <div class="card">

        {{-- Tab filters --}}
        <div style="border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; gap: 0;">
            <a href="{{ route('admin.coupons.index', request()->except('status', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }}; margin-bottom: -1px;">All</a>
            <a href="{{ route('admin.coupons.index', ['status' => 'active'] + request()->except('status', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'active' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'active' ? '#303030' : '#616161' }}; margin-bottom: -1px;">Active</a>
            <a href="{{ route('admin.coupons.index', ['status' => 'expired'] + request()->except('status', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'expired' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'expired' ? '#303030' : '#616161' }}; margin-bottom: -1px;">Expired</a>
        </div>

        {{-- Search bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.coupons.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999; width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search discounts"
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.coupons.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; min-width: 700px;">
                <thead>
                    <tr>
                        <th style="text-align: left;">Code</th>
                        <th style="text-align: left;">Name</th>
                        <th style="text-align: left;">Type</th>
                        <th style="text-align: right;">Value</th>
                        <th style="text-align: right;">Used</th>
                        <th style="text-align: left;">Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                        <tr style="cursor: pointer;" onclick="if(!event.target.closest('button,a,form')) window.location='{{ route('admin.coupons.edit', $coupon) }}'">
                            <td>
                                <span style="font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, monospace; font-size: 13px; font-weight: 600; color: #303030; background: #f7f7f7; padding: 0.125rem 0.5rem; border-radius: 0.25rem; border: 1px solid #e3e3e3;">{{ $coupon->code }}</span>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #303030;">{{ $coupon->name }}</span>
                            </td>
                            <td>
                                @php
                                    $typeBadges = [
                                        'percentage' => 'badge-info',
                                        'fixed' => 'badge-success',
                                        'free_shipping' => 'badge-warning',
                                        'buy_x_get_y' => 'badge-neutral',
                                    ];
                                @endphp
                                <span class="badge {{ $typeBadges[$coupon->type] ?? 'badge-info' }}">
                                    {{ ucfirst(str_replace('_', ' ', $coupon->type)) }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                @if($coupon->type === 'percentage')
                                    <span style="font-size: 13px; font-weight: 500; color: #303030;">{{ $coupon->value }}%</span>
                                @elseif($coupon->type === 'fixed')
                                    <span style="font-size: 13px; font-weight: 500; color: #303030;">@price($coupon->value)</span>
                                @elseif($coupon->type === 'free_shipping')
                                    <span style="font-size: 13px; font-weight: 500; color: #1a7a2e;">Free</span>
                                @else
                                    <span style="font-size: 13px; color: #616161;">&mdash;</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <span style="font-size: 13px; color: #303030; font-weight: 500;">{{ $coupon->times_used ?? 0 }}</span>
                                @if($coupon->usage_limit)
                                    <span style="font-size: 13px; color: #616161;">/ {{ $coupon->usage_limit }}</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.375rem;">
                                    @if($coupon->isValid())
                                        <span class="badge badge-success">Active</span>
                                    @elseif($coupon->expires_at?->isPast())
                                        <span class="badge badge-error">Expired</span>
                                    @else
                                        <span class="badge badge-neutral">Inactive</span>
                                    @endif
                                    @if($coupon->auto_apply)
                                        <span class="badge badge-neutral">Auto</span>
                                    @endif
                                </div>
                            </td>
                            <td style="text-align: right;" onclick="event.stopPropagation()">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem;">
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn-icon" title="Edit">
                                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST"
                                          onsubmit="return confirm('Delete this discount code?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon" style="color: #d72c0d;" title="Delete">
                                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 1.25rem; height: 1.25rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                        </svg>
                                    </div>
                                    <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No discounts found</h3>
                                    <p style="font-size: 13px; color: #616161; margin-bottom: 1rem;">
                                        @if(request()->hasAny(['search', 'status']))
                                            No discounts match your current filters.
                                        @else
                                            Create your first discount code to get started.
                                        @endif
                                    </p>
                                    @if(request()->hasAny(['search', 'status']))
                                        <a href="{{ route('admin.coupons.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none;">Clear all filters</a>
                                    @else
                                        <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary" style="font-size: 13px;">Create discount</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($coupons->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                {{ $coupons->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
