<x-layouts.seller>
    <x-slot name="title">Reviews</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Reviews</h1>
            <p class="text-neutral-600">Customer reviews for your products</p>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Rating</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Review</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($reviews as $review)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 text-sm font-medium text-neutral-900">
                                {{ $review->product->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $review->user->first_name ?? '' }} {{ $review->user->last_name ?? '' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-neutral-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600 max-w-xs truncate">
                                {{ $review->title ?? Str::limit($review->content, 50) }}
                            </td>
                            <td class="px-4 py-3">
                                @if($review->is_approved)
                                    <span class="badge badge-success">Approved</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $review->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('seller.reviews.show', $review) }}"
                                   class="text-primary-600 hover:text-primary-700 text-sm font-medium">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                                <h3 class="text-lg font-medium text-neutral-900 mb-1">No reviews yet</h3>
                                <p class="text-neutral-600">Customer reviews for your products will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reviews->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>
</x-layouts.seller>
