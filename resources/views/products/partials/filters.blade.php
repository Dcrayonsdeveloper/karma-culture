<form action="{{ route('shop') }}" method="GET" class="space-y-4">
    {{-- Preserve sort --}}
    @if(request('sort'))
        <input type="hidden" name="sort" value="{{ request('sort') }}">
    @endif

    {{-- Categories --}}
    @if($categories->count())
        <div x-data="{ open: true }">
            <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-sm font-semibold text-neutral-900">
                Categories
                <svg class="w-4 h-4 text-neutral-600 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="space-y-1.5 max-h-52 overflow-y-auto pt-1 pb-2">
                    @foreach($categories as $category)
                        <label class="flex items-center gap-2.5 cursor-pointer group py-0.5">
                            <input type="radio" name="category" value="{{ $category->slug }}"
                                   {{ request('category') === $category->slug ? 'checked' : '' }}
                                   class="w-3.5 h-3.5 border-neutral-300 text-[#6F9CA2] focus:ring-[#6F9CA2] focus:ring-offset-0"
                                   onchange="this.form.submit()">
                            <span class="text-sm text-neutral-600 group-hover:text-neutral-900 transition-colors">{{ $category->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="border-t border-neutral-100"></div>
    @endif

    {{-- Sub-categories --}}
    @if($subcategories->count())
        <div x-data="{ open: true }">
            <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-sm font-semibold text-neutral-900">
                Sub-categories
                <svg class="w-4 h-4 text-neutral-600 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open" x-collapse>
                <div class="space-y-1.5 max-h-52 overflow-y-auto pt-1 pb-2">
                    @foreach($subcategories as $sub)
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

    {{-- Size --}}
    @php
        $kkAllSizes = \Illuminate\Support\Facades\Cache::remember('kk_filter_sizes_v2_' . \App\Models\ProductVariant::filterCacheVersion(), 600, function () {
            return \App\Models\ProductVariant::where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->pluck('name')
                ->map(fn ($n) => \App\Models\ProductVariant::sizeLabel($n))
                ->filter()
                ->unique()
                ->sortBy(fn ($s) => \App\Models\ProductVariant::sizeRank($s))
                ->values();
        });
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
                            <span class="inline-block px-2.5 py-1 text-xs rounded-md border transition-colors
                                         border-neutral-200 text-neutral-700 hover:border-neutral-500 hover:text-neutral-900
                                         peer-checked:border-neutral-900 peer-checked:bg-neutral-900 peer-checked:text-white">
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
        $kkAllColours = \Illuminate\Support\Facades\Cache::remember('kk_filter_colours_' . \App\Models\ProductVariant::filterCacheVersion(), 600, function () {
            return \App\Models\Product::where('is_active', true)
                ->pluck('attributes')
                ->flatMap(fn ($a) => collect(data_get($a, 'Colours', []))
                    ->map(fn ($c) => is_array($c)
                        ? ['name' => trim((string) ($c['name'] ?? '')), 'hex' => $c['hex'] ?? null]
                        : ['name' => trim((string) $c), 'hex' => null]))
                ->filter(fn ($c) => $c['name'] !== '')
                ->unique('name')
                ->sortBy('name')
                ->values();
        });
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
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs rounded-md border transition-colors
                                         border-neutral-200 hover:border-neutral-500
                                         peer-checked:border-neutral-900 peer-checked:bg-neutral-100 peer-checked:font-semibold">
                                <span style="width:12px;height:12px;border-radius:50%;background-color: {{ $kkC['hex'] ?: '#ddd' }}; border:1px solid rgba(0,0,0,.15);"></span>
                                <span class="text-neutral-700">{{ $kkC['name'] }}</span>
                            </span>
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

    {{-- Rating --}}
    <div x-data="{ open: true }">
        <button type="button" @click="open = !open" class="flex items-center justify-between w-full py-2 text-sm font-semibold text-neutral-900">
            Rating
            <svg class="w-4 h-4 text-neutral-600 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse>
            <div class="space-y-1.5 pt-1 pb-2">
                @for($i = 4; $i >= 1; $i--)
                    <label class="flex items-center gap-2.5 cursor-pointer group py-0.5">
                        <input type="radio" name="rating" value="{{ $i }}"
                               {{ request('rating') == $i ? 'checked' : '' }}
                               class="w-3.5 h-3.5 border-neutral-300 text-[#6F9CA2] focus:ring-[#6F9CA2] focus:ring-offset-0">
                        <span class="flex items-center gap-0.5">
                            @for($j = 1; $j <= 5; $j++)
                                <svg class="w-3.5 h-3.5 {{ $j <= $i ? 'text-amber-400' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                            <span class="text-xs text-neutral-600 ml-0.5">& up</span>
                        </span>
                    </label>
                @endfor
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
        <a href="{{ route('home') }}" class="flex-1 py-2.5 text-center text-sm font-medium text-neutral-600 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">
            Reset
        </a>
    </div>
</form>
