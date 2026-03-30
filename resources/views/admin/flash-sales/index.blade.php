<x-layouts.admin>
    <x-slot name="title">Flash Sales</x-slot>

    {{-- Page Header --}}
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Flash Sales</h1>
            <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">Manage limited-time sale events</p>
        </div>
        <a href="{{ route('admin.flash-sales.create') }}" style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; background: #303030; color: #fff; border-radius: 0.5rem; font-size: 13px; font-weight: 500; text-decoration: none;">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Create flash sale
        </a>
    </div>

    {{-- Stats --}}
    @php
        $now           = now();
        $totalSales    = \App\Models\FlashSale::count();
        $liveSales     = \App\Models\FlashSale::where('starts_at', '<=', $now)->where('ends_at', '>=', $now)->count();
        $upcomingSales = \App\Models\FlashSale::where('starts_at', '>', $now)->count();
        $endedSales    = \App\Models\FlashSale::where('ends_at', '<', $now)->count();
    @endphp
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Total</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ $totalSales }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Live Now</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ $liveSales }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Upcoming</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #005bd3;">{{ $upcomingSales }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Ended</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #616161;">{{ $endedSales }}</div>
        </div>
    </div>

    {{-- Single Card: Tabs + Search + Table + Pagination --}}
    <div class="card" style="overflow: hidden;">

        {{-- Tab Filters --}}
        <div style="display: flex; border-bottom: 1px solid #e3e3e3; padding: 0 1rem;">
            <a href="{{ route('admin.flash-sales.index', request()->only('search')) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }};">
                All
            </a>
            <a href="{{ route('admin.flash-sales.index', array_merge(request()->only('search'), ['status' => 'live'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'live' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'live' ? '#303030' : '#616161' }};">
                Live
            </a>
            <a href="{{ route('admin.flash-sales.index', array_merge(request()->only('search'), ['status' => 'upcoming'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'upcoming' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'upcoming' ? '#303030' : '#616161' }};">
                Upcoming
            </a>
            <a href="{{ route('admin.flash-sales.index', array_merge(request()->only('search'), ['status' => 'ended'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'ended' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'ended' ? '#303030' : '#616161' }};">
                Ended
            </a>
        </div>

        {{-- Search --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.flash-sales.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999; width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search flash sales..."
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem;">
                </div>
            </form>
            @if(request('search'))
                <a href="{{ route('admin.flash-sales.index', request()->only('status')) }}" style="font-size: 13px; color: #005bd3; text-decoration: none;">Clear</a>
            @endif
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Name</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Products</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Schedule</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($flashSales as $sale)
                        <tr style="cursor: pointer; border-bottom: 1px solid #e3e3e3;"
                            onclick="window.location='{{ route('admin.flash-sales.edit', $sale) }}'"
                            onmouseover="this.style.backgroundColor='#f6f6f7'"
                            onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 0.5rem 1rem;">
                                <a href="{{ route('admin.flash-sales.edit', $sale) }}" style="color: #005bd3; text-decoration: none; font-weight: 500;" onclick="event.stopPropagation();">{{ $sale->name }}</a>
                                @if($sale->description)
                                    <div style="color: #616161; font-size: 12px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $sale->description }}</div>
                                @endif
                            </td>
                            <td style="padding: 0.5rem 1rem; text-align: right; color: #303030;">{{ $sale->products_count }}</td>
                            <td style="padding: 0.5rem 1rem; color: #616161;">
                                <div>{{ $sale->starts_at->format('M d, Y H:i') }}</div>
                                <div style="font-size: 12px;">to {{ $sale->ends_at->format('M d, Y H:i') }}</div>
                            </td>
                            <td style="padding: 0.5rem 1rem;">
                                @if($sale->isActive())
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Live</span>
                                @elseif($sale->isUpcoming())
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #e0f0ff; color: #005bd3;">Upcoming</span>
                                @elseif($sale->hasEnded())
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">Ended</span>
                                @else
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fff3cd; color: #8a6d00;">Inactive</span>
                                @endif
                            </td>
                            <td style="padding: 0.5rem 1rem; text-align: right;" onclick="event.stopPropagation();">
                                <div style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <a href="{{ route('admin.flash-sales.edit', $sale) }}" title="Edit"
                                       style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 0.375rem; color: #616161; text-decoration: none;"
                                       onmouseover="this.style.backgroundColor='#e3e3e3'" onmouseout="this.style.backgroundColor='transparent'">
                                        <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form action="{{ route('admin.flash-sales.destroy', $sale) }}" method="POST" onsubmit="return confirm('Delete this flash sale?')" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete"
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 0.375rem; color: #d72c0d; background: transparent; border: none; cursor: pointer;"
                                                onmouseover="this.style.backgroundColor='#ffe0db'" onmouseout="this.style.backgroundColor='transparent'">
                                            <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 3rem; height: 3rem; background: #f1f1f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 1.5rem; height: 1.5rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    </div>
                                    <p style="font-weight: 500; color: #303030; margin: 0 0 0.25rem 0;">No flash sales found</p>
                                    <p style="font-size: 13px; color: #616161; margin: 0 0 0.75rem 0;">
                                        @if(request()->hasAny(['search', 'status']))
                                            No sales match your current filters.
                                        @else
                                            You haven't created any flash sales yet.
                                        @endif
                                    </p>
                                    @if(request()->hasAny(['search', 'status']))
                                        <a href="{{ route('admin.flash-sales.index') }}" style="padding: 0.25rem 0.75rem; background: #fff; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; color: #303030; text-decoration: none;">Clear Filters</a>
                                    @else
                                        <a href="{{ route('admin.flash-sales.create') }}" style="padding: 0.25rem 0.75rem; background: #303030; border-radius: 0.5rem; font-size: 13px; color: #fff; text-decoration: none;">Create First Sale</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($flashSales->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $flashSales->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
