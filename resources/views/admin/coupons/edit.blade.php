<x-layouts.admin>
    <x-slot name="title">Edit Coupon</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <a href="{{ route('admin.coupons.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $coupon->code }}</h1>
            @if($coupon->isValid())
                <span class="badge badge-success">Active</span>
            @elseif($coupon->expires_at?->isPast())
                <span class="badge badge-error">Expired</span>
            @else
                <span class="badge badge-neutral">Inactive</span>
            @endif
        </div>
    </div>

    <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                {{-- Coupon Details --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Coupon Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="code" class="form-label">Code <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="code" id="code" value="{{ old('code', $coupon->code) }}" required
                                       class="form-input" style="font-family: monospace; text-transform: uppercase;">
                                @error('code')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="name" class="form-label">Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name', $coupon->name) }}" required
                                       class="form-input">
                                @error('name')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="2" class="form-textarea">{{ old('description', $coupon->description) }}</textarea>
                            @error('description')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{ couponType: '{{ old('type', $coupon->type) }}' }">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label for="type" class="form-label">Type <span style="color: #d72c0d;">*</span></label>
                                    <select name="type" id="type" class="form-select" required x-model="couponType">
                                        <option value="percentage">Percentage (%)</option>
                                        <option value="fixed">Fixed Amount</option>
                                        <option value="free_shipping">Free Shipping</option>
                                        <option value="buy_x_get_y">Buy X Get Y</option>
                                    </select>
                                    @error('type')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="value" class="form-label">
                                        <span x-show="couponType !== 'buy_x_get_y'">Value</span>
                                        <span x-show="couponType === 'buy_x_get_y'" x-cloak>Discount % on free items</span>
                                        <span style="color: #d72c0d;">*</span>
                                    </label>
                                    <input type="number" name="value" id="value" value="{{ old('value', $coupon->value) }}" step="0.01" min="0" required
                                           class="form-input"
                                           :placeholder="couponType === 'buy_x_get_y' ? 'e.g. 100 for free' : 'e.g. 20'">
                                    <p x-show="couponType === 'buy_x_get_y'" x-cloak style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Enter 100 for completely free, 50 for half price, etc.</p>
                                    @error('value')
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Buy X Get Y Configuration --}}
                            <div x-show="couponType === 'buy_x_get_y'" x-cloak style="margin-top: 1rem;">
                                <div style="padding: 1rem; background: #f6f6f7; border: 1px solid #e3e3e3; border-radius: 0.75rem; display: flex; flex-direction: column; gap: 1rem;">
                                    <h3 style="font-size: 13px; font-weight: 600; color: #303030;">Buy X Get Y Configuration</h3>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <div>
                                            <label for="conditions_buy_qty" class="form-label">Buy Quantity <span style="color: #d72c0d;">*</span></label>
                                            <input type="number" name="conditions[buy_qty]" id="conditions_buy_qty"
                                                   value="{{ old('conditions.buy_qty', $coupon->conditions['buy_qty'] ?? '') }}" min="1" step="1"
                                                   class="form-input" placeholder="e.g. 2">
                                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Customer must buy this many items</p>
                                            @error('conditions.buy_qty')
                                                <p class="form-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="conditions_get_qty" class="form-label">Get Quantity <span style="color: #d72c0d;">*</span></label>
                                            <input type="number" name="conditions[get_qty]" id="conditions_get_qty"
                                                   value="{{ old('conditions.get_qty', $coupon->conditions['get_qty'] ?? '') }}" min="1" step="1"
                                                   class="form-input" placeholder="e.g. 1">
                                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Number of items discounted</p>
                                            @error('conditions.get_qty')
                                                <p class="form-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    <p style="font-size: 12px; color: #005bd3;">
                                        Example: Buy <span x-text="$el.closest('[x-data]').querySelector('#conditions_buy_qty')?.value || '2'"></span>,
                                        Get <span x-text="$el.closest('[x-data]').querySelector('#conditions_get_qty')?.value || '1'"></span>
                                        at <span x-text="($el.closest('[x-data]').querySelector('#value')?.value || '100') + '% off'"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Limits --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Limits</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="max_discount" class="form-label">Max Discount ({{ currency_symbol() }})</label>
                                <input type="number" name="max_discount" id="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" step="0.01" min="0"
                                       class="form-input" placeholder="No limit">
                                @error('max_discount')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="min_order_amount" class="form-label">Min Order Amount ({{ currency_symbol() }})</label>
                                <input type="number" name="min_order_amount" id="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount) }}" step="0.01" min="0"
                                       class="form-input" placeholder="No minimum">
                                @error('min_order_amount')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="usage_limit" class="form-label">Total Usage Limit</label>
                                <input type="number" name="usage_limit" id="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" min="1"
                                       class="form-input" placeholder="Unlimited">
                                @error('usage_limit')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="usage_per_user" class="form-label">Usage Per User</label>
                                <input type="number" name="usage_per_user" id="usage_per_user" value="{{ old('usage_per_user', $coupon->usage_per_user) }}" min="1"
                                       class="form-input" placeholder="Unlimited">
                                @error('usage_per_user')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Applicable Products --}}
                @php
                    $selectedProductIds = old('applicable_products', $coupon->applicable_products ?? []);
                    $selectedProductNames = [];
                    if (!empty($selectedProductIds)) {
                        $selectedProductNames = \App\Models\Product::whereIn('id', $selectedProductIds)
                            ->pluck('name', 'id')->toArray();
                    }
                @endphp
                <div class="card" x-data="productSearch" data-search-url="{{ route('admin.search.products') }}"
                     data-selected="{{ json_encode(array_values($selectedProductIds ?: []), JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                     data-selected-names="{{ json_encode((object) $selectedProductNames, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
                     style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Applicable Products</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <p style="font-size: 12px; color: #616161;">Leave empty to apply to all products.</p>

                        {{-- Select2-style container --}}
                        <div style="position: relative;" @click.outside="showDropdown = false; focused = false">
                            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.375rem; min-height: 2.625rem; max-height: 8rem; overflow-y: auto; width: 100%; border-radius: 0.5rem; border: 1px solid #c9cccf; background: white; padding: 0.375rem 0.625rem; cursor: text;"
                                 :style="focused ? 'border-color: #303030' : ''"
                                 @click="$refs.searchInput.focus()">
                                {{-- Selected tags --}}
                                <template x-for="id in selected" :key="id">
                                    <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: #f6f6f7; color: #303030; font-size: 12px; font-weight: 500; padding-left: 0.5rem; padding-right: 0.25rem; padding-top: 0.125rem; padding-bottom: 0.125rem; border-radius: 0.25rem;">
                                        <span x-text="getName(id)" style="max-width: 9.375rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                                        <button type="button" @click.stop="remove(id)" style="color: #616161; border-radius: 0.25rem; padding: 0.125rem; cursor: pointer; border: none; background: none;">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                        <input type="hidden" name="applicable_products[]" :value="id">
                                    </span>
                                </template>
                                {{-- Inline search input --}}
                                <input type="text" x-ref="searchInput" x-model="search"
                                       @input="onSearch()"
                                       @focus="focused = true; if (results.length > 0) showDropdown = true"
                                       @blur="focused = false"
                                       @keydown.backspace="if (search === '' && selected.length > 0) remove(selected[selected.length - 1])"
                                       placeholder=""
                                       :placeholder="selected.length === 0 ? 'Search and select products...' : ''"
                                       style="flex: 1; min-width: 7.5rem; border: 0; background: transparent; padding: 0; font-size: 13px; color: #303030; outline: none;"
                                       autocomplete="off">
                                {{-- Loading / search indicator --}}
                                <div style="flex-shrink: 0; margin-left: auto; padding-left: 0.25rem;">
                                    <template x-if="loading">
                                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" style="animation: spin 1s linear infinite; color: #616161;">
                                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </template>
                                    <template x-if="!loading">
                                        <svg width="16" height="16" fill="none" stroke="#616161" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </template>
                                </div>
                            </div>

                            {{-- Dropdown results --}}
                            <div x-show="showDropdown" x-cloak x-transition.opacity.duration.150ms
                                 style="position: absolute; z-index: 50; margin-top: 0.25rem; width: 100%; background: white; border: 1px solid #e3e3e3; border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-height: 13rem; overflow-y: auto;">
                                <template x-if="!loading && results.length === 0 && search.length >= 2">
                                    <div style="padding: 0.625rem 0.75rem; font-size: 13px; color: #616161; display: flex; align-items: center; gap: 0.5rem;">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                        </svg>
                                        No products found for "<span x-text="search" style="font-weight: 500; color: #303030;"></span>"
                                    </div>
                                </template>
                                <template x-for="(product, index) in results" :key="product.id">
                                    <button type="button" @click="add(product)"
                                            style="width: 100%; text-align: left; padding: 0.5rem 0.75rem; font-size: 13px; color: #303030; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; border: none; background: white;"
                                            :style="index < results.length - 1 ? 'border-bottom: 1px solid #f6f6f7' : ''"
                                            onmouseover="this.style.background='#f6f6f7'" onmouseout="this.style.background='white'">
                                        <svg width="16" height="16" fill="none" stroke="#c9cccf" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <span x-text="product.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        @error('applicable_products')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Applicable Categories --}}
                @php
                    $selectedCategories = old('applicable_categories', $coupon->applicable_categories ?? []);
                @endphp
                <div class="card" x-data="{
                    selected: @json($selectedCategories ?: []),
                    categories: @json($categories)
                }" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Applicable Categories</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <p style="font-size: 12px; color: #616161;">Leave empty to apply to all categories.</p>

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; max-height: 12rem; overflow-y: auto;">
                            <template x-for="cat in categories" :key="cat.id">
                                <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 13px; cursor: pointer;">
                                    <input type="checkbox" name="applicable_categories[]"
                                           :value="cat.id"
                                           :checked="selected.includes(cat.id)"
                                           style="width: 1rem; height: 1rem; accent-color: #303030;">
                                    <span x-text="cat.name" style="color: #303030; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                                </label>
                            </template>
                        </div>

                        @error('applicable_categories')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                {{-- Schedule --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Schedule</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label for="starts_at" class="form-label">Starts At</label>
                            <input type="datetime-local" name="starts_at" id="starts_at"
                                   value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}"
                                   class="form-input">
                            @error('starts_at')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="expires_at" class="form-label">Expires At</label>
                            <input type="datetime-local" name="expires_at" id="expires_at"
                                   value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}"
                                   class="form-input">
                            @error('expires_at')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Status & Application --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Status & Application</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                   style="width: 1rem; height: 1rem; accent-color: #303030;"
                                   @checked(old('is_active', $coupon->is_active))>
                            <div>
                                <span style="font-size: 13px; font-weight: 500; color: #303030;">Active</span>
                                <p style="font-size: 12px; color: #616161;">Coupon can be used by customers</p>
                            </div>
                        </label>

                        <div style="border-top: 1px solid #e3e3e3; padding-top: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                <input type="hidden" name="auto_apply" value="0">
                                <input type="checkbox" name="auto_apply" value="1" id="auto_apply"
                                       style="width: 1rem; height: 1rem; accent-color: #303030;"
                                       @checked(old('auto_apply', $coupon->auto_apply))>
                                <div>
                                    <span style="font-size: 13px; font-weight: 500; color: #303030;">Auto Apply</span>
                                    <p style="font-size: 12px; color: #616161;">Automatically apply when conditions match</p>
                                </div>
                            </label>
                        </div>

                        <div style="border-top: 1px solid #e3e3e3; padding-top: 0.75rem;">
                            <p style="font-size: 12px; color: #616161;">
                                <span style="font-weight: 500; color: #303030;">Manual:</span> Customer enters coupon code at checkout.
                            </p>
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                <span style="font-weight: 500; color: #303030;">Auto:</span> Applied automatically if min order amount, product, and category conditions are met.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Info --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Info</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.625rem; font-size: 13px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="color: #616161;">Times Used</span>
                            <span style="font-weight: 600; color: #303030;">{{ $coupon->times_used ?? 0 }}</span>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="color: #616161;">Created</span>
                            <span style="font-weight: 500; color: #303030;">{{ $coupon->created_at->format('M d, Y') }}</span>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="color: #616161;">Updated</span>
                            <span style="font-weight: 500; color: #303030;">{{ $coupon->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST"
                      onsubmit="return confirm('Delete this coupon?')" style="display: inline;">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete coupon</button>
                </form>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productSearch', function () {
                return {
                    search: '',
                    results: [],
                    loading: false,
                    showDropdown: false,
                    focused: false,
                    selected: [],
                    selectedNames: {},
                    debounceTimer: null,
                    searchUrl: '',
                    init() {
                        this.searchUrl = this.$el.closest('[data-search-url]').dataset.searchUrl;
                        this.selected = JSON.parse(this.$el.closest('[data-selected]').dataset.selected || '[]');
                        this.selectedNames = JSON.parse(this.$el.closest('[data-selected-names]').dataset.selectedNames || '{}');
                    },
                    async fetchProducts() {
                        if (this.search.length < 2) { this.results = []; this.showDropdown = false; return; }
                        this.loading = true;
                        this.showDropdown = true;
                        try {
                            const res = await fetch(this.searchUrl + '?q=' + encodeURIComponent(this.search));
                            const data = await res.json();
                            this.results = data.filter(p => !this.selected.includes(p.id));
                        } catch (e) { this.results = []; }
                        this.loading = false;
                        if (this.results.length === 0 && !this.loading) this.showDropdown = this.search.length >= 2;
                    },
                    onSearch() {
                        clearTimeout(this.debounceTimer);
                        this.debounceTimer = setTimeout(() => this.fetchProducts(), 300);
                    },
                    add(product) {
                        if (!this.selected.includes(product.id)) {
                            this.selected.push(product.id);
                            this.selectedNames[product.id] = product.name;
                        }
                        this.search = '';
                        this.results = [];
                        this.showDropdown = false;
                        this.$refs.searchInput.focus();
                    },
                    remove(id) {
                        this.selected = this.selected.filter(i => i !== id);
                        delete this.selectedNames[id];
                    },
                    getName(id) {
                        return this.selectedNames[id] || 'Product #' + id;
                    }
                };
            });
        });
    </script>
    @endpush
</x-layouts.admin>
