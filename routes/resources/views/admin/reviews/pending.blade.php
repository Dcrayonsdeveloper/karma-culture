<x-layouts.admin>
    <x-slot name="title">Pending Reviews</x-slot>

    <a href="{{ route('admin.reviews.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none; margin-bottom: 0.5rem;">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Reviews
    </a>

    <x-slot name="header">
        <div class="page-header">
            <h1>Pending Reviews</h1>
            <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary" style="font-size: 13px;">All Reviews</a>
        </div>
    </x-slot>

    {{-- Reviews card --}}
    <div class="card" style="overflow: hidden;">
        @if($reviews->total() > 0)
            <div style="padding: 0.625rem 1rem; border-bottom: 1px solid #e3e3e3; font-size: 13px; color: #616161;">
                {{ $reviews->links('vendor.pagination.info-bar') }}
            </div>
        @endif

        @forelse($reviews as $review)
            <div style="padding: 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
                {{-- Review content --}}
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.375rem;">
                        <div style="display: flex; align-items: center; gap: 2px;">
                            @for($i = 1; $i <= 5; $i++)
                                <svg width="14" height="14" viewBox="0 0 20 20" fill="{{ $i <= $review->rating ? '#b98900' : '#e3e3e3' }}">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        @if($review->is_verified_purchase)
                            <span class="badge badge-success">Verified Purchase</span>
                        @endif
                    </div>
                    @if($review->title)
                        <p style="font-weight: 600; font-size: 13px; color: #303030; margin: 0 0 0.25rem 0;">{{ $review->title }}</p>
                    @endif
                    @if($review->content)
                        <p style="font-size: 13px; color: #616161; margin: 0 0 0.375rem 0;">{{ $review->content }}</p>
                    @endif
                    <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 12px; color: #616161;">
                        <span>Product: <span style="font-weight: 500; color: #303030;">{{ $review->product->name ?? 'Deleted' }}</span></span>
                        <span>By: <span style="font-weight: 500; color: #303030;">{{ $review->user ? $review->user->first_name . ' ' . $review->user->last_name : 'Guest' }}</span></span>
                        <span>{{ $review->created_at->format('M d, Y H:i') }}</span>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div style="display: flex; align-items: center; gap: 0.375rem; flex-shrink: 0;">
                    <form action="{{ route('admin.reviews.approve', $review) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="font-size: 12px; padding: 0.25rem 0.5rem;">Approve</button>
                    </form>
                    <form action="{{ route('admin.reviews.reject', $review) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 0.25rem 0.5rem; color: #d72c0d;">Reject</button>
                    </form>
                    <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Delete this review?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="background: none; border: none; cursor: pointer; font-size: 12px; font-weight: 500; color: #d72c0d; padding: 0.25rem 0.375rem;">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div style="padding: 4rem 1rem; text-align: center;">
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                        <svg width="24" height="24" fill="none" stroke="#616161" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p style="font-size: 14px; font-weight: 600; color: #303030; margin: 0 0 0.25rem 0;">No pending reviews</p>
                    <p style="font-size: 13px; color: #616161; margin: 0;">All caught up! There are no reviews awaiting moderation.</p>
                </div>
            </div>
        @endforelse

        @if($reviews->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
