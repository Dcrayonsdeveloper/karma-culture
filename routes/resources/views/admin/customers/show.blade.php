<x-layouts.admin>
    <x-slot name="title">{{ $customer->full_name }}</x-slot>

    {{-- Back link / breadcrumb --}}
        <a href="{{ route('admin.customers.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none; margin-bottom: 0.25rem;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" style="flex-shrink: 0;">
                <path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Customers
        </a>

        {{-- Page header --}}
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; margin-top: 0.25rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <h1 style="font-size: 20px; font-weight: 600; color: #303030; margin: 0; line-height: 1.4;">{{ $customer->full_name }}</h1>
                @if($customer->is_active)
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-neutral">Inactive</span>
                @endif
            </div>
            <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-secondary" style="font-size: 13px; display: inline-flex; align-items: center; gap: 0.375rem;">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="none" style="flex-shrink: 0;">
                    <path d="M14.846 1.403a2.012 2.012 0 012.845 2.845l-9.9 9.9-3.795.95.95-3.795 9.9-9.9z" stroke="#616161" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Edit
            </a>
        </div>

        {{-- Two-column layout --}}
        <div style="display: grid; grid-template-columns: 1fr 340px; gap: 1rem; align-items: start;">

            {{-- ========== MAIN COLUMN ========== --}}
            <div style="display: flex; flex-direction: column; gap: 1rem;">

                {{-- Customer Info Card --}}
                <div class="card" style="border: 1px solid #e3e3e3; border-radius: 0.75rem; background: #fff; overflow: hidden;">
                    <div style="padding: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.875rem; margin-bottom: 1rem;">
                            {{-- Avatar --}}
                            <div style="width: 52px; height: 52px; border-radius: 50%; background: #e0f0ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span style="font-size: 20px; font-weight: 600; color: #005bd3; line-height: 1;">{{ substr($customer->first_name, 0, 1) }}</span>
                            </div>
                            <div>
                                <p style="font-size: 14px; font-weight: 600; color: #303030; margin: 0 0 0.125rem 0; line-height: 1.4;">{{ $customer->full_name }}</p>
                                <p style="font-size: 13px; color: #616161; margin: 0; line-height: 1.4;">{{ $customer->email }}</p>
                                @if($customer->phone)
                                    <p style="font-size: 13px; color: #616161; margin: 0.125rem 0 0 0; line-height: 1.4;">{{ $customer->phone }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Stats row --}}
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; background: #f6f6f7; border-radius: 0.5rem; overflow: hidden;">
                            <div style="text-align: center; padding: 0.875rem 0.5rem; border-right: 1px solid #e3e3e3;">
                                <p style="font-size: 20px; font-weight: 600; color: #303030; margin: 0; line-height: 1.3;">{{ $stats['total_orders'] }}</p>
                                <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Total Orders</p>
                            </div>
                            <div style="text-align: center; padding: 0.875rem 0.5rem; border-right: 1px solid #e3e3e3;">
                                <p style="font-size: 20px; font-weight: 600; color: #303030; margin: 0; line-height: 1.3;">@price($stats['total_spent'])</p>
                                <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Total Spent</p>
                            </div>
                            <div style="text-align: center; padding: 0.875rem 0.5rem;">
                                <p style="font-size: 20px; font-weight: 600; color: #303030; margin: 0; line-height: 1.3;">@price($stats['avg_order_value'])</p>
                                <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Avg. Order Value</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Recent Orders Card --}}
                <div class="card" style="border: 1px solid #e3e3e3; border-radius: 0.75rem; background: #fff; overflow: hidden;">
                    {{-- Card header --}}
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Recent Orders</h2>
                        <a href="{{ route('admin.customers.orders', $customer) }}" style="font-size: 13px; color: #005bd3; text-decoration: none;">View all</a>
                    </div>

                    {{-- Orders table --}}
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <thead>
                                <tr style="background: #f6f6f7;">
                                    <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase; letter-spacing: 0.02em; border-bottom: 1px solid #e3e3e3;">Order</th>
                                    <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase; letter-spacing: 0.02em; border-bottom: 1px solid #e3e3e3;">Date</th>
                                    <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase; letter-spacing: 0.02em; border-bottom: 1px solid #e3e3e3;">Status</th>
                                    <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase; letter-spacing: 0.02em; border-bottom: 1px solid #e3e3e3;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    <tr style="border-bottom: 1px solid #f1f1f1;">
                                        <td style="padding: 0.625rem 1rem;">
                                            <a href="{{ route('admin.orders.show', $order) }}" style="font-weight: 500; color: #005bd3; text-decoration: none;">
                                                {{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td style="padding: 0.625rem 1rem; color: #616161;">{{ $order->created_at->format('M d, Y') }}</td>
                                        <td style="padding: 0.625rem 1rem;">
                                            @if($order->status === 'completed')
                                                <span style="display: inline-block; padding: 0.1rem 0.5rem; font-size: 12px; font-weight: 500; border-radius: 1rem; background: #cdfee1; color: #1a7a2e;">{{ ucfirst($order->status) }}</span>
                                            @elseif($order->status === 'pending')
                                                <span style="display: inline-block; padding: 0.1rem 0.5rem; font-size: 12px; font-weight: 500; border-radius: 1rem; background: #fff3cd; color: #8a6d00;">{{ ucfirst($order->status) }}</span>
                                            @elseif($order->status === 'cancelled' || $order->status === 'failed')
                                                <span style="display: inline-block; padding: 0.1rem 0.5rem; font-size: 12px; font-weight: 500; border-radius: 1rem; background: #ffe5e5; color: #d72c0d;">{{ ucfirst($order->status) }}</span>
                                            @else
                                                <span style="display: inline-block; padding: 0.1rem 0.5rem; font-size: 12px; font-weight: 500; border-radius: 1rem; background: #e0f0ff; color: #005bd3;">{{ ucfirst($order->status) }}</span>
                                            @endif
                                        </td>
                                        <td style="padding: 0.625rem 1rem; text-align: right; font-weight: 500; color: #303030;">@price($order->total)</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="padding: 2rem 1rem; text-align: center; color: #616161; font-size: 13px;">
                                            <svg width="24" height="24" viewBox="0 0 20 20" fill="none" style="margin: 0 auto 0.5rem auto; display: block;">
                                                <path d="M4 4h12l1 9H3L4 4z" stroke="#c9cccf" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M3 13h14v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2z" stroke="#c9cccf" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            No orders yet
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            {{-- ========== SIDEBAR ========== --}}
            <div style="display: flex; flex-direction: column; gap: 1rem;">

                {{-- Account Details Card --}}
                <div class="card" style="border: 1px solid #e3e3e3; border-radius: 0.75rem; background: #fff; overflow: hidden;">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Account Details</h2>
                    </div>
                    <div style="padding: 1rem;">
                        <dl style="margin: 0; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.375rem 0; border-bottom: 1px solid #f1f1f1;">
                                <dt style="color: #616161;">Member since</dt>
                                <dd style="margin: 0; font-weight: 500; color: #303030;">{{ $customer->created_at->format('M d, Y') }}</dd>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.375rem 0; border-bottom: 1px solid #f1f1f1;">
                                <dt style="color: #616161;">Last login</dt>
                                <dd style="margin: 0; font-weight: 500; color: #303030;">{{ $customer->last_login_at?->diffForHumans() ?? 'Never' }}</dd>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.375rem 0;">
                                <dt style="color: #616161;">Email verified</dt>
                                <dd style="margin: 0;">
                                    @if($customer->email_verified_at)
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-weight: 500; color: #1a7a2e;">
                                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none"><path d="M6 10l3 3 5-5" stroke="#1a7a2e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            Verified
                                        </span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-weight: 500; color: #b98900;">
                                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="7" stroke="#b98900" stroke-width="1.5"/><path d="M10 7v3m0 3h.01" stroke="#b98900" stroke-width="1.5" stroke-linecap="round"/></svg>
                                            Unverified
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- Addresses Card --}}
                <div class="card" style="border: 1px solid #e3e3e3; border-radius: 0.75rem; background: #fff; overflow: hidden;">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Saved Addresses</h2>
                    </div>
                    <div style="padding: 1rem;">
                        @if($customer->addresses && $customer->addresses->count())
                            <div style="display: flex; flex-direction: column; gap: 0.625rem;">
                                @foreach($customer->addresses as $address)
                                    <div style="padding: 0.75rem; background: #f6f6f7; border-radius: 0.5rem; font-size: 13px; border: 1px solid #e3e3e3;">
                                        @if($address->is_default)
                                            <span style="display: inline-block; padding: 0.1rem 0.5rem; font-size: 11px; font-weight: 500; border-radius: 1rem; background: #e0f0ff; color: #005bd3; margin-bottom: 0.375rem;">Default</span>
                                        @endif
                                        <p style="font-weight: 500; color: #303030; margin: 0 0 0.125rem 0;">{{ $address->name }}</p>
                                        <p style="color: #616161; margin: 0; line-height: 1.5;">{{ $address->address_line1 }}</p>
                                        @if($address->address_line2)
                                            <p style="color: #616161; margin: 0; line-height: 1.5;">{{ $address->address_line2 }}</p>
                                        @endif
                                        <p style="color: #616161; margin: 0; line-height: 1.5;">{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</p>
                                        <p style="color: #616161; margin: 0; line-height: 1.5;">{{ $address->country }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p style="font-size: 13px; color: #616161; margin: 0; text-align: center; padding: 0.5rem 0;">No saved addresses</p>
                        @endif
                    </div>
                </div>

                {{-- Actions Card --}}
                <div class="card" style="border: 1px solid #e3e3e3; border-radius: 0.75rem; background: #fff; overflow: hidden;">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Actions</h2>
                    </div>
                    <div style="padding: 1rem;">
                        <form action="{{ route('admin.customers.toggle-status', $customer) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @if($customer->is_active)
                                <button type="submit" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem; width: 100%; padding: 0.5rem 0.75rem; font-size: 13px; font-weight: 500; color: #d72c0d; background: #fff; border: 1px solid #e3e3e3; border-radius: 0.5rem; cursor: pointer; line-height: 1.4;">
                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" style="flex-shrink: 0;">
                                        <circle cx="10" cy="10" r="7" stroke="#d72c0d" stroke-width="1.5"/>
                                        <path d="M13 7l-6 6M7 7l6 6" stroke="#d72c0d" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                    Deactivate Account
                                </button>
                            @else
                                <button type="submit" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem; width: 100%; padding: 0.5rem 0.75rem; font-size: 13px; font-weight: 500; color: #1a7a2e; background: #fff; border: 1px solid #e3e3e3; border-radius: 0.5rem; cursor: pointer; line-height: 1.4;">
                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="none" style="flex-shrink: 0;">
                                        <circle cx="10" cy="10" r="7" stroke="#1a7a2e" stroke-width="1.5"/>
                                        <path d="M7 10l2 2 4-4" stroke="#1a7a2e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    Activate Account
                                </button>
                            @endif
                        </form>
                    </div>
                </div>

            </div>
        </div>
</x-layouts.admin>
