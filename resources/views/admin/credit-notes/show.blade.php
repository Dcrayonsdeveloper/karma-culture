<x-layouts.admin>
    <x-slot name="title">Credit Note {{ $creditNote->credit_note_number }}</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.credit-notes.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Credit Notes
        </a>
    </div>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">{{ $creditNote->credit_note_number }}</h1>
            <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">Created {{ $creditNote->created_at->format('F d, Y h:i A') }}</p>
        </div>
        @php
            $statusStyle = match($creditNote->status) {
                'active' => 'background: #cdfee1; color: #1a7a2e;',
                'partially_used' => 'background: #d4edfc; color: #0064a4;',
                'fully_used' => 'background: #ebebeb; color: #616161;',
                'expired' => 'background: #ffe0db; color: #b71c00;',
                default => 'background: #ebebeb; color: #616161;',
            };
        @endphp
        <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 13px; font-weight: 500; {{ $statusStyle }}">
            {{ ucfirst(str_replace('_', ' ', $creditNote->status)) }}
        </span>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        <!-- Main Content -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Amount Details -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Credit Details</h2>
                </div>
                <div style="padding: 1rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div style="text-align: center; padding: 1rem; background: #f6f6f7; border-radius: 0.75rem;">
                            <p style="font-size: 13px; color: #616161; margin: 0 0 0.25rem 0;">Total Amount</p>
                            <p style="font-size: 1.25rem; font-weight: 700; color: #303030; margin: 0;">@price($creditNote->amount)</p>
                        </div>
                        <div style="text-align: center; padding: 1rem; background: #d4edfc; border-radius: 0.75rem;">
                            <p style="font-size: 13px; color: #0064a4; margin: 0 0 0.25rem 0;">Used</p>
                            <p style="font-size: 1.25rem; font-weight: 700; color: #0064a4; margin: 0;">@price($creditNote->used_amount)</p>
                        </div>
                        <div style="text-align: center; padding: 1rem; background: #cdfee1; border-radius: 0.75rem;">
                            <p style="font-size: 13px; color: #1a7a2e; margin: 0 0 0.25rem 0;">Remaining</p>
                            <p style="font-size: 1.25rem; font-weight: 700; color: #1a7a2e; margin: 0;">@price($creditNote->remaining_amount)</p>
                        </div>
                    </div>

                    @if($creditNote->amount > 0)
                        <div style="margin-top: 1rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 12px; color: #616161; margin-bottom: 0.25rem;">
                                <span>Usage</span>
                                <span>{{ number_format(($creditNote->used_amount / $creditNote->amount) * 100, 0) }}%</span>
                            </div>
                            <div style="width: 100%; background: #e3e3e3; border-radius: 1rem; height: 0.5rem;">
                                <div style="background: #005bd3; height: 0.5rem; border-radius: 1rem; width: {{ ($creditNote->used_amount / $creditNote->amount) * 100 }}%;"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Return Items -->
            @if($creditNote->return && $creditNote->return->items->count())
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Returned Items</h2>
                    </div>
                    <div>
                        @foreach($creditNote->return->items as $item)
                            <div style="padding: 0.75rem 1rem; display: flex; gap: 0.75rem; {{ !$loop->last ? 'border-bottom: 1px solid #f6f6f7;' : '' }}">
                                <img src="{{ $item->orderItem->product->primary_image_url ?? '' }}" alt="{{ $item->orderItem->product_name ?? 'Product' }}"
                                     style="width: 3.5rem; height: 3.5rem; border-radius: 0.5rem; object-fit: cover; background: #f6f6f7;">
                                <div style="flex: 1;">
                                    <h3 style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $item->orderItem->product_name ?? 'Product' }}</h3>
                                    <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">Qty: {{ $item->quantity }}</p>
                                </div>
                                <div style="text-align: right;">
                                    <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">@price($item->orderItem->price ?? 0)</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Usage History -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Usage History</h2>
                </div>
                @if($creditNote->usages && $creditNote->usages->count())
                    <div>
                        @foreach($creditNote->usages as $usage)
                            <div style="padding: 0.75rem 1rem; display: flex; align-items: center; justify-content: space-between; {{ !$loop->last ? 'border-bottom: 1px solid #f6f6f7;' : '' }}">
                                <div>
                                    <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">
                                        Used on Order
                                        @if($usage->order)
                                            <a href="{{ route('admin.orders.show', $usage->order) }}" style="color: #005bd3; text-decoration: none;">
                                                #{{ $usage->order->order_number }}
                                            </a>
                                        @endif
                                    </p>
                                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">{{ $usage->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <span style="font-size: 13px; font-weight: 600; color: #d72c0d;">-@price($usage->amount)</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="padding: 2rem; text-align: center;">
                        <p style="font-size: 13px; color: #616161; margin: 0;">No usage yet. This credit hasn't been redeemed.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Customer Info -->
            <div class="card" style="padding: 1.25rem;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Customer</h2>
                @if($creditNote->user)
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 2.5rem; height: 2.5rem; background: #d4edfc; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <span style="font-weight: 500; color: #0064a4;">{{ substr($creditNote->user->first_name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $creditNote->user->full_name }}</p>
                            <p style="font-size: 13px; color: #616161; margin: 0;">{{ $creditNote->user->email }}</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.customers.show', $creditNote->user) }}" class="btn btn-secondary" style="width: 100%; text-align: center; margin-top: 1rem; font-size: 13px;">View Customer</a>
                @else
                    <p style="font-size: 13px; color: #616161; margin: 0;">Customer not found</p>
                @endif
            </div>

            <!-- Linked Order -->
            @if($creditNote->order)
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Source Order</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Order</span>
                            <a href="{{ route('admin.orders.show', $creditNote->order) }}" style="font-weight: 500; color: #005bd3; text-decoration: none;">
                                {{ $creditNote->order->order_number }}
                            </a>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Order Total</span>
                            <span style="font-weight: 500; color: #303030;">@price($creditNote->order->total)</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                            <span style="color: #616161;">Order Status</span>
                            @php
                                $orderBadgeStyle = match($creditNote->order->status) {
                                    'completed' => 'background: #cdfee1; color: #1a7a2e;',
                                    'cancelled' => 'background: #ffe0db; color: #b71c00;',
                                    default => 'background: #d4edfc; color: #0064a4;',
                                };
                            @endphp
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; {{ $orderBadgeStyle }}">
                                {{ ucfirst(str_replace('_', ' ', $creditNote->order->status)) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Linked Return -->
            @if($creditNote->return)
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Source Return</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Return</span>
                            <a href="{{ route('admin.returns.show', $creditNote->return) }}" style="font-weight: 500; color: #005bd3; text-decoration: none;">
                                {{ $creditNote->return->return_number }}
                            </a>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                            <span style="color: #616161;">Status</span>
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">{{ ucfirst(str_replace('_', ' ', $creditNote->return->status)) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Reason</span>
                            <span style="font-weight: 500; color: #303030; text-align: right;">{{ $creditNote->return->reason ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Credit Note Info -->
            <div class="card" style="padding: 1.25rem;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Details</h2>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Created</span>
                        <span style="font-weight: 500; color: #303030;">{{ $creditNote->created_at->format('M d, Y') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Expires</span>
                        <span style="font-weight: 500; {{ $creditNote->expires_at?->isPast() ? 'color: #d72c0d;' : 'color: #303030;' }}">
                            {{ $creditNote->expires_at?->format('M d, Y') ?? 'Never' }}
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: #616161;">Status</span>
                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; {{ $statusStyle }}">{{ ucfirst(str_replace('_', ' ', $creditNote->status)) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
