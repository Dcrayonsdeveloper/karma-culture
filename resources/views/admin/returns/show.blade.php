<x-layouts.admin>
    <x-slot name="title">Return {{ $return->return_number }}</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.returns.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Returns
        </a>
    </div>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Return {{ $return->return_number }}</h1>
        @php
            $headerStatusStyle = match($return->status) {
                'requested' => 'background: #fff3cd; color: #8a6d00;',
                'approved', 'pickup_scheduled', 'picked_up' => 'background: #d4edfc; color: #0064a4;',
                'received', 'processed' => 'background: #d4edfc; color: #0064a4;',
                'rejected' => 'background: #ffe0db; color: #b71c00;',
                'completed' => 'background: #cdfee1; color: #1a7a2e;',
                default => 'background: #ebebeb; color: #616161;',
            };
        @endphp
        <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 1rem; font-size: 13px; font-weight: 500; {{ $headerStatusStyle }}">
            {{ $return->status === 'processed' ? 'Refund Processed' : ucfirst(str_replace('_', ' ', $return->status)) }}
        </span>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        <!-- Main Content -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Return Details -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Return Details</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: #616161;">Type</span>
                        @php
                            $typeStyle = match($return->type ?? 'return') {
                                'return' => 'background: #d4edfc; color: #0064a4;',
                                'refund' => 'background: #fff3cd; color: #8a6d00;',
                                'exchange' => 'background: #ebebeb; color: #616161;',
                                default => 'background: #ebebeb; color: #616161;',
                            };
                        @endphp
                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; {{ $typeStyle }}">
                            {{ ucfirst($return->type ?? 'Return') }}
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: #616161;">Reason</span>
                        <span style="font-weight: 500; color: #303030;">{{ $return->reason ?? '-' }}</span>
                    </div>
                    @if($return->description)
                        <div style="padding-top: 0.5rem; border-top: 1px solid #e3e3e3;">
                            <span style="font-size: 13px; color: #616161;">Description</span>
                            <p style="margin: 0.5rem 0 0 0; font-size: 13px; color: #303030; background: #f6f6f7; border-radius: 0.5rem; padding: 0.75rem;">{{ $return->description }}</p>
                        </div>
                    @endif
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: #616161;">Created</span>
                        <span style="font-weight: 500; color: #303030;">{{ $return->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                    @if($return->approved_at)
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                            <span style="color: #616161;">Approved</span>
                            <span style="font-weight: 500; color: #303030;">{{ $return->approved_at->format('M d, Y h:i A') }}</span>
                        </div>
                    @endif
                    @if($return->refund_amount)
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                            <span style="color: #616161;">Refund Amount</span>
                            <span style="font-weight: 600; color: #1a7a2e;">@price($return->refund_amount)</span>
                        </div>
                    @endif
                    @if($return->completed_at)
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                            <span style="color: #616161;">Completed At</span>
                            <span style="font-weight: 500; color: #303030;">{{ $return->completed_at->format('M d, Y h:i A') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Return Items -->
            @if($return->items->count())
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Return Items</h2>
                    </div>
                    <div>
                        @foreach($return->items as $item)
                            <div style="padding: 0.75rem 1rem; display: flex; gap: 0.75rem; {{ !$loop->last ? 'border-bottom: 1px solid #f6f6f7;' : '' }}">
                                <div style="width: 3.5rem; height: 3.5rem; border-radius: 0.5rem; background: #f6f6f7; border: 1px solid #e3e3e3; overflow: hidden; flex-shrink: 0;">
                                    @if($item->orderItem->product->primary_image_url ?? null)
                                        <img src="{{ $item->orderItem->product->primary_image_url }}" alt="{{ $item->orderItem->product->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                            <svg style="width: 1.25rem; height: 1.25rem; color: #e3e3e3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $item->orderItem->product->name ?? 'Product' }}</p>
                                    <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">Qty: {{ $item->quantity ?? 1 }}</p>
                                    @if($item->reason)
                                        <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">{{ $item->reason }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Credit Note -->
            @if($return->creditNote)
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: #cdfee1; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg style="width: 1rem; height: 1rem; color: #1a7a2e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Credit Note Issued</h2>
                    </div>
                    <div style="padding: 1rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                            <div>
                                <a href="{{ route('admin.credit-notes.show', $return->creditNote) }}" style="font-size: 15px; font-weight: 600; color: #005bd3; text-decoration: none;">
                                    {{ $return->creditNote->credit_note_number }}
                                </a>
                                <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">{{ $return->creditNote->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                            @php
                                $cnStatusStyle = match($return->creditNote->status) {
                                    'active' => 'background: #cdfee1; color: #1a7a2e;',
                                    'partially_used' => 'background: #d4edfc; color: #0064a4;',
                                    'fully_used' => 'background: #ebebeb; color: #616161;',
                                    'expired' => 'background: #ffe0db; color: #b71c00;',
                                    default => 'background: #ebebeb; color: #616161;',
                                };
                            @endphp
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; {{ $cnStatusStyle }}">{{ ucfirst(str_replace('_', ' ', $return->creditNote->status)) }}</span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; text-align: center;">
                            <div style="background: #f6f6f7; border-radius: 0.5rem; padding: 0.75rem;">
                                <p style="font-size: 12px; color: #616161; margin: 0;">Amount</p>
                                <p style="font-size: 13px; font-weight: 700; color: #303030; margin: 0.25rem 0 0 0;">@price($return->creditNote->amount)</p>
                            </div>
                            <div style="background: #f6f6f7; border-radius: 0.5rem; padding: 0.75rem;">
                                <p style="font-size: 12px; color: #616161; margin: 0;">Used</p>
                                <p style="font-size: 13px; font-weight: 700; color: #0064a4; margin: 0.25rem 0 0 0;">@price($return->creditNote->used_amount)</p>
                            </div>
                            <div style="background: #f6f6f7; border-radius: 0.5rem; padding: 0.75rem;">
                                <p style="font-size: 12px; color: #616161; margin: 0;">Remaining</p>
                                <p style="font-size: 13px; font-weight: 700; color: #1a7a2e; margin: 0.25rem 0 0 0;">@price($return->creditNote->remaining_amount)</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.credit-notes.show', $return->creditNote) }}" class="btn btn-secondary" style="width: 100%; text-align: center; margin-top: 1rem; font-size: 13px;">
                            View Credit Note Details
                        </a>
                    </div>
                </div>
            @endif

            <!-- Update Status -->
            @if(!in_array($return->status, ['completed', 'rejected']))
                <div class="card" x-data="{ status: '{{ $return->status }}' }">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Update Status</h2>
                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; {{ $headerStatusStyle }}">
                            {{ $return->status === 'processed' ? 'Refund Processed' : ucfirst(str_replace('_', ' ', $return->status)) }}
                        </span>
                    </div>
                    <div style="padding: 1rem;">
                        <form action="{{ route('admin.returns.status', $return) }}" method="POST" style="display: flex; flex-direction: column; gap: 0.75rem;">
                            @csrf
                            @method('PUT')
                            <div>
                                <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">New Status</label>
                                <select name="status" x-model="status" class="form-select" style="width: 100%;">
                                    <option value="requested">Requested</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="pickup_scheduled">Pickup Scheduled</option>
                                    <option value="picked_up">Picked Up</option>
                                    <option value="received">Received</option>
                                </select>
                                <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">To complete the return, use the "Process Refund" form below</p>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                Update Status
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Process Refund -->
                @if(!in_array($return->status, ['requested', 'rejected']) && !$return->refund_amount)
                    <div class="card">
                        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: #cdfee1; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg style="width: 1rem; height: 1rem; color: #1a7a2e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Process Refund</h2>
                        </div>
                        <div style="padding: 1rem;">
                            <form action="{{ route('admin.returns.refund', $return) }}" method="POST" style="display: flex; flex-direction: column; gap: 0.75rem;">
                                @csrf
                                <div>
                                    <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Refund Amount ({{ currency_symbol() }})</label>
                                    <input type="number" name="amount" step="0.01" min="0"
                                           value="{{ $return->items->sum(fn($item) => ($item->orderItem->price ?? 0) * ($item->quantity ?? 1)) }}"
                                           class="form-input" style="width: 100%;" required>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Refund Method</label>
                                    <select name="refund_method" class="form-select" style="width: 100%;" required>
                                        <option value="wallet">Store Credit (Wallet)</option>
                                        <option value="original">Original Payment Method</option>
                                        <option value="bank">Bank Transfer</option>
                                    </select>
                                    <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">Store credit will be added to customer's account balance</p>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Notes (optional)</label>
                                    <textarea name="notes" rows="2" class="form-input" style="width: 100%;" placeholder="Add refund notes..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    Process Refund
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        <!-- Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Order Info -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Order Info</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: #616161;">Order</span>
                        <a href="{{ route('admin.orders.show', $return->order_id) }}" style="font-weight: 500; color: #005bd3; text-decoration: none;">
                            {{ $return->order->order_number ?? 'N/A' }}
                        </a>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: #616161;">Order Total</span>
                        <span style="font-weight: 600; color: #303030;">@price($return->order->total ?? 0)</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: #616161;">Order Status</span>
                        @php
                            $orderStatusStyle = match($return->order->status ?? '') {
                                'delivered', 'completed' => 'background: #cdfee1; color: #1a7a2e;',
                                'pending', 'confirmed' => 'background: #fff3cd; color: #8a6d00;',
                                'processing', 'packed' => 'background: #d4edfc; color: #0064a4;',
                                'shipped', 'out_for_delivery' => 'background: #d4edfc; color: #0064a4;',
                                'cancelled', 'returned' => 'background: #ffe0db; color: #b71c00;',
                                default => 'background: #ebebeb; color: #616161;',
                            };
                        @endphp
                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; {{ $orderStatusStyle }}">
                            {{ ucfirst(str_replace('_', ' ', $return->order->status ?? '-')) }}
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: #616161;">Order Date</span>
                        <span style="font-weight: 500; color: #303030;">{{ $return->order->created_at?->format('M d, Y') ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Assign Pickup Partner -->
            @if(in_array($return->status, ['approved', 'pickup_scheduled', 'picked_up']))
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: #ebebeb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg style="width: 1rem; height: 1rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>
                        </div>
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Pickup Partner</h2>
                    </div>
                    <div style="padding: 1rem;">
                        @if($return->pickupPartner)
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <div style="width: 2.5rem; height: 2.5rem; background: #ebebeb; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <span style="font-weight: 500; color: #616161;">{{ substr($return->pickupPartner->user->first_name ?? 'P', 0, 1) }}</span>
                                </div>
                                <div>
                                    <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $return->pickupPartner->user->full_name }}</p>
                                    <p style="font-size: 12px; color: #616161; margin: 0;">{{ $return->pickupPartner->partner_id }} &middot; {{ $return->pickupPartner->phone }}</p>
                                </div>
                            </div>
                        @endif
                        <form action="{{ route('admin.returns.assign-partner', $return) }}" method="POST" style="display: flex; flex-direction: column; gap: 0.75rem;">
                            @csrf
                            <select name="pickup_partner_id" class="form-select" style="width: 100%; font-size: 13px;">
                                <option value="">-- No partner --</option>
                                @foreach($activePartners as $partner)
                                    <option value="{{ $partner->id }}" {{ $return->pickup_partner_id == $partner->id ? 'selected' : '' }}>
                                        {{ $partner->user->full_name }} ({{ $partner->partner_id }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 13px;">
                                {{ $return->pickup_partner_id ? 'Change Partner' : 'Assign Partner' }}
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($return->pickupPartner)
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Pickup Partner</h2>
                    </div>
                    <div style="padding: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 2.5rem; height: 2.5rem; background: #ebebeb; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <span style="font-weight: 500; color: #616161;">{{ substr($return->pickupPartner->user->first_name ?? 'P', 0, 1) }}</span>
                            </div>
                            <div>
                                <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $return->pickupPartner->user->full_name }}</p>
                                <p style="font-size: 12px; color: #616161; margin: 0;">{{ $return->pickupPartner->partner_id }} &middot; {{ $return->pickupPartner->phone }}</p>
                            </div>
                        </div>
                        @if($return->pickup_scheduled_at)
                            <p style="font-size: 12px; color: #616161; margin: 0.5rem 0 0 0;">Scheduled: {{ $return->pickup_scheduled_at->format('M d, Y h:i A') }}</p>
                        @endif
                        @if($return->picked_up_at)
                            <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">Picked up: {{ $return->picked_up_at->format('M d, Y h:i A') }}</p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Customer Info -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Customer</h2>
                </div>
                <div style="padding: 1rem;">
                    @if($return->order->user ?? null)
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                            <div style="width: 2.5rem; height: 2.5rem; background: #d4edfc; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid #e3e3e3;">
                                <span style="font-size: 13px; font-weight: 700; color: #0064a4;">{{ strtoupper(substr($return->order->user->first_name ?? 'G', 0, 1)) }}</span>
                            </div>
                            <div>
                                <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $return->order->user->full_name }}</p>
                                <p style="font-size: 13px; color: #616161; margin: 0;">{{ $return->order->user->email }}</p>
                            </div>
                        </div>
                        @if($return->order->user->phone ?? null)
                            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 13px; color: #616161; margin-bottom: 0.5rem;">
                                <svg style="width: 1rem; height: 1rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                {{ $return->order->user->phone }}
                            </div>
                        @endif
                        <a href="{{ route('admin.customers.show', $return->order->user) }}" class="btn btn-secondary" style="width: 100%; text-align: center; margin-top: 0.5rem;">
                            View Customer
                        </a>
                    @else
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 2.5rem; height: 2.5rem; background: #f6f6f7; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1.25rem; height: 1.25rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <span style="font-size: 13px; color: #616161;">Guest checkout</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Photos -->
            @if($return->images && count($return->images))
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Photos</h2>
                    </div>
                    <div style="padding: 0.75rem; display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        @foreach($return->images as $image)
                            <a href="{{ $image }}" target="_blank" style="display: block; border-radius: 0.5rem; overflow: hidden; border: 1px solid #e3e3e3;">
                                <img src="{{ $image }}" alt="Return photo" style="width: 100%; height: 6rem; object-fit: cover;">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Processed By -->
            @if($return->processedBy)
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Processed By</h2>
                    </div>
                    <div style="padding: 1rem;">
                        <p style="font-size: 13px; color: #616161; margin: 0;">{{ $return->processedBy->full_name }}</p>
                        @if($return->completed_at)
                            <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">{{ $return->completed_at->format('M d, Y h:i A') }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
