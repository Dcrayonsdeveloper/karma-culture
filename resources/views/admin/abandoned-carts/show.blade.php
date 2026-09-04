@php
    $cart = $abandonedCart->cart;
    $items = $cart?->items ?? collect();
    $liveTotal = $abandonedCart->liveTotal();
    $cartStatus = $abandonedCart->cartStatus();
    $email = $abandonedCart->contactEmail();
    $customer = $abandonedCart->user;
@endphp

<x-layouts.admin>
    <x-slot name="title">Abandoned cart #{{ $abandonedCart->id }}</x-slot>

    <x-slot name="header">
        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <a href="{{ route('admin.abandoned-carts.index') }}" style="display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 0.5rem; color: #616161; text-decoration: none;" class="btn-icon">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div style="flex: 1 1 14rem; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; row-gap: 0.25rem;">
                    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030;">Abandoned cart #{{ $abandonedCart->id }}</h1>
                    <span class="badge {{ $abandonedCart->badgeClass() }}">{{ \App\Models\AbandonedCart::statusLabel($abandonedCart->recovery_status) }}</span>
                    <span class="badge {{ $abandonedCart->cartStatusBadgeClass() }}">Cart {{ $cartStatus }}</span>
                </div>
                <p style="font-size: 13px; color: #616161; margin-top: 2px;">
                    Abandoned {{ $abandonedCart->abandoned_at->format('M d, Y \a\t g:i A') }} &middot; {{ $abandonedCart->timeSinceAbandonment() }} ago
                </p>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-left: auto;">
                @if($blockedReason)
                    <span class="btn btn-secondary btn-sm" style="opacity: 0.55; cursor: not-allowed;" title="{{ $blockedReason }}">Send recovery email</span>
                @else
                    {{-- The address is deliberately NOT interpolated into the
                         confirm() string. Blade escapes an apostrophe to &#039;,
                         the HTML parser turns it back into ', and it then closes
                         the JS string literal - the guard silently disappears. --}}
                    <form action="{{ route('admin.abandoned-carts.remind', $abandonedCart) }}" method="POST" style="display: inline;"
                          onsubmit="return confirm('Send the recovery email to this customer now?')">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">Send recovery email</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <x-admin.form-errors />

    @if($blockedReason)
        <div class="card" style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-left: 3px solid #b98900;">
            <p style="font-size: 13px; color: #303030;">{{ $blockedReason }}</p>
        </div>
    @endif

    @if($abandonedCart->last_reminder_error)
        <div class="card" style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-left: 3px solid #d72c0d;">
            <p style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 2px;">The last reminder could not be delivered</p>
            <p style="font-size: 13px; color: #616161; overflow-wrap: anywhere;">{{ $abandonedCart->last_reminder_error }}</p>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;" class="lg:!grid-cols-[1fr_340px]">
        {{-- Main column --}}
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            {{-- Products --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Products in the cart</h2>
                    <span style="font-size: 12px; color: #616161;">
                        {{ $items->count() }} line(s) now &middot; {{ $abandonedCart->item_count }} when abandoned
                    </span>
                </div>

                @forelse($items as $item)
                    @php
                        $product = $item->product;
                        $variantLabel = $item->variant?->attributeValues->pluck('value')->join(' / ');
                        $lineTotal = (float) $item->total;
                        // The line was priced when it went in; the product may
                        // have moved since. Showing both is the difference
                        // between "recover this basket" and a surprise at checkout.
                        $currentPrice = $product?->price !== null ? (float) $product->price : null;
                        $priceMoved = $currentPrice !== null && abs($currentPrice - (float) $item->price) > 0.001;
                    @endphp
                    <div style="padding: 0.75rem 1rem; display: flex; gap: 0.75rem; border-bottom: 1px solid #f1f1f1;">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: #f7f7f7; border: 1px solid #e3e3e3; overflow: hidden; flex-shrink: 0;">
                            @if($product?->primary_image_url)
                                <img src="{{ $product->primary_image_url }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                            @endif
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            @if($product && auth('admin')->user()->canAccessSection('catalog'))
                                <a href="{{ route('admin.products.edit', $product) }}" style="font-size: 13px; font-weight: 500;">{{ $product->name }}</a>
                            @elseif($product)
                                <p style="font-size: 13px; font-weight: 500; color: #303030;">{{ $product->name }}</p>
                            @else
                                {{-- cart_items.product_id cascades on delete, so a
                                     missing product here means the row was left by
                                     a soft-deleted product rather than a hard one. --}}
                                <p style="font-size: 13px; font-weight: 500; color: #303030;">Product no longer available</p>
                            @endif
                            @if($variantLabel)
                                <p style="font-size: 12px; color: #616161;">{{ $variantLabel }}</p>
                            @endif
                            @if($item->size)
                                <p style="font-size: 12px; color: #616161;">Size: <strong>{{ $item->size }}</strong></p>
                            @endif
                            @if($item->colour)
                                <p style="font-size: 12px; color: #616161;">Colour: <strong>{{ $item->colour }}</strong></p>
                            @endif
                            <p style="font-size: 12px; color: #999; font-family: monospace;">SKU: {{ $item->variant?->sku ?: ($product?->sku ?: '-') }}</p>
                            @if($product && ! $product->is_active)
                                <span class="badge badge-error">Product inactive</span>
                            @endif
                            @if($product && ($product->stock_quantity ?? 0) < $item->quantity)
                                <span class="badge badge-warning">Only {{ (int) ($product->stock_quantity ?? 0) }} in stock</span>
                            @endif
                            @if($priceMoved)
                                <span class="badge badge-info">Price now @price($currentPrice)</span>
                            @endif
                        </div>
                        <div style="text-align: right; flex-shrink: 0;">
                            <p style="font-size: 13px; color: #616161;">@price($item->price) &times; {{ $item->quantity }}</p>
                            <p style="font-size: 13px; font-weight: 600; color: #303030;">@price($lineTotal)</p>
                        </div>
                    </div>
                @empty
                    <div style="padding: 2rem 1rem; text-align: center;">
                        <p style="font-size: 13px; color: #616161;">
                            This cart is empty now. Its contents when it was abandoned are shown in the summary.
                        </p>
                    </div>
                @endforelse

                {{-- Totals. Snapshot first, because that is the basket this
                     episode is about; the live figures follow when they differ. --}}
                <div style="padding: 0.75rem 1rem; background: #fafafa;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #616161; padding: 0.25rem 0;">
                        <span>Subtotal</span><span>@price($abandonedCart->subtotal)</span>
                    </div>
                    @if((float) $abandonedCart->discount > 0)
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #1a7a2e; padding: 0.25rem 0;">
                            <span>Discount{{ $cart?->coupon ? ' ('.$cart->coupon->code.')' : '' }}</span><span>-@price($abandonedCart->discount)</span>
                        </div>
                    @endif
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #616161; padding: 0.25rem 0;">
                        <span>Tax</span><span>@price($abandonedCart->tax)</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #616161; padding: 0.25rem 0;">
                        <span>Shipping</span><span>@price($abandonedCart->shipping)</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 600; color: #303030; padding: 0.5rem 0 0; border-top: 1px solid #e3e3e3; margin-top: 0.25rem;">
                        <span>Cart total when abandoned</span><span>@price($abandonedCart->total) {{ $abandonedCart->currency }}</span>
                    </div>
                    @if(abs($liveTotal - (float) $abandonedCart->subtotal) > 0.001)
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #b98900; padding: 0.375rem 0 0;">
                            <span>Line total today</span><span>@price($liveTotal)</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Earlier episodes of the same cart row --}}
            @if($history->isNotEmpty())
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Earlier baskets in this cart</h2>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; padding: 0.5rem 0.75rem 0.5rem 1rem;">Episode</th>
                                    <th style="text-align: left;">Abandoned</th>
                                    <th style="text-align: right;">Value</th>
                                    <th style="text-align: left; padding: 0.5rem 1rem 0.5rem 0.75rem;">Outcome</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $past)
                                    <tr style="cursor: pointer;" onclick="window.location='{{ route('admin.abandoned-carts.show', $past) }}'">
                                        <td style="padding: 0.5rem 0.75rem 0.5rem 1rem;"><span style="font-size: 13px; color: #005bd3;">#{{ $past->id }}</span></td>
                                        <td><span style="font-size: 13px; color: #616161;">{{ $past->abandoned_at->format('M d, Y') }}</span></td>
                                        <td style="text-align: right;"><span style="font-size: 13px; color: #303030;">@price($past->total)</span></td>
                                        <td style="padding: 0.5rem 1rem 0.5rem 0.75rem;"><span class="badge {{ $past->badgeClass() }}">{{ \App\Models\AbandonedCart::statusLabel($past->recovery_status) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            {{-- Customer --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Customer</h2>
                </div>
                <div style="padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.375rem;">
                    <p style="font-size: 13px; color: #303030; font-weight: 500;">{{ $abandonedCart->customerName() }}</p>
                    @if($customer)
                        @if($customer->trashed())
                            <span class="badge badge-error">Account deleted</span>
                        @endif
                        <p style="font-size: 13px; color: #616161;">Customer ID: {{ $customer->id }}</p>
                        <p style="font-size: 13px; overflow-wrap: anywhere;">
                            {{ $email ?: 'No email on file' }}
                        </p>
                        <p style="font-size: 13px; color: #616161;">{{ $abandonedCart->contactPhone() ?: 'No phone on file' }}</p>
                        {{-- Gated on the section that owns the page, not on this
                             one: a recovery-desk staff member may hold
                             `abandoned_carts` without `customers`, and an
                             ungated link would just 403 them. --}}
                        @if(! $customer->trashed() && auth('admin')->user()->canAccessSection('customers'))
                            <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-secondary btn-sm" style="margin-top: 0.25rem; text-align: center;">View customer</a>
                        @endif
                    @else
                        {{-- Guest carts only exist from before adding to a cart
                             required an account. There is no email anywhere for
                             one, so a reminder is impossible - the recovery link
                             has to be handed over by hand. --}}
                        <p style="font-size: 13px; color: #616161;">
                            This is a guest cart with no account behind it, so there is no address to email. Copy the recovery link below if you reach the customer another way.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Cart --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Cart</h2>
                </div>
                <div style="padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.375rem; font-size: 13px; color: #616161;">
                    <p>Cart ID: <span style="color: #303030;">{{ $abandonedCart->cart_id }}</span></p>
                    <p>Created: <span style="color: #303030;">{{ $cart?->created_at?->format('M d, Y') ?? '-' }}</span></p>
                    <p>Last activity: <span style="color: #303030;">{{ $abandonedCart->last_activity_at->format('M d, Y \a\t g:i A') }}</span></p>
                    <p>Abandoned: <span style="color: #303030;">{{ $abandonedCart->abandoned_at->format('M d, Y \a\t g:i A') }}</span></p>
                    <p>Units: <span style="color: #303030;">{{ $abandonedCart->quantity }}</span></p>
                    <p>Currency: <span style="color: #303030;">{{ $abandonedCart->currency }}</span></p>
                    @if($cart)
                        {{-- created_at is when this customer FIRST ever had a
                             cart, not when this basket started: checkout empties
                             the row and hands the same one back next time. --}}
                        <p style="font-size: 12px; color: #999;">The cart row is reused for every basket this customer builds, so its creation date can be much older than these items.</p>
                    @endif
                </div>
            </div>

            {{-- Recovery --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Recovery</h2>
                </div>
                <div style="padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.5rem; font-size: 13px; color: #616161;">
                    <p>Status: <span class="badge {{ $abandonedCart->badgeClass() }}">{{ \App\Models\AbandonedCart::statusLabel($abandonedCart->recovery_status) }}</span></p>
                    <p>Reminders sent: <span style="color: #303030;">{{ $abandonedCart->reminder_count }}</span></p>
                    <p>Last reminder: <span style="color: #303030;">{{ $abandonedCart->last_reminder_at?->format('M d, Y g:i A') ?? 'Never' }}</span></p>
                    <p>Last contact attempt: <span style="color: #303030;">{{ $abandonedCart->last_contacted_at?->format('M d, Y g:i A') ?? 'None' }}</span></p>
                    @if($abandonedCart->isRecovered())
                        <p>Recovered: <span style="color: #303030;">{{ $abandonedCart->recovered_at?->format('M d, Y g:i A') ?? '-' }}</span></p>
                        @if($abandonedCart->recoveredOrder && auth('admin')->user()->canAccessSection('orders'))
                            <a href="{{ route('admin.orders.show', $abandonedCart->recoveredOrder) }}" class="btn btn-secondary btn-sm" style="text-align: center;">
                                View order {{ $abandonedCart->recoveredOrder->order_number }}
                            </a>
                        @elseif($abandonedCart->recoveredOrder)
                            <p>Order: <span style="color: #303030;">{{ $abandonedCart->recoveredOrder->order_number }}</span></p>
                        @else
                            <p style="font-size: 12px; color: #999;">Recorded by hand, with no order attached.</p>
                        @endif
                    @endif

                    {{-- Recovery link. The token is the whole credential, so it
                         is rendered once here and never put in a listing. --}}
                    <div x-data="{ copied: false }" style="margin-top: 0.25rem;">
                        <label class="form-label" style="font-size: 12px;">Recovery link</label>
                        <input type="text" readonly class="form-input" style="font-size: 12px;"
                               x-ref="link" value="{{ $abandonedCart->recoveryUrl() }}">
                        <p style="font-size: 12px; color: #999; margin-top: 0.25rem;">
                            Valid until {{ $linkExpiresAt->format('M d, Y') }}. It only opens this cart for its owner.
                        </p>
                        <button type="button" class="btn btn-secondary btn-sm" style="margin-top: 0.375rem; width: 100%;"
                                @click="$refs.link.select(); navigator.clipboard?.writeText($refs.link.value); copied = true; setTimeout(() => copied = false, 2000)">
                            <span x-show="!copied">Copy recovery link</span>
                            <span x-show="copied" x-cloak>Copied</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030;">Actions</h2>
                </div>
                <div style="padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    @if($abandonedCart->isOpen())
                        <form action="{{ route('admin.abandoned-carts.contacted', $abandonedCart) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;">Mark as contacted</button>
                        </form>
                    @endif

                    @if(! $abandonedCart->isRecovered())
                        <form action="{{ route('admin.abandoned-carts.recovered', $abandonedCart) }}" method="POST"
                              onsubmit="return confirm('Mark this cart as recovered? It will count towards the recovery rate and cannot be undone from this screen.')">
                            @csrf
                            <label class="form-label" style="font-size: 12px;">Order ID (optional)</label>
                            <input type="number" min="1" name="order_id" class="form-input" placeholder="Link the order this became">
                            <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; margin-top: 0.375rem;">Mark as recovered</button>
                        </form>

                        <form action="{{ route('admin.abandoned-carts.archive', $abandonedCart) }}" method="POST"
                              onsubmit="return confirm('Archive this cart? It will be left out of the recovery figures.')">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm" style="width: 100%;">Archive / ignore</button>
                        </form>
                    @endif

                    @if(! $abandonedCart->isOpen() && ! $abandonedCart->isRecovered())
                        <p style="font-size: 12px; color: #999;">This cart is {{ \App\Models\AbandonedCart::statusLabel($abandonedCart->recovery_status) }}. It reopens on its own if the customer comes back and changes the basket.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
