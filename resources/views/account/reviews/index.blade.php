<x-layouts.app>
    <x-slot name="title">My Reviews</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <x-breadcrumb :items="[['label' => 'Account', 'url' => route('account.dashboard')], ['label' => 'My Reviews']]" />
            <div class="flex flex-col lg:flex-row gap-8 mt-4">
                @include('account.partials.sidebar')

                <div class="flex-1">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-xl font-bold text-neutral-900">My Reviews</h1>
                            <p class="text-sm text-neutral-600 mt-0.5">{{ $reviews->total() }} {{ Str::plural('review', $reviews->total()) }}</p>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ session('success') }}
                        </div>
                    @endif

                    @forelse($reviews as $review)
                        <div class="bg-white rounded-xl border border-neutral-200 mb-3 overflow-hidden">
                            <div class="p-4 sm:p-5">
                                <div class="flex gap-4">
                                    {{-- Product Image --}}
                                    <a href="{{ route('product.show', $review->product) }}" class="shrink-0">
                                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-lg overflow-hidden bg-neutral-100">
                                            <img src="{{ $review->product->primary_image_url ?? '' }}" alt="{{ $review->product->name }}"
                                                 class="w-full h-full object-cover">
                                        </div>
                                    </a>

                                    <div class="flex-1 min-w-0">
                                        {{-- Top row: product name + status --}}
                                        <div class="flex items-start justify-between gap-2 mb-1.5">
                                            <a href="{{ route('product.show', $review->product) }}" class="text-sm font-semibold text-neutral-900 hover:text-[#6F9CA2] transition-colors line-clamp-1">
                                                {{ $review->product->name }}
                                            </a>
                                            @php
                                                $statusColors = [
                                                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                    'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                    'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                                ];
                                                $color = $statusColors[$review->status] ?? 'bg-neutral-50 text-neutral-600 border-neutral-200';
                                            @endphp
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border shrink-0 {{ $color }}">
                                                {{ ucfirst($review->status) }}
                                            </span>
                                        </div>

                                        {{-- Stars + date --}}
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="flex items-center gap-0.5">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <svg class="w-3.5 h-3.5 {{ $i <= $review->rating ? 'text-amber-400' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                    </svg>
                                                @endfor
                                            </div>
                                            <span class="text-xs text-neutral-600">{{ $review->created_at->format('M d, Y') }}</span>
                                            @if($review->is_verified_purchase)
                                                <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">Verified</span>
                                            @endif
                                        </div>

                                        {{-- Title --}}
                                        @if($review->title)
                                            <h4 class="text-sm font-semibold text-neutral-800 mb-1">{{ $review->title }}</h4>
                                        @endif

                                        {{-- Content --}}
                                        <p class="text-sm text-neutral-600 line-clamp-2">{{ $review->content }}</p>

                                        {{-- Pros / Cons --}}
                                        @if($review->pros || $review->cons)
                                            <div class="flex flex-wrap gap-3 mt-2">
                                                @if(is_array($review->pros) && count($review->pros))
                                                    <div class="flex items-start gap-1">
                                                        <svg class="w-3.5 h-3.5 text-emerald-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21H4.5A1.5 1.5 0 013 19.5V12a1.5 1.5 0 011.5-1.5h1.09A2 2 0 007.382 9.2L10 3.5"/></svg>
                                                        <span class="text-xs text-neutral-600">{{ implode(', ', $review->pros) }}</span>
                                                    </div>
                                                @endif
                                                @if(is_array($review->cons) && count($review->cons))
                                                    <div class="flex items-start gap-1">
                                                        <svg class="w-3.5 h-3.5 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3H19.5A1.5 1.5 0 0121 4.5V12a1.5 1.5 0 01-1.5 1.5h-1.09a2 2 0 00-1.792 1.3L14 20.5"/></svg>
                                                        <span class="text-xs text-neutral-600">{{ implode(', ', $review->cons) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-xl border border-neutral-200 p-12 text-center">
                            <div class="w-16 h-16 mx-auto bg-neutral-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-neutral-900 mb-1">No reviews yet</h3>
                            <p class="text-sm text-neutral-600 mb-5">Share your experience with products you've purchased.</p>
                            <a href="{{ route('account.orders.index') }}" class="inline-flex items-center gap-2 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">
                                View Orders
                            </a>
                        </div>
                    @endforelse

                    @if($reviews->hasPages())
                        <div class="mt-6">
                            {{ $reviews->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
