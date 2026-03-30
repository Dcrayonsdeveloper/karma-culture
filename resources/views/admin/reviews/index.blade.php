<x-layouts.admin>
    <x-slot name="title">Reviews</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Reviews</h1>
        </div>
    </x-slot>

    @php
        $totalReviews = \App\Models\Review::count();
        $approvedReviews = \App\Models\Review::where('status', 'approved')->count();
        $pendingReviews = \App\Models\Review::where('status', 'pending')->count();
    @endphp

    <div class="card" style="overflow: hidden;">
        {{-- Tab filters --}}
        <div style="display: flex; align-items: center; gap: 0; border-bottom: 1px solid #e3e3e3; padding: 0 1rem;">
            <a href="{{ route('admin.reviews.index', request()->except('status')) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }};">
                All <span style="color: #616161; font-size: 12px;">({{ $totalReviews }})</span>
            </a>
            <a href="{{ route('admin.reviews.index', array_merge(request()->except('status'), ['status' => 'pending'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'pending' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'pending' ? '#303030' : '#616161' }};">
                Pending <span style="color: #616161; font-size: 12px;">({{ $pendingReviews }})</span>
            </a>
            <a href="{{ route('admin.reviews.index', array_merge(request()->except('status'), ['status' => 'approved'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'approved' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'approved' ? '#303030' : '#616161' }};">
                Approved <span style="color: #616161; font-size: 12px;">({{ $approvedReviews }})</span>
            </a>
            <a href="{{ route('admin.reviews.index', array_merge(request()->except('status'), ['status' => 'rejected'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'rejected' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'rejected' ? '#303030' : '#616161' }};">
                Rejected
            </a>
        </div>

        {{-- Search bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.reviews.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999; width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by product or customer" style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem;">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>
            @if(request('search'))
                <a href="{{ route('admin.reviews.index', request()->except('search')) }}" style="font-size: 13px; color: #005bd3; text-decoration: none; white-space: nowrap;">Clear all</a>
            @endif
        </div>

        {{-- Table --}}
        @if($reviews->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e3e3e3;">
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Product</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Customer</th>
                            <th style="padding: 0.5rem 1rem; text-align: center; font-weight: 500; color: #616161; font-size: 12px;">Rating</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Review</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Status</th>
                            <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Date</th>
                            <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reviews as $review)
                            <tr onclick="window.location='{{ route('admin.reviews.show', $review) }}'" style="cursor: pointer; border-bottom: 1px solid #e3e3e3;" onmouseover="this.style.backgroundColor='#f6f6f7'" onmouseout="this.style.backgroundColor='transparent'">
                                <td style="padding: 0.5rem 1rem; max-width: 10rem;">
                                    <span style="color: #005bd3; font-weight: 500; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $review->product->name ?? 'Deleted Product' }}</span>
                                </td>
                                <td style="padding: 0.5rem 1rem; color: #303030;">
                                    {{ $review->user ? $review->user->first_name . ' ' . $review->user->last_name : 'Guest' }}
                                </td>
                                <td style="padding: 0.5rem 1rem; text-align: center;">
                                    <div style="display: inline-flex; align-items: center; gap: 1px;">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg style="width: 0.75rem; height: 0.75rem;" fill="{{ $i <= $review->rating ? '#b98900' : '#e3e3e3' }}" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                </td>
                                <td style="padding: 0.5rem 1rem; max-width: 14rem;">
                                    @if($review->title)
                                        <span style="color: #303030; font-weight: 500; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $review->title }}</span>
                                    @endif
                                    @if($review->content)
                                        <span style="color: #616161; font-size: 12px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $review->content }}</span>
                                    @endif
                                </td>
                                <td style="padding: 0.5rem 1rem;">
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                        @if($review->status === 'approved')
                                            <span class="badge badge-success">Approved</span>
                                        @elseif($review->status === 'pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @elseif($review->status === 'rejected')
                                            <span class="badge badge-error">Rejected</span>
                                        @elseif($review->status === 'flagged')
                                            <span class="badge badge-warning">Flagged</span>
                                        @endif
                                        @if($review->is_verified_purchase)
                                            <span class="badge badge-success">Verified</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="padding: 0.5rem 1rem; color: #616161; white-space: nowrap;">
                                    {{ $review->created_at->format('M d, Y') }}
                                </td>
                                <td style="padding: 0.5rem 1rem;" onclick="event.stopPropagation()">
                                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                                        <a href="{{ route('admin.reviews.show', $review) }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none;">View</a>
                                        <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Delete this review?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="font-size: 13px; color: #b71c1c; font-weight: 500; background: none; border: none; cursor: pointer; padding: 0;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($reviews->hasPages())
                <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                    {{ $reviews->links() }}
                </div>
            @endif
        @else
            {{-- Empty state --}}
            <div style="padding: 3rem 1rem; text-align: center;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 50%; background: #f6f6f7; margin-bottom: 1rem;">
                    <svg style="width: 1.5rem; height: 1.5rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
                <p style="color: #303030; font-size: 14px; font-weight: 500; margin: 0 0 0.25rem;">No reviews found</p>
                <p style="color: #616161; font-size: 13px; margin: 0 0 1rem;">
                    @if(request()->hasAny(['search', 'status']))
                        Try changing the search term or filters.
                    @else
                        No reviews have been submitted yet.
                    @endif
                </p>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.reviews.index') }}" style="font-size: 13px; color: #005bd3; text-decoration: none; font-weight: 500;">Clear all filters</a>
                @endif
            </div>
        @endif
    </div>
</x-layouts.admin>
