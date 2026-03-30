<x-layouts.admin>
    <x-slot name="title">Review Details</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.reviews.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Reviews
        </a>
    </div>
    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Review #{{ $review->id }}</h1>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        <!-- Main Content -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Review Content -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Review Content</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 2px;">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $review->rating)
                                    <svg style="width: 1rem; height: 1rem; color: #b98900;" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @else
                                    <svg style="width: 1rem; height: 1rem; color: #e3e3e3;" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endif
                            @endfor
                        </div>
                        <span style="font-size: 15px; font-weight: 600; color: #303030;">{{ $review->rating }}/5</span>
                    </div>

                    @if($review->title)
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #616161; margin-bottom: 0.25rem;">Title</label>
                            <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $review->title }}</p>
                        </div>
                    @endif

                    @if($review->content)
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #616161; margin-bottom: 0.25rem;">Content</label>
                            <p style="font-size: 13px; color: #303030; margin: 0;">{{ $review->content }}</p>
                        </div>
                    @endif

                    @if($review->pros && count($review->pros))
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #616161; margin-bottom: 0.25rem;">Pros</label>
                            <ul style="list-style: disc; padding-left: 1.25rem; font-size: 13px; color: #303030; margin: 0; display: flex; flex-direction: column; gap: 0.25rem;">
                                @foreach($review->pros as $pro)
                                    <li>{{ $pro }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($review->cons && count($review->cons))
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #616161; margin-bottom: 0.25rem;">Cons</label>
                            <ul style="list-style: disc; padding-left: 1.25rem; font-size: 13px; color: #303030; margin: 0; display: flex; flex-direction: column; gap: 0.25rem;">
                                @foreach($review->cons as $con)
                                    <li>{{ $con }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Product -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Product</h2>
                </div>
                <div style="padding: 1rem;">
                    @if($review->product)
                        <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $review->product->name }}</p>
                        <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">{{ $review->product->sku ?? 'N/A' }}</p>
                    @else
                        <p style="font-size: 13px; color: #616161; margin: 0;">Product has been deleted</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Status -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Status</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div>
                        @if($review->status === 'approved')
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Approved</span>
                        @elseif($review->status === 'rejected')
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">Rejected</span>
                        @elseif($review->status === 'flagged')
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fff3cd; color: #8a6d00;">Flagged</span>
                        @else
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #d4edfc; color: #0064a4;">Pending</span>
                        @endif
                    </div>

                    <div style="display: flex; gap: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #e3e3e3;">
                        @if($review->status !== 'approved')
                            <form action="{{ route('admin.reviews.approve', $review) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" style="font-size: 13px; padding: 0.375rem 0.75rem;">Approve</button>
                            </form>
                        @endif
                        @if($review->status !== 'rejected')
                            <form action="{{ route('admin.reviews.reject', $review) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-secondary" style="font-size: 13px; padding: 0.375rem 0.75rem;">Reject</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Info</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Customer</span>
                        <span style="font-weight: 500; color: #303030;">{{ $review->user ? $review->user->first_name . ' ' . $review->user->last_name : 'Guest' }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Verified Purchase</span>
                        <span style="font-weight: 500; color: #303030;">{{ $review->is_verified_purchase ? 'Yes' : 'No' }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Featured</span>
                        <span style="font-weight: 500; color: #303030;">{{ $review->is_featured ? 'Yes' : 'No' }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Helpful</span>
                        <span style="font-weight: 500; color: #303030;">{{ $review->helpful_count }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Unhelpful</span>
                        <span style="font-weight: 500; color: #303030;">{{ $review->unhelpful_count }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Submitted</span>
                        <span style="font-weight: 500; color: #303030;">{{ $review->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    @if($review->moderated_at)
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Moderated</span>
                            <span style="font-weight: 500; color: #303030;">{{ $review->moderated_at->format('M d, Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary" style="width: 100%; text-align: center;">Back to Reviews</a>
                    <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Delete this review?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; cursor: pointer; background: none; border: none; padding: 0.5rem 0; width: 100%; text-align: center;">Delete Review</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
