<x-layouts.admin>
    <x-slot name="title">Edit Coupon</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <a href="{{ route('admin.coupons.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $coupon->code }}</h1>
            {{-- Same source as the index badge, so opening a coupon cannot
                 report a different status to the row you clicked. --}}
            <span class="badge {{ [
                \App\Models\Coupon::STATUS_ACTIVE    => 'badge-success',
                \App\Models\Coupon::STATUS_SCHEDULED => 'badge-info',
                \App\Models\Coupon::STATUS_EXPIRED   => 'badge-error',
                \App\Models\Coupon::STATUS_USED_UP   => 'badge-warning',
                \App\Models\Coupon::STATUS_DISABLED  => 'badge-neutral',
            ][$coupon->status()] }}">{{ $coupon->statusLabel() }}</span>
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
                                       minlength="3" maxlength="50" pattern="[A-Za-z0-9_\-]+" autocomplete="off"
                                       title="Use letters, numbers, hyphens and underscores only - no spaces."
                                       class="form-input" style="font-family: monospace; text-transform: uppercase;">
                                @error('code')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="name" class="form-label">Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name', $coupon->name) }}" required
                                       minlength="2" maxlength="255"
                                       class="form-input">
                                @error('name')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" rows="2" maxlength="1000" class="form-textarea">{{ old('description', $coupon->description) }}</textarea>
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
                                    {{-- A percentage is capped at 100 on both sides: the server refuses
                                         anything above it, so the input must too or the admin is bounced
                                         after submitting. Fixed amounts keep the money ceiling. --}}
                                    <input type="number" name="value" id="value" value="{{ old('value', $coupon->value) }}" step="0.01" min="0" required
                                           inputmode="decimal"
                                           :max="couponType === 'fixed' ? '9999999.99' : '100'"
                                           :title="couponType === 'fixed' ? 'Enter an amount, up to two decimal places.' : 'Enter a percentage between 0 and 100.'"
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
                                                   value="{{ old('conditions.buy_qty', $coupon->conditions['buy_qty'] ?? '') }}" min="1" max="100" step="1"
                                                   inputmode="numeric" title="Enter a whole number between 1 and 100."
                                                   x-bind:required="couponType === 'buy_x_get_y'"
                                                   class="form-input" placeholder="e.g. 2">
                                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Customer must buy this many items</p>
                                            @error('conditions.buy_qty')
                                                <p class="form-error">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="conditions_get_qty" class="form-label">Get Quantity <span style="color: #d72c0d;">*</span></label>
                                            <input type="number" name="conditions[get_qty]" id="conditions_get_qty"
                                                   value="{{ old('conditions.get_qty', $coupon->conditions['get_qty'] ?? '') }}" min="1" max="100" step="1"
                                                   inputmode="numeric" title="Enter a whole number between 1 and 100."
                                                   x-bind:required="couponType === 'buy_x_get_y'"
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
                                <input type="number" name="max_discount" id="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" step="0.01" min="0" max="9999999.99"
                                       inputmode="decimal" title="Enter an amount, up to two decimal places."
                                       class="form-input" placeholder="No limit">
                                @error('max_discount')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="min_order_amount" class="form-label">Min Order Amount ({{ currency_symbol() }})</label>
                                <input type="number" name="min_order_amount" id="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount) }}" step="0.01" min="0" max="9999999.99"
                                       inputmode="decimal" title="Enter an amount, up to two decimal places."
                                       class="form-input" placeholder="No minimum">
                                @error('min_order_amount')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="usage_limit" class="form-label">Total Usage Limit</label>
                                <input type="number" name="usage_limit" id="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" min="1" max="1000000" step="1"
                                       inputmode="numeric" title="Enter a whole number of 1 or more."
                                       class="form-input" placeholder="Unlimited">
                                @error('usage_limit')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="usage_per_user" class="form-label">Usage Per User</label>
                                {{-- 65535 is the width of the unsignedSmallInteger column, not a
                                     preference: past it the insert truncates or errors. --}}
                                <input type="number" name="usage_per_user" id="usage_per_user" value="{{ old('usage_per_user', $coupon->usage_per_user) }}" min="1" max="65535" step="1"
                                       inputmode="numeric" title="Enter a whole number between 1 and 65535."
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
                    $selectedProductIds = old('applicable_products', $coupon->applicable_products ?? []) ?: [];
                    $selectedProductNames = $selectedProductIds
                        ? \App\Models\Product::whereIn('id', $selectedProductIds)->pluck('name', 'id')->toArray()
                        : [];
                @endphp
                <x-admin.product-picker :selected="$selectedProductIds" :names="$selectedProductNames" />

                {{-- Applicable Categories --}}
                @php
                    // old() hands back strings after a failed submit while cat.id is an int,
                    // and includes() compares strictly - without the cast every box the
                    // admin had ticked comes back empty.
                    $selectedCategories = array_map('intval', old('applicable_categories', $coupon->applicable_categories ?? []));
                @endphp
                {{-- Js::from, not @json: @json leaves the JSON's own double quotes raw, which
                     closes this attribute early and leaves Alpine an unparseable fragment. --}}
                <div class="card" x-data="{
                    selected: {{ Js::from($selectedCategories) }},
                    categories: {{ Js::from($categories) }}
                }" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Applicable Categories</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <p style="font-size: 12px; color: #616161;">Leave empty to apply to all categories.</p>

                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(10.5rem, 1fr)); gap: 0.5rem; max-height: 12rem; overflow-y: auto;">
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
                    @php
                        // A schedule that has already begun stays selectable, so
                        // editing anything else on this form does not drag its
                        // dates forward. Only a CHANGED date has to be in the
                        // future - the rule behind V::scheduleStart() agrees.
                        $now = now()->format('Y-m-d\TH:i');
                        $startOriginal = $coupon->starts_at?->format('Y-m-d\TH:i');
                        $endOriginal = $coupon->expires_at?->format('Y-m-d\TH:i');
                        $startFloor = $startOriginal && $startOriginal < $now ? $startOriginal : $now;
                        $endFloor = $endOriginal && $endOriginal < $now ? $endOriginal : $now;
                    @endphp
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label for="starts_at" class="form-label">Starts At</label>
                            <input type="datetime-local" name="starts_at" id="starts_at"
                                   value="{{ old('starts_at', $startOriginal) }}"
                                   min="{{ $startFloor }}" data-schedule-start data-schedule-original="{{ $startOriginal }}"
                                   class="form-input">
                            @error('starts_at')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="expires_at" class="form-label">Expires At</label>
                            <input type="datetime-local" name="expires_at" id="expires_at"
                                   value="{{ old('expires_at', $endOriginal) }}"
                                   min="{{ $endFloor }}" data-schedule-end="starts_at" data-schedule-original="{{ $endOriginal }}"
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
                {{-- Submits the delete form declared after the edit form below.
                     The two must not nest: the browser hoisted the inner form's
                     _method=DELETE into the edit form, which already sent
                     _method=PUT, and PHP keeps the last value for a repeated key
                     — so clicking Save destroyed the record. --}}
                <button type="submit" form="kk-delete-coupon"
                        style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete coupon</button>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>

    <form id="kk-delete-coupon" action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST"
          onsubmit="return confirm('Delete this coupon?')">
        @csrf @method('DELETE')
    </form>
</x-layouts.admin>
