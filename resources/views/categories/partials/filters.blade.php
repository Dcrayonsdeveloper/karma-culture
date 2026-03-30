<form action="{{ route('category.show', $category) }}" method="GET" class="space-y-4">
    {{-- Preserve sort --}}
    @if(request('sort'))
        <input type="hidden" name="sort" value="{{ request('sort') }}">
    @endif

    {{-- Sub-categories --}}
    @if($filterSubcategories->count())
        <div x-data="{ open: true }">
            <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-sm font-semibold text-neutral-900">
                Sub-categories
                <svg class="w-4 h-4 text-neutral-600 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="space-y-1.5 max-h-52 overflow-y-auto pt-1 pb-2">
                    @foreach($filterSubcategories as $sub)
                        <label class="flex items-center gap-2.5 cursor-pointer group py-0.5">
                            <input type="checkbox" name="subcategory[]" value="{{ $sub->slug }}"
                                   {{ in_array($sub->slug, (array) request('subcategory')) ? 'checked' : '' }}
                                   class="w-3.5 h-3.5 rounded border-neutral-300 text-[#6F9CA2] focus:ring-[#6F9CA2] focus:ring-offset-0">
                            <span class="text-sm text-neutral-600 group-hover:text-neutral-900 transition-colors">{{ $sub->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="border-t border-neutral-100"></div>
    @endif

    {{-- Price Range --}}
    <div x-data="{ open: true }">
        <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-sm font-semibold text-neutral-900">
            Price Range
            <svg class="w-4 h-4 text-neutral-600 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse>
            <div class="flex items-center gap-2 pt-1 pb-2">
                <div class="relative flex-1">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-neutral-600">₹</span>
                    <input type="number" name="min_price" value="{{ request('min_price') }}"
                           placeholder="Min" class="w-full pl-6 pr-2 py-2 text-sm border border-neutral-200 rounded-lg focus:outline-none focus:border-[#6F9CA2] bg-neutral-50">
                </div>
                <span class="text-neutral-300 text-sm">—</span>
                <div class="relative flex-1">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-neutral-600">₹</span>
                    <input type="number" name="max_price" value="{{ request('max_price') }}"
                           placeholder="Max" class="w-full pl-6 pr-2 py-2 text-sm border border-neutral-200 rounded-lg focus:outline-none focus:border-[#6F9CA2] bg-neutral-50">
                </div>
            </div>
        </div>
    </div>
    <div class="border-t border-neutral-100"></div>

    {{-- Availability & Offers --}}
    <div x-data="{ open: true }">
        <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-sm font-semibold text-neutral-900">
            Availability
            <svg class="w-4 h-4 text-neutral-600 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse>
            <div class="space-y-2 pt-1 pb-2">
                <label class="flex items-center gap-2.5 cursor-pointer group py-0.5">
                    <input type="checkbox" name="in_stock" value="1"
                           {{ request('in_stock') ? 'checked' : '' }}
                           class="w-3.5 h-3.5 rounded border-neutral-300 text-[#6F9CA2] focus:ring-[#6F9CA2] focus:ring-offset-0">
                    <span class="text-sm text-neutral-600 group-hover:text-neutral-900 transition-colors">In Stock Only</span>
                </label>
                <label class="flex items-center gap-2.5 cursor-pointer group py-0.5">
                    <input type="checkbox" name="on_sale" value="1"
                           {{ request('on_sale') ? 'checked' : '' }}
                           class="w-3.5 h-3.5 rounded border-neutral-300 text-[#6F9CA2] focus:ring-[#6F9CA2] focus:ring-offset-0">
                    <span class="text-sm text-neutral-600 group-hover:text-neutral-900 transition-colors">On Sale</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex gap-2 pt-2">
        <button type="submit" class="flex-1 py-2.5 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold rounded-lg transition-colors">
            Apply
        </button>
        <a href="{{ route('category.show', $category) }}" class="flex-1 py-2.5 text-center text-sm font-medium text-neutral-600 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
            Reset
        </a>
    </div>
</form>
