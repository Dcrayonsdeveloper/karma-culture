<x-layouts.app>
    <x-slot name="title">Checkout - {{ config('app.name') }}</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-4">
            <x-breadcrumb :items="[['label' => 'Cart', 'url' => route('cart.index')], ['label' => 'Checkout', 'url' => null]]" />
        </div>

        <div class="container mx-auto px-4 pb-10">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-lg font-bold text-neutral-900">Checkout</h1>
                <a href="{{ route('cart.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    Back to Cart
                </a>
            </div>

            @php
                $prefill = auth()->user();
                // One source of truth with the Rule::in that validates the POST,
                // so an option can never be offered that the server rejects.
                $states = \App\Http\Controllers\CheckoutController::STATES;
            @endphp

            @php
                $codAvailable    = in_array('cod', $paymentMethods, true);
                $onlineAvailable = in_array('online', $paymentMethods, true);
                $defaultMethod   = old('payment_method', $codAvailable ? 'cod' : 'online');
            @endphp

            <form action="{{ route('checkout.process') }}" method="POST" x-data="{ pm: '{{ $defaultMethod }}' }">
                @csrf

                <div class="flex flex-col lg:flex-row lg:items-start gap-5">
                    <!-- Left Column: Shipping details -->
                    <div class="flex-1 min-w-0 space-y-4">
                        <div class="bg-white rounded-lg border border-neutral-100">
                            <div class="flex items-center gap-2.5 px-4 py-3 border-b border-neutral-100">
                                <div class="w-6 h-6 rounded-full bg-primary-600 text-white text-xs font-bold flex items-center justify-center">1</div>
                                <h2 class="text-sm font-semibold text-neutral-900">Shipping Details</h2>
                            </div>
                            @php
                                $kkDefaultAddr = $addresses->firstWhere('is_default', true)?->id ?? $addresses->first()?->id;
                            @endphp
                            <div class="p-4 space-y-3" x-data="{ addrId: '{{ old('address_id', $addresses->isNotEmpty() ? $kkDefaultAddr : '') }}' }">
                                @if($addresses->isNotEmpty())
                                    {{-- Saved addresses, so a returning customer does not retype
                                         details the account already holds. --}}
                                    <div class="space-y-2">
                                        @foreach($addresses as $kkAddr)
                                            <label class="flex items-start gap-3 border rounded-lg px-4 py-3 cursor-pointer transition-colors"
                                                   :class="addrId === '{{ $kkAddr->id }}' ? 'border-primary-500 ring-1 ring-primary-200 bg-primary-50' : 'border-neutral-200 hover:border-neutral-300'">
                                                <input type="radio" name="address_id" value="{{ $kkAddr->id }}" x-model="addrId" class="mt-1 accent-primary-600">
                                                {{-- overflow-wrap:anywhere, and it inherits, so all three lines
                                                     below are covered by the one declaration. min-w-0 alone is not
                                                     enough: a label or street line saved as one unbroken string has
                                                     no break opportunity in it, so the flex item shrinks but the text
                                                     still runs off the card - and off the screen on a phone, where
                                                     html{overflow-x:clip} silently cuts it rather than scrolling. --}}
                                                <span class="min-w-0 flex-1 [overflow-wrap:anywhere]">
                                                    <span class="flex items-center gap-2 text-sm font-semibold text-neutral-900">
                                                        <span class="min-w-0">{{ $kkAddr->label ?: 'Address' }}</span>
                                                        @if($kkAddr->is_default)
                                                            <span class="shrink-0 text-[10px] font-medium text-primary-700 bg-primary-100 rounded px-1.5 py-0.5">Default</span>
                                                        @endif
                                                    </span>
                                                    <span class="block text-xs text-neutral-700 mt-0.5">{{ trim($kkAddr->first_name . ' ' . $kkAddr->last_name) }} &middot; {{ $kkAddr->phone }}</span>
                                                    <span class="block text-xs text-neutral-600">{{ collect([$kkAddr->address_line_1, $kkAddr->address_line_2, $kkAddr->city, $kkAddr->state, $kkAddr->postal_code])->filter()->join(', ') }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                        <label class="flex items-center gap-3 border rounded-lg px-4 py-3 cursor-pointer transition-colors"
                                               :class="addrId === '' ? 'border-primary-500 ring-1 ring-primary-200 bg-primary-50' : 'border-neutral-200 hover:border-neutral-300'">
                                            <input type="radio" name="address_id" value="" x-model="addrId" class="accent-primary-600">
                                            <span class="text-sm font-semibold text-neutral-900">Use a new address</span>
                                        </label>
                                    </div>
                                    @error('address_id')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
                                @endif

                                {{-- The order confirmation goes to the address on the account, so
                                     this box shows it instead of asking for it. An editable box
                                     here meant the confirmation for an order placed on this
                                     account could be sent to any address typed over it; the
                                     address is changed in the profile, and process() reads it off
                                     the user row rather than from this form. --}}
                                <div>
                                    <label for="kk-co-email" class="block text-[11px] font-medium text-neutral-600 mb-1">Email</label>
                                    {{-- readonly AND no name attribute: readonly stops it being
                                         edited on the page, and dropping the name leaves it out of
                                         the POST altogether, so there is nothing for devtools to
                                         rewrite either. --}}
                                    <input type="email" id="kk-co-email" value="{{ $prefill?->email }}"
                                           readonly aria-readonly="true"
                                           class="w-full text-sm border border-neutral-200 bg-neutral-100 text-neutral-600 rounded-lg px-3 py-2 cursor-not-allowed focus:outline-none">
                                    <p class="mt-1 text-[11px] text-neutral-500">
                                        Order updates go to your account email.
                                        <a href="{{ route('account.profile') }}" class="text-primary-600 hover:text-primary-700 font-medium">Change it in your profile</a>.
                                    </p>
                                </div>

                                <div x-show="addrId === ''" x-cloak class="space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label for="kk-co-name" class="block text-[11px] font-medium text-neutral-600 mb-1">Full Name *</label>
                                        {{-- Length was the only thing this box asked for, so
                                             "chirag raw arakn@#@!#q13123123" passed the browser and
                                             was only refused by PersonName after the whole checkout
                                             had been submitted. The pattern is that rule's charset,
                                             and data-kk-chars refuses the character as it is typed. --}}
                                        <input type="text" name="full_name" id="kk-co-name" value="{{ old('full_name', $prefill->name ?? '') }}"
                                               minlength="2" maxlength="100" autocomplete="name"
                                               data-kk-chars="personName"
                                               pattern="{{ \App\Rules\ValidationRules::namePattern() }}"
                                               title="The full name may only contain letters, spaces, hyphens, apostrophes and periods."
                                               class="w-full text-sm border border-neutral-200 rounded-lg px-3 py-2 focus:border-primary-400 focus:ring focus:ring-primary-100"
                                               placeholder="Full name" :required="addrId === ''" :disabled="addrId !== ''">
                                        @error('full_name')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="kk-co-phone" class="block text-[11px] font-medium text-neutral-600 mb-1">Phone *</label>
                                        {{-- maxlength was 10, which made the box reject the
                                             "+91 98765 43210" form the server happily accepts.
                                             The pattern tolerates exactly what IndianMobile
                                             strips before testing the ten digits. --}}
                                        <input type="tel" name="phone" id="kk-co-phone" value="{{ old('phone') }}"
                                               maxlength="20" inputmode="numeric" autocomplete="tel"
                                               pattern="(\+?91[\s\-]?)?0?[6-9][0-9\s\-]{9,}"
                                               title="Enter a 10-digit Indian mobile number starting with 6, 7, 8 or 9."
                                               class="w-full text-sm border border-neutral-200 rounded-lg px-3 py-2 focus:border-primary-400 focus:ring focus:ring-primary-100"
                                               placeholder="10-digit mobile number" :required="addrId === ''" :disabled="addrId !== ''">
                                        @error('phone')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>


                                <div>
                                    <label for="kk-co-addr1" class="block text-[11px] font-medium text-neutral-600 mb-1">Address Line 1 *</label>
                                    <input type="text" name="address_line_1" id="kk-co-addr1" value="{{ old('address_line_1') }}"
                                           minlength="3" maxlength="255" autocomplete="address-line1"
                                           class="w-full text-sm border border-neutral-200 rounded-lg px-3 py-2 focus:border-primary-400 focus:ring focus:ring-primary-100"
                                           placeholder="House no., Building, Street" :required="addrId === ''" :disabled="addrId !== ''">
                                    @error('address_line_1')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="kk-co-addr2" class="block text-[11px] font-medium text-neutral-600 mb-1">Address Line 2</label>
                                    <input type="text" name="address_line_2" id="kk-co-addr2" value="{{ old('address_line_2') }}"
                                           minlength="3" maxlength="255" autocomplete="address-line2"
                                           class="w-full text-sm border border-neutral-200 rounded-lg px-3 py-2 focus:border-primary-400 focus:ring focus:ring-primary-100"
                                           placeholder="Area, Landmark" :disabled="addrId !== ''">
                                    @error('address_line_2')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label for="kk-co-city" class="block text-[11px] font-medium text-neutral-600 mb-1">City *</label>
                                        <input type="text" name="city" id="kk-co-city" value="{{ old('city') }}"
                                               minlength="1" maxlength="100" autocomplete="address-level2"
                                               class="w-full text-sm border border-neutral-200 rounded-lg px-3 py-2 focus:border-primary-400 focus:ring focus:ring-primary-100"
                                               placeholder="City" :required="addrId === ''" :disabled="addrId !== ''">
                                        @error('city')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="kk-co-state" class="block text-[11px] font-medium text-neutral-600 mb-1">State *</label>
                                        <select name="state" id="kk-co-state" autocomplete="address-level1" class="w-full text-sm border border-neutral-200 rounded-lg px-3 py-2 focus:border-primary-400 focus:ring focus:ring-primary-100" :required="addrId === ''" :disabled="addrId !== ''">
                                            <option value="">Select state</option>
                                            @foreach($states as $s)
                                                <option value="{{ $s }}" {{ old('state') === $s ? 'selected' : '' }}>{{ $s }}</option>
                                            @endforeach
                                        </select>
                                        @error('state')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="kk-co-pin" class="block text-[11px] font-medium text-neutral-600 mb-1">PIN Code *</label>
                                        {{-- inputmode="numeric" is what makes the box refuse letters
                                             as they are typed, so "asdasd" can no longer be entered. --}}
                                        <input type="text" name="postal_code" id="kk-co-pin" value="{{ old('postal_code') }}"
                                               inputmode="numeric" minlength="6" maxlength="6" autocomplete="postal-code"
                                               pattern="[1-9][0-9]{5}"
                                               title="Enter a 6-digit PIN code. It cannot start with 0."
                                               class="w-full text-sm border border-neutral-200 rounded-lg px-3 py-2 focus:border-primary-400 focus:ring focus:ring-primary-100"
                                               placeholder="400001" :required="addrId === ''" :disabled="addrId !== ''">
                                        @error('postal_code')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="bg-white rounded-lg border border-neutral-100">
                            <div class="flex items-center gap-2.5 px-4 py-3 border-b border-neutral-100">
                                <div class="w-6 h-6 rounded-full bg-primary-600 text-white text-xs font-bold flex items-center justify-center">2</div>
                                <h2 class="text-sm font-semibold text-neutral-900">Payment Method</h2>
                            </div>
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @if($codAvailable)
                                    <label class="flex items-start gap-3 border rounded-lg px-4 py-3 cursor-pointer transition-colors"
                                           :class="pm === 'cod' ? 'border-primary-500 ring-1 ring-primary-200 bg-primary-50' : 'border-neutral-200 hover:border-neutral-300'">
                                        <input type="radio" name="payment_method" value="cod" x-model="pm" required class="mt-1 accent-primary-600">
                                        <span class="min-w-0">
                                            <span class="flex items-center gap-1.5 text-sm font-semibold text-neutral-900">
                                                <svg class="w-4 h-4 text-neutral-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                Cash on Delivery
                                            </span>
                                            <span class="block text-xs text-neutral-600 mt-0.5">Pay in cash when your order is delivered.</span>
                                        </span>
                                    </label>
                                @endif

                                @if($onlineAvailable)
                                    <label class="flex items-start gap-3 border rounded-lg px-4 py-3 cursor-pointer transition-colors"
                                           :class="pm === 'online' ? 'border-primary-500 ring-1 ring-primary-200 bg-primary-50' : 'border-neutral-200 hover:border-neutral-300'">
                                        <input type="radio" name="payment_method" value="online" x-model="pm" required class="mt-1 accent-primary-600">
                                        <span class="min-w-0">
                                            <span class="flex items-center gap-1.5 text-sm font-semibold text-neutral-900">
                                                <svg class="w-4 h-4 text-neutral-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                                Pay Online
                                            </span>
                                            <span class="block text-xs text-neutral-600 mt-0.5">UPI, Cards, Net Banking &amp; Wallets - secure payment.</span>
                                        </span>
                                    </label>
                                @else
                                    <div class="flex items-start gap-3 border border-neutral-200 rounded-lg px-4 py-3 opacity-60 cursor-not-allowed">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-semibold text-neutral-500">Pay Online</span>
                                            <span class="block text-xs text-neutral-500 mt-0.5">Temporarily unavailable - please use Cash on Delivery.</span>
                                        </span>
                                    </div>
                                @endif
                            </div>
                            @error('payment_method')<p class="px-4 pb-3 -mt-2 text-xs text-error-500">{{ $message }}</p>@enderror
                        </div>

                        <!-- Order Notes -->
                        <div class="bg-white rounded-lg border border-neutral-100">
                            <div class="flex items-center gap-2.5 px-4 py-3 border-b border-neutral-100">
                                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                <h2 class="text-sm font-semibold text-neutral-900">Order Notes</h2>
                            </div>
                            <div class="p-4">
                                <textarea name="notes" id="kk-co-notes" rows="2" maxlength="500"
                                          aria-label="Order notes" class="form-input w-full text-[13px]"
                                          placeholder="Special instructions for delivery or your order...">{{ old('notes') }}</textarea>
                                @error('notes')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Order Summary -->
                    <div class="lg:w-85 shrink-0 self-stretch">
                        <div class="bg-white rounded-lg border border-neutral-100 sticky top-20 flex flex-col">
                            <!-- Coupon Display -->
                            @if($cart->coupon)
                                <div class="p-4 border-b border-neutral-100">
                                    <div class="flex items-center gap-2 mb-2.5">
                                        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        <span class="text-sm font-semibold text-neutral-800">Coupon Applied</span>
                                    </div>
                                    <div class="flex items-center justify-between px-3 py-2 bg-success-50 border border-dashed border-success-300 rounded-md">
                                        <span class="text-xs font-bold text-success-700 bg-success-100 px-2 py-0.5 rounded">{{ $cart->coupon->code }}</span>
                                        <span class="text-xs font-semibold text-success-700">-@price($cart->discount)</span>
                                    </div>
                                </div>
                            @endif

                            <!-- Items -->
                            <div class="p-4 border-b border-neutral-100">
                                <h3 class="text-[11px] font-bold text-neutral-600 uppercase tracking-wider mb-3">Order Items ({{ $cart->items->sum('quantity') }} {{ $cart->items->sum('quantity') === 1 ? 'item' : 'items' }})</h3>

                                <div class="space-y-3 max-h-52 overflow-y-auto">
                                    @foreach($cart->items as $item)
                                        <div class="flex gap-2.5">
                                            <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product->name }}"
                                                 class="w-12 h-12 rounded border border-neutral-100 bg-neutral-50 object-contain shrink-0">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[13px] font-medium text-neutral-800 line-clamp-1">{{ $item->product->name }}</p>
                                                @if($item->size || $item->colour)
                                                    <p class="text-[11px] text-neutral-600 mt-0.5">{{ collect([$item->size ? 'Size: ' . $item->size : null, $item->colour ? 'Colour: ' . $item->colour : null])->filter()->join(' · ') }}</p>
                                                @endif
                                                <div class="flex items-center justify-between mt-0.5">
                                                    <span class="text-[11px] text-neutral-600">Qty: {{ $item->quantity }}</span>
                                                    <span class="text-[13px] font-semibold text-neutral-900">@price($item->price * $item->quantity)</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Price Details -->
                            <div class="p-4">
                                <h3 class="text-[11px] font-bold text-neutral-600 uppercase tracking-wider mb-3">Price Details</h3>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-[13px]">
                                        <span class="text-neutral-600">Subtotal</span>
                                        <span class="text-neutral-800 font-medium">@price($cart->subtotal)</span>
                                    </div>

                                    @if($cart->discount > 0)
                                        <div class="flex items-center justify-between text-[13px]">
                                            <span class="text-neutral-600">Discount</span>
                                            <span class="text-success-600 font-medium">-@price($cart->discount)</span>
                                        </div>
                                    @endif

                                    @php $kkFreeShip = (int) \App\Models\Setting::get('free_shipping_threshold', 999); @endphp
                                    <div class="flex items-center justify-between text-[13px]">
                                        <span class="text-neutral-600">Shipping <span class="text-neutral-400">(free over ₹{{ number_format($kkFreeShip) }})</span></span>
                                        <span class="text-success-600 font-semibold">FREE</span>
                                    </div>
                                </div>

                                <div class="border-t border-dashed border-neutral-200 my-3"></div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-neutral-900">Total Amount</span>
                                    <span class="text-sm font-bold text-neutral-900">@price($cart->subtotal - $cart->discount)</span>
                                </div>

                                @if($cart->discount > 0)
                                    <div class="mt-3 px-3 py-2 bg-success-50 border border-success-100 rounded-md">
                                        <p class="text-xs font-semibold text-success-700 text-center">
                                            You save @price($cart->discount) on this order
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <!-- Place Order Button -->
                            <div class="p-4 pt-0">
                                <button type="submit"
                                        class="block w-full py-3 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold text-center rounded-lg transition-colors shadow-sm">
                                    <span x-show="pm !== 'online'">PLACE ORDER</span>
                                    <span x-show="pm === 'online'" x-cloak>CONTINUE TO PAYMENT</span>
                                </button>
                            </div>

                            <!-- Terms -->
                            <div class="px-4 pb-4">
                                <p class="text-[10px] text-neutral-600 text-center leading-relaxed">
                                    By placing your order, you agree to our
                                    <a href="{{ route('terms') }}" class="text-primary-500 hover:text-primary-600">Terms</a>
                                    and
                                    <a href="{{ route('privacy') }}" class="text-primary-500 hover:text-primary-600">Privacy Policy</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
