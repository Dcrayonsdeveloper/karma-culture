<x-layouts.app>
    <x-slot name="title">Request a Return - {{ $order->order_number }}</x-slot>

    <div class="bg-neutral-50 min-h-screen py-6 sm:py-10">
        <div class="max-w-2xl mx-auto px-4">

            <a href="{{ url()->previous() }}" class="inline-flex items-center gap-1.5 text-[13px] text-neutral-600 hover:text-neutral-900 mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to tracking
            </a>

            <h1 class="text-lg sm:text-xl font-bold text-neutral-900">Request a Return</h1>
            <p class="text-[13px] text-neutral-600 mt-1 mb-5">Order {{ $order->order_number }} &middot; delivered {{ $order->delivered_at?->format('d M Y') }}</p>

            <x-form-errors title="Your return request could not be submitted." />

            @if($items->isEmpty())
                <div class="bg-white border border-neutral-100 rounded-xl p-6 text-center">
                    <p class="text-[13px] text-neutral-600">Every item on this order already has a return request.</p>
                </div>
            @else
                <form action="{{ route('track-order.return.store', $order) }}" method="POST"
                      class="bg-white border border-neutral-100 rounded-xl overflow-hidden">
                    @csrf

                    <div class="px-5 py-4 border-b border-neutral-100">
                        <h2 class="text-[15px] font-semibold text-neutral-900">Select items</h2>
                    </div>

                    <div class="divide-y divide-neutral-100">
                        @foreach($items as $i => $item)
                            <label class="flex items-start gap-3 p-4 sm:p-5 cursor-pointer hover:bg-neutral-50/60">
                                <input type="checkbox" name="items[{{ $i }}][selected]" value="1"
                                       class="mt-1 form-checkbox"
                                       onchange="this.closest('label').querySelectorAll('select,input[type=number]').forEach(el => el.disabled = !this.checked)">
                                <input type="hidden" name="items[{{ $i }}][order_item_id]" value="{{ $item->id }}">

                                <div class="flex-1 min-w-0">
                                    <p class="text-[13px] font-medium text-neutral-900">{{ $item->product_name }}</p>
                                    <p class="text-xs text-neutral-600 mt-0.5">Ordered: {{ $item->quantity }} &middot; @price($item->price)</p>

                                    <div class="grid grid-cols-2 gap-3 mt-3">
                                        <div>
                                            <label class="block text-xs text-neutral-600 mb-1">Quantity</label>
                                            {{-- step and inputmode make the box whole-number-only and
                                                 refuse letters as they are typed; min/max already match
                                                 the server's min:1 and its ordered-quantity ceiling. --}}
                                            <input type="number" name="items[{{ $i }}][quantity]" value="1"
                                                   min="1" max="{{ $item->quantity }}" step="1" inputmode="numeric"
                                                   required aria-label="Return quantity for {{ $item->product_name }}" disabled
                                                   class="w-full px-3 py-2 text-sm border border-neutral-200 rounded-lg bg-white disabled:bg-neutral-50 disabled:text-neutral-400">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-neutral-600 mb-1">Condition</label>
                                            <select name="items[{{ $i }}][condition]" disabled
                                                    class="w-full px-3 py-2 text-sm border border-neutral-200 rounded-lg bg-white disabled:bg-neutral-50 disabled:text-neutral-400">
                                                <option value="unopened">Unopened</option>
                                                <option value="opened">Opened</option>
                                                <option value="damaged">Damaged</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="p-4 sm:p-5 border-t border-neutral-100 space-y-4">
                        <div>
                            <label class="block text-[13px] font-medium text-neutral-700 mb-1.5">What would you like?</label>
                            <select name="type" required
                                    class="w-full px-3.5 py-2.5 text-sm border border-neutral-200 rounded-lg bg-white">
                                <option value="return" @selected(old('type') === 'return')>Return &amp; refund</option>
                                <option value="exchange" @selected(old('type') === 'exchange')>Exchange</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[13px] font-medium text-neutral-700 mb-1.5">Reason</label>
                            <select name="reason" required
                                    class="w-full px-3.5 py-2.5 text-sm border border-neutral-200 rounded-lg bg-white">
                                @foreach(['Size or fit issue', 'Damaged or defective', 'Wrong item delivered', 'Not as described', 'Changed my mind', 'Other'] as $reason)
                                    <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ $reason }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[13px] font-medium text-neutral-700 mb-1.5">Anything else?</label>
                            <textarea name="description" rows="3" maxlength="1000"
                                      class="w-full px-3.5 py-2.5 text-sm border border-neutral-200 rounded-lg bg-white"
                                      placeholder="Tell us more so we can sort this out faster">{{ old('description') }}</textarea>
                        </div>

                        <button type="submit"
                                class="w-full px-5 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors"
                                style="background:#2D1810;"
                                onmouseover="this.style.background='#1f1109'" onmouseout="this.style.background='#2D1810'">
                            Submit Return Request
                        </button>
                        <p class="text-xs text-neutral-500 text-center">We will contact you on the mobile number used for this order.</p>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
