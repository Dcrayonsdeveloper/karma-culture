<x-layouts.app>
    <x-slot name="title">Shopping Cart - {{ config('app.name') }}</x-slot>

    <div class="bg-neutral-50" x-data="cartPage()" x-cloak>
        <div class="container mx-auto px-4 py-4">
            <x-breadcrumb :items="[['label' => 'Shopping Cart', 'url' => null]]" />
        </div>

        <div class="container mx-auto px-4 pb-10">
            {{-- Skeleton: visible until Alpine initializes, then removed --}}
            <div x-data x-init="$el.remove()" class="animate-pulse">
                <div class="flex items-center justify-between mb-4">
                    <div class="h-6 w-32 bg-neutral-200 rounded"></div>
                    <div class="h-4 w-28 bg-neutral-200 rounded"></div>
                </div>
                <div class="flex flex-col lg:flex-row lg:items-start gap-5">
                    <div class="flex-1 min-w-0 space-y-2.5">
                        @for($i = 0; $i < 2; $i++)
                            <div class="bg-white rounded-lg border border-neutral-100 p-3 sm:p-4">
                                <div class="flex gap-3">
                                    <div class="w-[60px] h-[60px] bg-neutral-200 rounded shrink-0"></div>
                                    <div class="flex-1 space-y-2">
                                        <div class="h-3 w-16 bg-neutral-200 rounded"></div>
                                        <div class="h-4 w-3/4 bg-neutral-200 rounded"></div>
                                        <div class="h-4 w-20 bg-neutral-200 rounded"></div>
                                        <div class="flex gap-3 mt-2">
                                            <div class="h-7 w-24 bg-neutral-200 rounded"></div>
                                            <div class="h-4 w-16 bg-neutral-200 rounded"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                    <div class="lg:w-85 shrink-0">
                        <div class="bg-white rounded-lg border border-neutral-100 p-4 space-y-3">
                            <div class="h-4 w-24 bg-neutral-200 rounded"></div>
                            <div class="h-10 w-full bg-neutral-200 rounded-lg"></div>
                            <div class="border-t border-neutral-100 pt-3 space-y-2">
                                <div class="flex justify-between"><div class="h-3 w-20 bg-neutral-200 rounded"></div><div class="h-3 w-16 bg-neutral-200 rounded"></div></div>
                                <div class="flex justify-between"><div class="h-3 w-24 bg-neutral-200 rounded"></div><div class="h-3 w-16 bg-neutral-200 rounded"></div></div>
                                <div class="flex justify-between"><div class="h-3 w-16 bg-neutral-200 rounded"></div><div class="h-3 w-12 bg-neutral-200 rounded"></div></div>
                            </div>
                            <div class="border-t border-dashed border-neutral-200 pt-3">
                                <div class="flex justify-between"><div class="h-4 w-24 bg-neutral-200 rounded"></div><div class="h-4 w-20 bg-neutral-200 rounded"></div></div>
                            </div>
                            <div class="h-12 w-full bg-neutral-200 rounded-lg"></div>
                        </div>
                    </div>
                </div>
            </div>

            <template x-if="items.length > 0">
                <div>
                    <!-- Cart Header -->
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="text-lg font-bold text-neutral-900">
                            My Cart <span class="text-sm font-normal text-neutral-600" x-text="'(' + totalQty + ' ' + (totalQty === 1 ? 'item' : 'items') + ')'"></span>
                        </h1>
                        <a href="{{ route('home') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                            Continue Shopping
                        </a>
                    </div>

                    <div class="flex flex-col lg:flex-row lg:items-start gap-5">
                        <!-- Left: Cart Items -->
                        <div class="flex-1 min-w-0 space-y-2.5">
                            <template x-for="item in items" :key="item.id">
                                <div class="bg-white rounded-lg border border-neutral-100 p-3 sm:p-4">
                                    <div class="flex gap-3">
                                        <!-- Product Image -->
                                        <a :href="item.product_url" class="shrink-0 self-start block" style="height: 60px; width: 60px;">
                                            <img :src="item.image" :alt="item.name"
                                                 class="rounded border border-neutral-100 bg-neutral-50"
                                                 style="height: 60px; width: auto; max-width: 60px; object-fit: contain;">
                                        </a>

                                        <!-- Product Details + Price -->
                                        <div class="flex-1 min-w-0 flex flex-col sm:flex-row sm:gap-4">
                                            <!-- Info column -->
                                            <div class="flex-1 min-w-0">
                                                <template x-if="item.brand">
                                                    <p class="text-[10px] font-semibold text-neutral-600 uppercase tracking-wide leading-none" x-text="item.brand"></p>
                                                </template>

                                                <a :href="item.product_url"
                                                   class="text-[13px] font-medium text-neutral-800 hover:text-primary-600 line-clamp-1 leading-snug mt-0.5 block"
                                                   x-text="item.name"></a>

                                                <template x-if="item.variant_label">
                                                    <p class="text-[11px] text-neutral-600 mt-0.5" x-text="item.variant_label"></p>
                                                </template>
                                                <template x-if="item.size">
                                                    <p class="text-[11px] text-neutral-600 mt-0.5">Size: <span class="font-medium text-neutral-800" x-text="item.size"></span></p>
                                                </template>
                                                <template x-if="item.colour">
                                                    <p class="text-[11px] text-neutral-600 mt-0.5">Colour: <span class="font-medium text-neutral-800" x-text="item.colour"></span></p>
                                                </template>

                                                <!-- Price -->
                                                <div class="flex items-center gap-1.5 mt-1">
                                                    <span class="text-[13px] font-bold text-neutral-900" x-text="fp(item.price)"></span>
                                                    <template x-if="item.mrp > item.price">
                                                        <span class="flex items-center gap-1.5">
                                                            <span class="text-[11px] text-neutral-600 line-through" x-text="fp(item.mrp)"></span>
                                                            <span class="text-[11px] font-semibold text-success-600" x-text="item.discount_pct + '% off'"></span>
                                                        </span>
                                                    </template>
                                                </div>

                                                <!-- Quantity + Remove -->
                                                <div class="flex items-center gap-3 mt-2">
                                                    <div class="flex items-center border border-neutral-200 rounded overflow-hidden">
                                                        <button @click="updateQty(item, item.quantity - 1)"
                                                                class="w-9 h-9 flex items-center justify-center text-neutral-600 hover:bg-neutral-50 transition-colors"
                                                                :class="item.quantity <= 1 || item.updating ? 'opacity-30 cursor-not-allowed' : 'hover:text-neutral-900'"
                                                                :disabled="item.quantity <= 1 || item.updating"
                                                                aria-label="Decrease quantity">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                                        </button>
                                                        <span class="w-9 h-9 flex items-center justify-center text-xs font-semibold border-x border-neutral-200 bg-neutral-50/50"
                                                              x-text="item.quantity"></span>
                                                        <button @click="updateQty(item, item.quantity + 1)"
                                                                class="w-9 h-9 flex items-center justify-center text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900 transition-colors"
                                                                :class="item.quantity >= 99 || item.updating ? 'opacity-30 cursor-not-allowed' : ''"
                                                                :disabled="item.quantity >= 99 || item.updating"
                                                                aria-label="Increase quantity">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                        </button>
                                                    </div>

                                                    <button @click="removeItem(item)" class="flex items-center gap-1 text-[12px] text-neutral-600 hover:text-error-500 transition-colors py-2 px-1"
                                                            :disabled="item.updating">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        Remove
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Item Total - right aligned on desktop -->
                                            <div class="hidden sm:flex flex-col items-end justify-center shrink-0">
                                                <p class="text-[13px] font-bold text-neutral-900" x-text="fp(item.price * item.quantity)"></p>
                                                <template x-if="item.quantity > 1">
                                                    <p class="text-[10px] text-neutral-600 mt-0.5" x-text="fp(item.price) + ' x ' + item.quantity"></p>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Clear Cart -->
                            <div class="flex justify-end">
                                <button @click="clearCart()" class="text-xs text-neutral-600 hover:text-error-500 transition-colors py-2 px-1">
                                    Remove All Items
                                </button>
                            </div>
                        </div>

                        <!-- Right: Order Summary -->
                        <div class="lg:w-85 shrink-0 self-stretch">
                            <div class="bg-white rounded-lg border border-neutral-100 sticky top-20 flex flex-col">
                                <!-- Coupon Section -->
                                <div class="p-4 border-b border-neutral-100">
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        <span class="text-sm font-semibold text-neutral-800">Apply Coupon</span>
                                    </div>

                                    <template x-if="coupon">
                                        <div>
                                            <div class="flex items-center justify-between px-3 py-2.5 bg-success-50 border border-dashed border-success-300 rounded-md">
                                                <div class="flex flex-col gap-0.5">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xs font-bold text-success-700 bg-success-100 px-2 py-0.5 rounded" x-text="coupon.code"></span>
                                                        <span class="text-xs text-success-600">applied</span>
                                                        <template x-if="coupon.auto_apply">
                                                            <span class="text-[10px] text-primary-600 bg-primary-50 px-1.5 py-0.5 rounded font-medium">Auto</span>
                                                        </template>
                                                    </div>
                                                    <template x-if="couponLabel">
                                                        <p class="text-[11px] text-success-600 font-medium" x-text="couponLabel"></p>
                                                    </template>
                                                </div>
                                                <button @click="removeCoupon()" class="text-xs text-error-500 hover:text-error-600 font-medium py-1 px-2">Remove</button>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="!coupon">
                                        <div>
                                            <form @submit.prevent="applyCoupon()" class="flex items-stretch">
                                                <input type="text" x-model="couponCode" placeholder="Enter coupon code"
                                                       class="flex-1 min-w-0 text-[13px] border border-neutral-200 border-r-0 rounded-l-lg px-3 py-2.5 focus:border-primary-400 focus:outline-none uppercase placeholder:normal-case placeholder:text-neutral-600" required>
                                                <button type="submit"
                                                        class="shrink-0 text-[13px] font-bold text-white bg-primary-600 hover:bg-primary-700 rounded-r-lg px-5 py-2.5 transition-colors disabled:opacity-50"
                                                        :disabled="applyingCoupon">
                                                    <span x-show="!applyingCoupon">APPLY</span>
                                                    <span x-show="applyingCoupon" class="inline-flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                    </span>
                                                </button>
                                            </form>
                                            <template x-if="couponError">
                                                <p class="mt-1.5 text-[11px] text-error-500" x-text="couponError"></p>
                                            </template>
                                        </div>
                                    </template>
                                </div>

                                <!-- Price Details -->
                                <div class="p-4">
                                    <h3 class="text-[11px] font-bold text-neutral-600 uppercase tracking-wider mb-3"
                                        x-text="'Price Details (' + totalQty + ' ' + (totalQty === 1 ? 'item' : 'items') + ')'"></h3>

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-[13px]">
                                            <span class="text-neutral-600">Total MRP</span>
                                            <span class="text-neutral-800 font-medium" x-text="fp(totalMrp)"></span>
                                        </div>

                                        <template x-if="productDiscount > 0">
                                            <div class="flex items-center justify-between text-[13px]">
                                                <span class="text-neutral-600">Discount on MRP</span>
                                                <span class="text-success-600 font-medium" x-text="'-' + fp(productDiscount)"></span>
                                            </div>
                                        </template>

                                        <template x-if="discount > 0">
                                            <div class="flex items-center justify-between text-[13px]">
                                                <span class="text-neutral-600" x-text="couponLabel ? couponLabel : 'Coupon Discount'"></span>
                                                <span class="text-success-600 font-medium" x-text="'-' + fp(discount)"></span>
                                            </div>
                                        </template>

                                        @php $kkFreeShip = (int) \App\Models\Setting::get('free_shipping_threshold', 999); @endphp
                                        <div class="flex items-center justify-between text-[13px]">
                                            <span class="text-neutral-600">Shipping <span class="text-neutral-400">(free over ₹{{ number_format($kkFreeShip) }})</span></span>
                                            <span class="text-success-600 font-semibold">FREE</span>
                                        </div>
                                    </div>

                                    <div class="border-t border-dashed border-neutral-200 my-3"></div>

                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-bold text-neutral-900">Total Amount</span>
                                        <span class="text-sm font-bold text-neutral-900" x-text="fp(totalAmount)"></span>
                                    </div>

                                    <template x-if="totalSavings > 0">
                                        <div class="mt-3 px-3 py-2 bg-success-50 border border-success-100 rounded-md">
                                            <p class="text-xs font-semibold text-success-700 text-center"
                                               x-text="'You will save ' + fp(totalSavings) + ' on this order'"></p>
                                        </div>
                                    </template>
                                </div>

                                <!-- Checkout Button -->
                                <div class="p-4 pt-0">
                                    <a href="{{ route('checkout.index') }}"
                                       class="block w-full py-3 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold text-center rounded-lg transition-colors shadow-sm">
                                        PROCEED TO CHECKOUT
                                    </a>
                                </div>

                                <!-- Trust Badges -->
                                <div class="px-4 pb-4">
                                    <div class="flex items-center justify-center gap-4 pt-3 border-t border-neutral-100">
                                        <div class="flex items-center gap-1.5 text-neutral-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            <span class="text-[10px] font-medium">Secure</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-neutral-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            <span class="text-[10px] font-medium">100% Genuine</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 text-neutral-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            <span class="text-[10px] font-medium">Easy Returns</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="items.length === 0">
                <!-- Empty Cart -->
                <div class="flex flex-col items-center justify-center bg-white rounded-xl border border-neutral-100 py-20 px-6 mt-4">
                    <div class="w-24 h-24 mb-6 bg-[#6F9CA2]/5 rounded-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-[#6F9CA2]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-neutral-800 mb-2">Your bag is empty</h2>
                    <p class="text-sm text-neutral-600 mb-8 max-w-sm text-center leading-relaxed">There is nothing in your bag. Let's add some items.</p>
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center gap-2 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold px-10 py-3 rounded-lg transition-colors shadow-md shadow-[#F8931D]/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        Start Shopping
                    </a>
                </div>
            </template>
        </div>
    </div>

    {{-- You May Also Like (cart page) --}}
    @if(isset($recommended) && $recommended->count())
    <section class="border-t border-neutral-200 bg-white">
        <div class="container mx-auto px-4 py-10 lg:py-14">
            <h2 class="text-center" style="font-family:'Playfair Display',Georgia,serif; font-size:26px; font-weight:700; letter-spacing:0.18em; text-transform:uppercase; color:#2d1810; margin:0 0 28px;">You May Also Like</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
                @foreach($recommended as $product)
                    <x-product-card :product="$product" :show-quick-view="false" />
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @php
        $cartItems = $cart->items->filter(fn ($item) => $item->product)->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'brand' => $item->product->brand?->name,
                'image' => $item->product->primary_image_url,
                'product_url' => route('product.show', $item->product),
                'variant_label' => $item->variant ? $item->variant->attributeValues->pluck('value')->join(' / ') : null,
                'size' => $item->size,
                'colour' => $item->colour,
                'price' => (float) $item->price,
                'mrp' => (float) $item->product->mrp,
                'discount_pct' => $item->product->discount_percentage ?? 0,
                'quantity' => $item->quantity,
                'updating' => false,
            ];
        })->values();
        $cartCoupon = null;
        if ($cart->coupon) {
            $cartCoupon = [
                'code' => $cart->coupon->code,
                'type' => $cart->coupon->type,
                'value' => (float) $cart->coupon->value,
                'auto_apply' => $cart->coupon->auto_apply,
            ];
            if ($cart->coupon->type === 'buy_x_get_y' && $cart->coupon->conditions) {
                $cartCoupon['buy_qty'] = (int) ($cart->coupon->conditions['buy_qty'] ?? 0);
                $cartCoupon['get_qty'] = (int) ($cart->coupon->conditions['get_qty'] ?? 0);
            }
        }
        $cartDiscount = (float) $cart->discount;
    @endphp
    <script>
        function cartPage() {
            return {
                items: @json($cartItems),
                coupon: @json($cartCoupon),
                discount: {{ $cartDiscount }},
                couponCode: '',
                couponError: '',
                applyingCoupon: false,
                csrfToken: '{{ csrf_token() }}',
                currencySymbol: '{{ currency_symbol() }}',
                currencyPosition: '{{ currency_position() }}',

                fp(amount) {
                    const formatted = amount.toFixed(2);
                    return this.currencyPosition === 'after'
                        ? formatted + this.currencySymbol
                        : this.currencySymbol + formatted;
                },

                get totalQty() {
                    return this.items.reduce((sum, i) => sum + i.quantity, 0);
                },
                get subtotal() {
                    return this.items.reduce((sum, i) => sum + (i.price * i.quantity), 0);
                },
                get totalMrp() {
                    return this.items.reduce((sum, i) => sum + (i.mrp * i.quantity), 0);
                },
                get productDiscount() {
                    return this.totalMrp - this.subtotal;
                },
                get totalAmount() {
                    return this.subtotal - this.discount;
                },
                get totalSavings() {
                    return this.productDiscount + this.discount;
                },

                async updateQty(item, newQty) {
                    if (newQty < 1 || newQty > 99 || item.updating) return;
                    item.updating = true;
                    const oldQty = item.quantity;
                    item.quantity = newQty;

                    try {
                        const res = await fetch(`/cart/${item.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ quantity: newQty }),
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            item.quantity = oldQty;
                            this.toast(data.error || 'Failed to update', 'error');
                        } else {
                            this.syncCouponData(data);
                            this.updateCartBadge();
                        }
                    } catch (e) {
                        item.quantity = oldQty;
                        this.toast('Something went wrong', 'error');
                    } finally {
                        item.updating = false;
                    }
                },

                async removeItem(item) {
                    if (item.updating) return;
                    item.updating = true;

                    try {
                        const res = await fetch(`/cart/${item.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                        });
                        const data = await res.json();
                        if (res.ok) {
                            this.items = this.items.filter(i => i.id !== item.id);
                            this.syncCouponData(data);
                            this.updateCartBadge();
                            this.toast('Item removed from cart');
                        } else {
                            this.toast(data.error || 'Failed to remove', 'error');
                            item.updating = false;
                        }
                    } catch (e) {
                        this.toast('Something went wrong', 'error');
                        item.updating = false;
                    }
                },

                async clearCart() {
                    try {
                        const res = await fetch('/cart', {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                        });
                        if (res.ok) {
                            this.items = [];
                            this.coupon = null;
                            this.discount = 0;
                            this.updateCartBadge();
                            this.toast('Cart cleared');
                        }
                    } catch (e) {
                        this.toast('Something went wrong', 'error');
                    }
                },

                async applyCoupon() {
                    if (!this.couponCode.trim() || this.applyingCoupon) return;
                    this.applyingCoupon = true;
                    this.couponError = '';

                    try {
                        const res = await fetch('/cart/apply-coupon', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ code: this.couponCode }),
                        });
                        const data = await res.json();
                        if (res.ok) {
                            this.syncCouponData(data);
                            this.couponCode = '';
                            this.toast('Coupon applied successfully');
                        } else {
                            this.couponError = data.error || 'Invalid coupon';
                        }
                    } catch (e) {
                        this.couponError = 'Something went wrong';
                    } finally {
                        this.applyingCoupon = false;
                    }
                },

                async removeCoupon() {
                    try {
                        const res = await fetch('/cart/remove-coupon', {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json',
                            },
                        });
                        const data = await res.json();
                        if (res.ok) {
                            this.syncCouponData(data);
                            this.toast('Coupon removed');
                        }
                    } catch (e) {
                        this.toast('Something went wrong', 'error');
                    }
                },

                syncCouponData(data) {
                    if (data.cart_discount !== undefined) {
                        this.discount = data.cart_discount;
                    }
                    if (data.coupon) {
                        this.coupon = data.coupon;
                    } else {
                        this.coupon = null;
                        this.discount = 0;
                    }
                },

                get couponLabel() {
                    if (!this.coupon) return '';
                    if (this.coupon.type === 'buy_x_get_y') {
                        return 'Buy ' + (this.coupon.buy_qty || 0) + ' Get ' + (this.coupon.get_qty || 0) + (this.coupon.value >= 100 ? ' Free' : ' at ' + this.coupon.value + '% off');
                    }
                    if (this.coupon.type === 'percentage') {
                        return this.coupon.code + ' (' + this.coupon.value + '% off)';
                    }
                    return this.coupon.code;
                },

                updateCartBadge() {
                    const store = Alpine.store('cart');
                    if (store) {
                        store.items = this.items.map(i => ({ quantity: i.quantity, price: i.price }));
                    }
                },

                toast(message, type = 'success') {
                    const store = Alpine.store('toast');
                    if (store) {
                        type === 'error' ? store.error(message) : store.success(message);
                    }
                },
            };
        }
    </script>
</x-layouts.app>
