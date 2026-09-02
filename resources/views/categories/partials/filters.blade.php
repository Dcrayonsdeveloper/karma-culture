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
                    @php $activeSubs = $activeSubcategorySlugs ?? (array) request('subcategory'); @endphp
                    @foreach($filterSubcategories as $sub)
                        {{-- A ticked box stays clickable even when the other filters have
                             emptied it out, or there would be no way to untick it. --}}
                        @php $kkEmpty = ($sub->products_total ?? 0) === 0 && ! in_array($sub->slug, $activeSubs); @endphp
                        <label class="flex items-center gap-2.5 py-0.5 group {{ $kkEmpty ? 'cursor-not-allowed opacity-45' : 'cursor-pointer' }}"
                               @if($kkEmpty) title="Nothing in this collection yet" @endif>
                            <input type="checkbox" name="subcategory[]" value="{{ $sub->slug }}" onchange="this.form.submit()" @disabled($kkEmpty)
                                   {{ in_array($sub->slug, $activeSubs) ? 'checked' : '' }}
                                   class="w-3.5 h-3.5 rounded border-neutral-300 text-[#6F9CA2] focus:ring-[#6F9CA2] focus:ring-offset-0">
                            <span class="text-sm text-neutral-600 group-hover:text-neutral-900 transition-colors">{{ $sub->name }}</span>
                            @isset($sub->products_total)
                                <span class="ml-auto text-xs text-neutral-400 tabular-nums">{{ $sub->products_total }}</span>
                            @endisset
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="border-t border-neutral-100"></div>
    @endif

    {{-- Size --}}
    @php
        // Built by the controller from the products currently matching, minus the size
        // filter itself, so ticking a sub-category or a colour reshapes this list on the
        // next submit. Working it out here instead meant the sidebar was pinned to the
        // whole category and kept offering sizes nothing on screen had.
        $kkAllSizes = $filterSizes ?? collect();
    @endphp
    @if($kkAllSizes->isNotEmpty())
        <div x-data="{ open: true }">
            <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-sm font-semibold text-neutral-900">
                Size
                <svg class="w-4 h-4 text-neutral-600 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="flex flex-wrap gap-1.5 pt-1 pb-2">
                    @foreach($kkAllSizes as $kkSize)
                        @php $kkOn = in_array($kkSize, (array) request('size', []), true); @endphp
                        <label class="cursor-pointer select-none">
                            <input type="checkbox" name="size[]" value="{{ $kkSize }}" @checked($kkOn)
                                   onchange="this.form.submit()" class="sr-only peer">
                            {{-- The selected chip is black, so the plain hover:text-* below would repaint
                                 its label near-black and swallow it. Tailwind v4 wraps peer-* in :where(),
                                 which zeroes its specificity, so peer-checked:text-white ties with the hover
                                 rule and loses on source order. The peer-checked:hover:* pair outranks it. --}}
                            <span class="inline-block px-2.5 py-1 text-xs rounded-md border transition-colors
                                         border-neutral-200 text-neutral-700 hover:border-neutral-500 hover:text-neutral-900
                                         peer-checked:border-neutral-900 peer-checked:bg-neutral-900 peer-checked:text-white
                                         peer-checked:hover:text-white peer-checked:hover:border-neutral-900">
                                {{ $kkSize }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="border-t border-neutral-100"></div>
    @endif

    {{-- Colour --}}
    @php
        // Same story as Size: the controller narrows this to the colours the matching
        // products actually come in.
        $kkAllColours = $filterColours ?? collect();
    @endphp
    @if($kkAllColours->isNotEmpty())
        <div x-data="{ open: true }">
            <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-sm font-semibold text-neutral-900">
                Colour
                <svg class="w-4 h-4 text-neutral-600 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="flex flex-wrap gap-1.5 pt-1 pb-2">
                    @foreach($kkAllColours as $kkC)
                        @php $kkOn = in_array($kkC['name'], (array) request('colour', []), true); @endphp
                        <label class="cursor-pointer select-none" title="{{ $kkC['name'] }}">
                            <input type="checkbox" name="colour[]" value="{{ $kkC['name'] }}" @checked($kkOn)
                                   onchange="this.form.submit()" class="sr-only peer">
                            {{-- The label inherits its colour so the selected state can
                                 invert it. Hardcoding it on the inner span left dark text
                                 on a dark chip once selected. --}}
                            {{-- Selected state is a ring, not a fill: filling the chip
                                 with black fights the swatch, which is the one thing
                                 the customer is actually reading. --}}
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs rounded-md border transition-all
                                         border-neutral-200 text-neutral-700 bg-white hover:border-neutral-500
                                         peer-checked:border-neutral-900 peer-checked:text-neutral-900 peer-checked:font-semibold
                                         peer-checked:ring-2 peer-checked:ring-neutral-900/15 peer-checked:shadow-sm
                                         peer-checked:hover:border-neutral-900">
                                <span style="width:12px;height:12px;border-radius:50%;background-color: {{ $kkC['hex'] ?: '#ddd' }}; border:1px solid rgba(0,0,0,.2);"></span>
                                <span>{{ $kkC['name'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="border-t border-neutral-100"></div>
    @endif

    {{-- Brand --}}
    @if(($filterBrands ?? collect())->isNotEmpty())
        <div x-data="{ open: true }">
            <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-sm font-semibold text-neutral-900">
                Brand
                <svg class="w-4 h-4 text-neutral-600 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="space-y-1.5 max-h-52 overflow-y-auto pt-1 pb-2">
                    @php $kkActiveBrands = array_filter((array) request('brand')); @endphp
                    @foreach($filterBrands as $kkBrand)
                        <label class="flex items-center gap-2.5 cursor-pointer group py-0.5">
                            <input type="checkbox" name="brand[]" value="{{ $kkBrand->slug }}" onchange="this.form.submit()"
                                   {{ in_array($kkBrand->slug, $kkActiveBrands, true) ? 'checked' : '' }}
                                   class="w-3.5 h-3.5 rounded border-neutral-300 text-[#6F9CA2] focus:ring-[#6F9CA2] focus:ring-offset-0">
                            <span class="text-sm text-neutral-600 group-hover:text-neutral-900 transition-colors">{{ $kkBrand->name }}</span>
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
                <span class="text-neutral-300 text-sm">-</span>
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
