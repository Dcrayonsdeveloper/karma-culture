<x-layouts.seller>
    <x-slot name="title">Review Details</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('seller.reviews.index') }}" class="hover:text-primary-600">Reviews</a>
        <span>/</span>
        <span>Review Details</span>
    </div>

    <div class="max-w-3xl">
        <div class="card p-6 mb-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-neutral-900">{{ $review->product->name ?? 'Product' }}</h2>
                    <p class="text-sm text-neutral-600">
                        By {{ $review->user->first_name ?? '' }} {{ $review->user->last_name ?? '' }}
                        on {{ $review->created_at->format('F d, Y') }}
                    </p>
                </div>
                @if($review->is_approved)
                    <span class="badge badge-success">Approved</span>
                @else
                    <span class="badge badge-warning">Pending</span>
                @endif
            </div>

            <div class="flex items-center gap-1 mb-4">
                @for($i = 1; $i <= 5; $i++)
                    <svg class="w-5 h-5 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-neutral-300' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
                <span class="ml-2 text-sm text-neutral-600">{{ $review->rating }}/5</span>
            </div>

            @if($review->title)
                <h3 class="font-medium text-neutral-900 mb-2">{{ $review->title }}</h3>
            @endif

            @if($review->content)
                <p class="text-neutral-700 mb-4">{{ $review->content }}</p>
            @endif

            @if($review->pros && count($review->pros))
                <div class="mb-3">
                    <p class="text-sm font-medium text-success-600 mb-1">Pros:</p>
                    <ul class="list-disc list-inside text-sm text-neutral-600">
                        @foreach($review->pros as $pro)
                            <li>{{ $pro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($review->cons && count($review->cons))
                <div class="mb-3">
                    <p class="text-sm font-medium text-error-600 mb-1">Cons:</p>
                    <ul class="list-disc list-inside text-sm text-neutral-600">
                        @foreach($review->cons as $con)
                            <li>{{ $con }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center gap-4 text-sm text-neutral-600 pt-4 border-t border-neutral-200">
                @if($review->is_verified_purchase)
                    <span class="text-success-600 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Verified Purchase
                    </span>
                @endif
                <span>{{ $review->helpful_count ?? 0 }} found helpful</span>
            </div>
        </div>

        <!-- Respond Form -->
        <div class="card p-6">
            <h3 class="font-semibold text-neutral-900 mb-4">Respond to Review</h3>
            <form action="{{ route('seller.reviews.respond', $review) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <textarea name="response" rows="4" required
                              class="form-input w-full @error('response') border-error-300 @enderror"
                              placeholder="Write your response to this review...">{{ old('response') }}</textarea>
                    @error('response')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary">Submit Response</button>
            </form>
        </div>
    </div>
</x-layouts.seller>
