<x-layouts.app>
    <x-slot name="title">Write a Review</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col lg:flex-row gap-8">
                @include('account.partials.sidebar')

                <div class="flex-1 max-w-2xl">
                    {{-- Breadcrumb --}}
                    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-5">
                        <a href="{{ route('account.reviews') }}" class="hover:text-[#6F9CA2] transition-colors">My Reviews</a>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span class="text-neutral-900 font-medium">Write a Review</span>
                    </div>

                    {{-- Already reviewed --}}
                    @if($existingReview)
                        <div class="bg-white rounded-xl border border-neutral-200 p-8 text-center">
                            <div class="w-14 h-14 mx-auto bg-amber-50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <h3 class="text-base font-semibold text-neutral-900 mb-1">Already reviewed</h3>
                            <p class="text-sm text-neutral-600 mb-4">You have already submitted a review for this product.</p>
                            <a href="{{ route('account.reviews') }}" class="inline-flex items-center gap-2 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">
                                View My Reviews
                            </a>
                        </div>
                    @else
                        {{-- Product Info --}}
                        <div class="bg-white rounded-xl border border-neutral-200 p-4 mb-4">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-lg overflow-hidden bg-neutral-100 shrink-0">
                                    <img src="{{ $product->primary_image_url ?? '' }}" alt="{{ $product->name }}"
                                         class="w-full h-full object-cover">
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('product.show', $product) }}" class="text-sm font-semibold text-neutral-900 hover:text-[#6F9CA2] transition-colors line-clamp-1">
                                        {{ $product->name }}
                                    </a>
                                    @if($product->brand)
                                        <p class="text-xs text-neutral-600">{{ $product->brand->name }}</p>
                                    @endif
                                    @if(!$hasPurchased)
                                        <p class="text-xs text-amber-600 mt-1 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                                            You haven't purchased this product yet
                                        </p>
                                    @else
                                        <p class="text-xs text-emerald-600 mt-1 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Verified purchase
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if(session('error'))
                            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                                {{ session('error') }}
                            </div>
                        @endif

                        {{-- Review Form --}}
                        <form action="{{ route('account.reviews.store', $product) }}" method="POST"
                              x-data="{ rating: {{ old('rating', 0) }}, hoveredRating: 0 }"
                              class="space-y-4">
                            @csrf

                            {{-- Rating --}}
                            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                                <div class="px-5 py-3 border-b border-neutral-100">
                                    <h2 class="text-sm font-bold text-neutral-900">Your Rating <span class="text-red-500">*</span></h2>
                                </div>
                                <div class="px-5 py-5">
                                    <input type="hidden" name="rating" :value="rating">
                                    <div class="flex items-center gap-1.5">
                                        @for($i = 1; $i <= 5; $i++)
                                            <button type="button"
                                                    @click="rating = {{ $i }}"
                                                    @mouseenter="hoveredRating = {{ $i }}"
                                                    @mouseleave="hoveredRating = 0"
                                                    class="focus:outline-none transition-transform hover:scale-110">
                                                <svg class="w-8 h-8 transition-colors"
                                                     :class="(hoveredRating || rating) >= {{ $i }} ? 'text-amber-400' : 'text-neutral-200'"
                                                     fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            </button>
                                        @endfor
                                        <span class="text-sm text-neutral-600 ml-2" x-show="rating > 0" x-cloak>
                                            <span x-text="['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'][rating]"></span>
                                        </span>
                                    </div>
                                    @error('rating')
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Review Content --}}
                            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                                <div class="px-5 py-3 border-b border-neutral-100">
                                    <h2 class="text-sm font-bold text-neutral-900">Your Review</h2>
                                </div>
                                <div class="p-5 space-y-4">
                                    {{-- Title --}}
                                    <div>
                                        <label for="title" class="block text-xs font-medium text-neutral-600 mb-1">Review Title</label>
                                        <input type="text" name="title" id="title" value="{{ old('title') }}"
                                               placeholder="Summarize your experience"
                                               class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2.5 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50">
                                        @error('title')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Content --}}
                                    <div>
                                        <label for="content" class="block text-xs font-medium text-neutral-600 mb-1">Detailed Review <span class="text-red-500">*</span></label>
                                        <textarea name="content" id="content" rows="4"
                                                  placeholder="What did you like or dislike? How was the quality? Would you recommend it?"
                                                  class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2.5 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50 resize-none">{{ old('content') }}</textarea>
                                        @error('content')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Pros & Cons --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="pros" class="block text-xs font-medium text-neutral-600 mb-1 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                Pros <span class="text-neutral-600">(one per line)</span>
                                            </label>
                                            <textarea name="pros" id="pros" rows="3"
                                                      placeholder="Soft fabric&#10;True to size&#10;Great quality"
                                                      class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2.5 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50 resize-none">{{ old('pros') }}</textarea>
                                        </div>
                                        <div>
                                            <label for="cons" class="block text-xs font-medium text-neutral-600 mb-1 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                                Cons <span class="text-neutral-600">(one per line)</span>
                                            </label>
                                            <textarea name="cons" id="cons" rows="3"
                                                      placeholder="Packaging could be better&#10;Slightly expensive"
                                                      class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2.5 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50 resize-none">{{ old('cons') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Submit --}}
                            <div class="flex items-center gap-3 pt-2">
                                <button type="submit"
                                        :disabled="rating === 0"
                                        :class="rating === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-[#E07E0A]'"
                                        class="inline-flex items-center gap-2 bg-[#F8931D] text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Submit Review
                                </button>
                                <a href="{{ route('account.reviews') }}" class="text-sm font-medium text-neutral-600 hover:text-neutral-700 transition-colors px-4 py-2.5">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
