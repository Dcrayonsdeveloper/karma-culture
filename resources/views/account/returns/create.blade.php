<x-layouts.app>
    <x-slot name="title">Request a Return</x-slot>

    <div class="bg-neutral-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col lg:flex-row gap-8">
                @include('account.partials.sidebar')

                <div class="flex-1">
                    {{-- Breadcrumb --}}
                    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-5">
                        <a href="{{ route('account.returns.index') }}" class="hover:text-[#6F9CA2] transition-colors">My Returns</a>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span class="text-neutral-900 font-medium">New Request</span>
                    </div>

                    @if($orders->isEmpty())
                        <div class="bg-white rounded-xl border border-neutral-200 p-12 text-center">
                            <div class="w-16 h-16 mx-auto bg-neutral-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-neutral-900 mb-1">No eligible orders</h3>
                            <p class="text-sm text-neutral-600 mb-5">You don't have any eligible orders. Returns are available {{ $returnMinHours }} hours after delivery, within a {{ $returnWindowDays }}-day window. Items already returned are excluded.</p>
                            <a href="{{ route('account.orders.index') }}" class="inline-flex items-center gap-2 bg-[#F8931D] hover:bg-[#E07E0A] text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">
                                View Orders
                            </a>
                        </div>
                    @else
                        {{-- `submitting` belongs to this component rather than being left to the
                             site-wide guard in app.js, because the button below already carries an
                             Alpine :disabled binding and two owners of one property fight. app.js
                             sets button.disabled imperatively as the request leaves; the next time
                             selectedItems changes, Alpine's effect re-runs and hands the button
                             straight back - live, on top of a request that is already out. Folding
                             the flag into the same expression leaves Alpine the only writer of it.

                             What the missing guard cost: a second click posted the same return
                             again, and the answer to it contradicted the first with "One or more
                             selected items already have a return request." for a request the
                             customer had made exactly once.

                             The flag is raised from @submit, which the browser fires only after its
                             own required/min/max/step checks have passed, so a submission stopped by
                             client-side validation leaves the button live for the correction instead
                             of dead. It is lowered again on a persisted pageshow: a page restored
                             from the bfcache brings this component's state back exactly as it left -
                             mid-submit - and would otherwise be a form that can never be sent. --}}
                        <form action="{{ route('account.returns.store') }}" method="POST"
                              @submit="submitting = true"
                              @pageshow.window="if ($event.persisted) submitting = false"
                              x-data="{
                                  submitting: false,
                                  selectedOrder: '{{ old('order_id', '') }}',
                                  type: '{{ old('type', 'return') }}',
                                  orders: {{ Js::from($orders->map(fn($o) => [
                                      'id' => $o->id,
                                      'order_number' => $o->order_number,
                                      'date' => $o->created_at->format('M d, Y'),
                                      'items' => $o->items->map(fn($i) => [
                                          'id' => $i->id,
                                          'name' => $i->product->name ?? $i->product_name,
                                          'variant' => $i->variant_name,
                                          'quantity' => $i->quantity,
                                          'price' => $i->price,
                                      ]),
                                  ])) }},
                                  selectedItems: [],
                                  get currentOrder() {
                                      return this.orders.find(o => o.id == this.selectedOrder) || null;
                                  },
                                  toggleItem(itemId) {
                                      const idx = this.selectedItems.findIndex(si => si.id === itemId);
                                      if (idx > -1) {
                                          this.selectedItems.splice(idx, 1);
                                      } else {
                                          const item = this.currentOrder?.items.find(i => i.id === itemId);
                                          if (item) {
                                              this.selectedItems.push({ id: item.id, quantity: 1, reason: '', condition: 'unopened' });
                                          }
                                      }
                                  },
                                  isSelected(itemId) {
                                      return this.selectedItems.some(si => si.id === itemId);
                                  },
                                  getSelected(itemId) {
                                      return this.selectedItems.find(si => si.id === itemId);
                                  }
                              }"
                              class="space-y-4">
                            @csrf

                            {{-- Step 1: Select Order --}}
                            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                                <div class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                                    <span class="w-5 h-5 bg-[#F8931D] text-white text-[11px] font-bold rounded-full flex items-center justify-center">1</span>
                                    <h2 class="text-sm font-bold text-neutral-900">Select Order</h2>
                                </div>
                                <div class="p-5">
                                    <div class="grid gap-2">
                                        @foreach($orders as $order)
                                            <label @click="selectedOrder = '{{ $order->id }}'; selectedItems = []"
                                                   :class="selectedOrder == '{{ $order->id }}' ? 'border-[#6F9CA2]/50 bg-[#6F9CA2]/5 ring-1 ring-[#6F9CA2]/30' : 'border-neutral-200 hover:border-neutral-300 bg-white'"
                                                   class="flex flex-wrap items-center justify-between gap-2 p-3.5 rounded-lg border cursor-pointer transition-all">
                                                {{-- No `required` on purpose: the constraint applies to every
                                                     radio in the group, so the helper would print the same
                                                     message under all of them. The step is unreachable anyway
                                                     - items only appear once an order is picked, and Submit
                                                     stays disabled until an item is ticked - and the server
                                                     checks order_id belongs to this customer regardless. --}}
                                                <input type="radio" name="order_id" value="{{ $order->id }}" x-model="selectedOrder"
                                                       aria-label="Order {{ $order->order_number }}" class="sr-only">
                                                <div class="flex items-center gap-3">
                                                    <div :class="selectedOrder == '{{ $order->id }}' ? 'border-[#6F9CA2] bg-[#F8931D]' : 'border-neutral-300'"
                                                         class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors">
                                                        <div x-show="selectedOrder == '{{ $order->id }}'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-semibold text-neutral-900">{{ $order->order_number }}</p>
                                                        <p class="text-xs text-neutral-600">{{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}</p>
                                                    </div>
                                                </div>
                                                <span class="text-xs text-neutral-600">{{ $order->created_at->format('M d, Y') }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <x-field-error field="order_id" />
                                </div>
                            </div>

                            {{-- Step 2: Return Type --}}
                            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                                <div class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                                    <span class="w-5 h-5 bg-[#F8931D] text-white text-[11px] font-bold rounded-full flex items-center justify-center">2</span>
                                    <h2 class="text-sm font-bold text-neutral-900">Return Type</h2>
                                </div>
                                <div class="p-5">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <label @click="type = 'return'"
                                               :class="type === 'return' ? 'border-[#6F9CA2]/50 bg-[#6F9CA2]/5 ring-1 ring-[#6F9CA2]/30' : 'border-neutral-200 hover:border-neutral-300'"
                                               class="flex items-center gap-3 p-3.5 rounded-lg border cursor-pointer transition-all">
                                            <input type="radio" name="type" value="return" x-model="type" class="sr-only">
                                            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                                 :class="type === 'return' ? 'bg-[#6F9CA2]/10 text-[#6F9CA2]' : 'bg-neutral-100 text-neutral-600'">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-neutral-900">Return</p>
                                                <p class="text-xs text-neutral-600">Get a refund</p>
                                            </div>
                                        </label>
                                        <label @click="type = 'exchange'"
                                               :class="type === 'exchange' ? 'border-[#6F9CA2]/50 bg-[#6F9CA2]/5 ring-1 ring-[#6F9CA2]/30' : 'border-neutral-200 hover:border-neutral-300'"
                                               class="flex items-center gap-3 p-3.5 rounded-lg border cursor-pointer transition-all">
                                            <input type="radio" name="type" value="exchange" x-model="type" class="sr-only">
                                            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                                 :class="type === 'exchange' ? 'bg-[#6F9CA2]/10 text-[#6F9CA2]' : 'bg-neutral-100 text-neutral-600'">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-neutral-900">Exchange</p>
                                                <p class="text-xs text-neutral-600">Replace the item</p>
                                            </div>
                                        </label>
                                    </div>
                                    <x-field-error field="type" />
                                </div>
                            </div>

                            {{-- Step 3: Reason --}}
                            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                                <div class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                                    <span class="w-5 h-5 bg-[#F8931D] text-white text-[11px] font-bold rounded-full flex items-center justify-center">3</span>
                                    <h2 class="text-sm font-bold text-neutral-900">Reason for Return</h2>
                                </div>
                                <div class="p-5 space-y-3">
                                    {{-- $reasons is ReturnController::REASONS, the list the store
                                         action validates against, so the options offered here and
                                         the values accepted there cannot drift apart. --}}
                                    <select name="reason" id="reason" required aria-label="Reason for return"
                                            class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2.5 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50">
                                        <option value="">Select a reason...</option>
                                        @foreach ($reasons as $reasonOption)
                                            <option value="{{ $reasonOption }}" @selected(old('reason') === $reasonOption)>{{ $reasonOption }}</option>
                                        @endforeach
                                    </select>
                                    <x-field-error field="reason" />

                                    <textarea name="description" id="description" rows="2" maxlength="1000"
                                              aria-label="Additional details"
                                              placeholder="Additional details"
                                              class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2.5 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50 resize-none">{{ old('description') }}</textarea>
                                    <x-field-error field="description" />
                                </div>
                            </div>

                            {{-- Step 4: Select Items --}}
                            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden" x-show="currentOrder" x-cloak>
                                <div class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                                    <span class="w-5 h-5 bg-[#F8931D] text-white text-[11px] font-bold rounded-full flex items-center justify-center">4</span>
                                    <h2 class="text-sm font-bold text-neutral-900">Select Items to Return</h2>
                                </div>
                                <div class="p-5">
                                    <p class="text-xs text-neutral-600 mb-3">Select the items you'd like to return and provide details for each.</p>

                                    <div class="space-y-2">
                                        <template x-for="(item, idx) in currentOrder?.items || []" :key="item.id">
                                            <div :class="isSelected(item.id) ? 'border-[#6F9CA2]/50 bg-[#6F9CA2]/5/30 ring-1 ring-[#6F9CA2]/30' : 'border-neutral-200'"
                                                 class="rounded-lg border transition-all overflow-hidden">
                                                {{-- Item header --}}
                                                <div @click="toggleItem(item.id)" class="flex items-center gap-3 p-3.5 cursor-pointer">
                                                    <div :class="isSelected(item.id) ? 'bg-[#F8931D] border-[#6F9CA2]' : 'border-neutral-300'"
                                                         class="w-4.5 h-4.5 rounded border-2 flex items-center justify-center shrink-0 transition-colors">
                                                        <svg x-show="isSelected(item.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium text-neutral-900 truncate" x-text="item.name"></p>
                                                        <p class="text-xs text-neutral-600" x-show="item.variant" x-text="item.variant"></p>
                                                    </div>
                                                    <div class="text-right shrink-0">
                                                        <p class="text-sm font-semibold text-neutral-900" x-text="'₹' + parseFloat(item.price).toLocaleString('en-IN')"></p>
                                                        <p class="text-[11px] text-neutral-600" x-text="'Qty: ' + item.quantity"></p>
                                                    </div>
                                                </div>

                                                {{-- Item details (shown when selected) --}}
                                                <div x-show="isSelected(item.id)" x-collapse class="border-t border-neutral-100 bg-white p-3.5 space-y-3">
                                                    {{-- Hidden input for order_item_id --}}
                                                    <input type="hidden" :name="'items[' + idx + '][order_item_id]'" :value="item.id" :disabled="!isSelected(item.id)">

                                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                        {{-- Quantity --}}
                                                        <div>
                                                            <label class="block text-xs font-medium text-neutral-600 mb-1">Return Qty</label>
                                                            {{-- required + step: the box was free to be
                                                                 emptied or set to 1.5, and only the
                                                                 server said so. max is the quantity
                                                                 actually ordered, which the store
                                                                 action re-checks against the order. --}}
                                                            <input type="number"
                                                                   :name="'items[' + idx + '][quantity]'"
                                                                   min="1" :max="item.quantity" step="1" required
                                                                   inputmode="numeric"
                                                                   aria-label="Return quantity"
                                                                   title="Enter how many of this item you are returning."
                                                                   x-model.number="getSelected(item.id).quantity"
                                                                   :disabled="!isSelected(item.id)"
                                                                   class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50">
                                                        </div>

                                                        {{-- Condition --}}
                                                        <div>
                                                            <label class="block text-xs font-medium text-neutral-600 mb-1">Condition</label>
                                                            <select :name="'items[' + idx + '][condition]'"
                                                                    required
                                                                    aria-label="Item condition"
                                                                    x-model="getSelected(item.id).condition"
                                                                    :disabled="!isSelected(item.id)"
                                                                    class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50">
                                                                <option value="unopened">Unopened</option>
                                                                <option value="opened">Opened</option>
                                                                <option value="damaged">Damaged</option>
                                                            </select>
                                                        </div>

                                                        {{-- Reason --}}
                                                        <div>
                                                            <label class="block text-xs font-medium text-neutral-600 mb-1">Item Note</label>
                                                            <input type="text"
                                                                   :name="'items[' + idx + '][reason]'"
                                                                   maxlength="500"
                                                                   aria-label="Item note"
                                                                   x-model="getSelected(item.id).reason"
                                                                   :disabled="!isSelected(item.id)"
                                                                   placeholder="Any specific issue?"
                                                                   class="w-full rounded-lg border border-neutral-200 text-sm px-3 py-2 focus:border-[#6F9CA2]/50 focus:ring focus:ring-[#6F9CA2]/15 focus:ring-opacity-50">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <x-field-error field="items" />
                                </div>
                            </div>

                            {{-- Submit --}}
                            <div class="flex flex-wrap items-center gap-3 pt-2">
                                {{-- Two different reasons to be disabled that have to look the same
                                     to the customer: nothing is ticked yet, or the request they have
                                     already sent has not come back. Only the first was ever handled,
                                     so for the whole round trip the button stayed bright, hoverable
                                     and clickable - which is an invitation to press it again. The
                                     label says which of the two states it is in, because a greyed
                                     button with no explanation reads as broken; x-text over the
                                     server-rendered wording, so there is no flash of the wrong label
                                     before Alpine starts, and no label at all if it never does. --}}
                                <button type="submit"
                                        :disabled="submitting || selectedItems.length === 0"
                                        :aria-busy="submitting ? 'true' : 'false'"
                                        :class="(submitting || selectedItems.length === 0) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-[#E07E0A]'"
                                        class="inline-flex items-center gap-2 bg-[#F8931D] text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="submitting ? 'Submitting...' : 'Submit Return Request'">Submit Return Request</span>
                                </button>
                                <a href="{{ route('account.returns.index') }}" class="text-sm font-medium text-neutral-600 hover:text-neutral-700 transition-colors px-4 py-2.5">
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
