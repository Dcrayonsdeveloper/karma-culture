<x-layouts.admin>
    <x-slot name="title">{{ $deliveryPartner->user->full_name }}</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.delivery-partners.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Delivery Partners
        </a>
    </div>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">{{ $deliveryPartner->user->full_name }}</h1>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <a href="{{ route('admin.delivery-partners.edit', $deliveryPartner) }}" class="btn btn-primary" style="font-size: 13px;">Edit</a>
            <form action="{{ route('admin.delivery-partners.toggle-status', $deliveryPartner) }}" method="POST">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-secondary" style="font-size: 13px; {{ $deliveryPartner->is_active ? 'color: #b98900;' : 'color: #1a7a2e;' }}">
                    {{ $deliveryPartner->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
        <!-- Partner Details (left column) -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Partner Details</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e3e3e3;">
                        <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #d4edfc; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 15px; font-weight: 700; color: #0064a4;">{{ strtoupper(substr($deliveryPartner->user->first_name, 0, 1) . substr($deliveryPartner->user->last_name, 0, 1)) }}</span>
                        </div>
                        <div style="flex: 1;">
                            <p style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">{{ $deliveryPartner->user->full_name }}</p>
                            <p style="font-size: 12px; color: #616161; margin: 0;">{{ $deliveryPartner->partner_id }}</p>
                        </div>
                        @if($deliveryPartner->is_active)
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                        @else
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">Inactive</span>
                        @endif
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Email</span>
                        <span style="font-weight: 500; color: #303030;">{{ $deliveryPartner->user->email }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Phone</span>
                        <span style="font-weight: 500; color: #303030;">{{ $deliveryPartner->phone ?? '-' }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Vehicle</span>
                        <span style="font-weight: 500; color: #303030;">{{ ucfirst($deliveryPartner->vehicle_type) }}</span>
                    </div>
                    @if($deliveryPartner->vehicle_number)
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Vehicle No.</span>
                        <span style="font-family: monospace; font-weight: 500; color: #303030;">{{ $deliveryPartner->vehicle_number }}</span>
                    </div>
                    @endif
                    @if($deliveryPartner->license_number)
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">License</span>
                        <span style="font-weight: 500; color: #303030;">{{ $deliveryPartner->license_number }}</span>
                    </div>
                    @endif
                    @if($deliveryPartner->company_name)
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Company</span>
                        <span style="font-weight: 500; color: #303030;">{{ $deliveryPartner->company_name }}</span>
                    </div>
                    @endif
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Joined</span>
                        <span style="font-weight: 500; color: #303030;">{{ $deliveryPartner->created_at->format('M d, Y') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: #616161;">Verification</span>
                        @php
                            $vBadgeStyle = match($deliveryPartner->verification_status) {
                                'verified' => 'background: #cdfee1; color: #1a7a2e;',
                                'rejected' => 'background: #ffe0db; color: #b71c00;',
                                default => 'background: #fff3cd; color: #8a6d00;',
                            };
                        @endphp
                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; {{ $vBadgeStyle }}">{{ ucfirst($deliveryPartner->verification_status) }}</span>
                    </div>
                    @if($deliveryPartner->verified_at)
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Verified On</span>
                        <span style="font-weight: 500; color: #303030;">{{ $deliveryPartner->verified_at->format('M d, Y') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Documents -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Documents</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: #616161;">ID Proof</span>
                        @if($deliveryPartner->id_proof)
                            <a href="{{ asset('storage/' . $deliveryPartner->id_proof) }}" target="_blank" style="color: #005bd3; font-weight: 500; font-size: 12px; text-decoration: none;">View</a>
                        @else
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">Not Uploaded</span>
                        @endif
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: #616161;">Driving License</span>
                        @if($deliveryPartner->license_document)
                            <a href="{{ asset('storage/' . $deliveryPartner->license_document) }}" target="_blank" style="color: #005bd3; font-weight: 500; font-size: 12px; text-decoration: none;">View</a>
                        @else
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">Not Uploaded</span>
                        @endif
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                        <span style="color: #616161;">Address Proof</span>
                        @if($deliveryPartner->address_proof)
                            <a href="{{ asset('storage/' . $deliveryPartner->address_proof) }}" target="_blank" style="color: #005bd3; font-weight: 500; font-size: 12px; text-decoration: none;">View</a>
                        @else
                            <span style="font-size: 12px; color: #616161;">Not uploaded</span>
                        @endif
                    </div>
                    @if($deliveryPartner->verification_note)
                        <div style="padding-top: 0.5rem; border-top: 1px solid #e3e3e3;">
                            <p style="font-size: 12px; color: #616161; font-weight: 500; margin: 0 0 0.25rem 0;">Verification Note</p>
                            <p style="font-size: 13px; color: #303030; margin: 0;">{{ $deliveryPartner->verification_note }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Stats -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Delivery Stats</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Active Deliveries</span>
                        <span style="font-weight: 600; color: #b98900;">{{ $stats['active'] }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Completed</span>
                        <span style="font-weight: 600; color: #1a7a2e;">{{ $stats['delivered'] }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Total Assigned</span>
                        <span style="font-weight: 600; color: #303030;">{{ $stats['total'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders (right column) -->
        <div>
            <div class="card" style="overflow: hidden;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Assigned Orders</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f6f6f7;">
                                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Order</th>
                                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Customer</th>
                                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Status</th>
                                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Total</th>
                                <th style="padding: 0.75rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                <tr style="border-bottom: 1px solid #e3e3e3;">
                                    <td style="padding: 0.75rem 1rem;">
                                        <a href="{{ route('admin.orders.show', $order) }}" style="font-family: monospace; font-size: 13px; font-weight: 500; color: #005bd3; text-decoration: none;">
                                            {{ $order->order_number }}
                                        </a>
                                    </td>
                                    <td style="padding: 0.75rem 1rem; font-size: 13px; color: #616161;">{{ $order->user->full_name ?? '-' }}</td>
                                    <td style="padding: 0.75rem 1rem;">
                                        @php
                                            $badgeStyle = match($order->status) {
                                                'delivered' => 'background: #cdfee1; color: #1a7a2e;',
                                                'shipped', 'out_for_delivery' => 'background: #fff3cd; color: #8a6d00;',
                                                'cancelled' => 'background: #ffe0db; color: #b71c00;',
                                                default => 'background: #d4edfc; color: #0064a4;',
                                            };
                                        @endphp
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; {{ $badgeStyle }}">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                                    </td>
                                    <td style="padding: 0.75rem 1rem; font-size: 13px; font-weight: 500; color: #303030;">@price($order->total)</td>
                                    <td style="padding: 0.75rem 1rem; font-size: 13px; color: #616161;">{{ $order->created_at->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="padding: 2rem 1rem; text-align: center; font-size: 13px; color: #616161;">No orders assigned yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($orders->hasPages())
                    <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.admin>
