<x-layouts.admin>
    <x-slot name="title">{{ $seller->store_name }}</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.sellers.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Sellers
        </a>
    </div>
    <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">{{ $seller->store_name }}</h1>
            <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">{{ $seller->user->email ?? 'N/A' }}</p>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <a href="{{ route('admin.sellers.products', $seller) }}" class="btn btn-secondary" style="font-size: 13px;">Products</a>
            <a href="{{ route('admin.sellers.payouts', $seller) }}" class="btn btn-secondary" style="font-size: 13px;">Payouts</a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        <!-- Main Info -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Stats -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 0.75rem;">
                <div class="card" style="padding: 1rem; display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: #d4edfc; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: #0064a4;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p style="font-size: 1.25rem; font-weight: 700; color: #303030; margin: 0;">{{ number_format($stats['total_products']) }}</p>
                        <p style="font-size: 12px; color: #616161; margin: 0;">Products</p>
                    </div>
                </div>
                <div class="card" style="padding: 1rem; display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: #d4edfc; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: #0064a4;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div>
                        <p style="font-size: 1.25rem; font-weight: 700; color: #303030; margin: 0;">{{ number_format($stats['total_orders']) }}</p>
                        <p style="font-size: 12px; color: #616161; margin: 0;">Orders</p>
                    </div>
                </div>
                <div class="card" style="padding: 1rem; display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: #cdfee1; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: #1a7a2e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p style="font-size: 1.25rem; font-weight: 700; color: #1a7a2e; margin: 0;">@price($stats['total_revenue'])</p>
                        <p style="font-size: 12px; color: #616161; margin: 0;">Revenue</p>
                    </div>
                </div>
                <div class="card" style="padding: 1rem; display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: #fff3cd; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: #b98900;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p style="font-size: 1.25rem; font-weight: 700; color: #b98900; margin: 0;">@price($stats['pending_payouts'])</p>
                        <p style="font-size: 12px; color: #616161; margin: 0;">Pending Payouts</p>
                    </div>
                </div>
            </div>

            <!-- Seller Details Form -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Seller Details</h2>
                </div>
                <form action="{{ route('admin.sellers.update', $seller) }}" method="POST" style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    @csrf
                    @method('PUT')

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Store Name</label>
                            <input type="text" name="store_name" value="{{ old('store_name', $seller->store_name) }}" required
                                   class="form-input" style="width: 100%;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Business Name</label>
                            <input type="text" name="business_name" value="{{ old('business_name', $seller->business_name) }}"
                                   class="form-input" style="width: 100%;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Commission Rate (%)</label>
                            <input type="number" name="commission_rate" value="{{ old('commission_rate', $seller->commission_rate ?? 15) }}"
                                   min="0" max="100" step="0.1" required
                                   class="form-input" style="width: 100%;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Status</label>
                            <select name="status" required class="form-input" style="width: 100%;">
                                <option value="pending" @selected($seller->status === 'pending')>Pending</option>
                                <option value="approved" @selected($seller->status === 'approved')>Approved</option>
                                <option value="suspended" @selected($seller->status === 'suspended')>Suspended</option>
                                <option value="rejected" @selected($seller->status === 'rejected')>Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Recent Products -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Recent Products</h2>
                    <a href="{{ route('admin.sellers.products', $seller) }}" style="font-size: 13px; color: #005bd3; text-decoration: none;">View All</a>
                </div>
                <div>
                    @forelse($recentProducts as $product)
                        <div style="padding: 0.75rem 1rem; display: flex; align-items: center; gap: 0.75rem; {{ !$loop->last ? 'border-bottom: 1px solid #e3e3e3;' : '' }}">
                            <div style="width: 3rem; height: 3rem; background: #f6f6f7; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">
                                @if($product->image)
                                    <img src="{{ $product->image }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0.5rem;">
                                @else
                                    <svg style="width: 1.5rem; height: 1.5rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                @endif
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $product->name }}</p>
                                <p style="font-size: 13px; color: #616161; margin: 0;">@price($product->price)</p>
                            </div>
                            @if($product->is_active)
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                            @else
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ebebeb; color: #616161;">Inactive</span>
                            @endif
                        </div>
                    @empty
                        <div style="padding: 1rem; text-align: center; font-size: 13px; color: #616161;">No products yet</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Status Card -->
            <div class="card" style="padding: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Status</h3>
                    @switch($seller->status)
                        @case('approved')
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Approved</span>
                            @break
                        @case('pending')
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fff3cd; color: #8a6d00;">Pending</span>
                            @break
                        @case('suspended')
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">Suspended</span>
                            @break
                        @case('rejected')
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ebebeb; color: #616161;">Rejected</span>
                            @break
                    @endswitch
                </div>

                @if($seller->status === 'pending')
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <form action="{{ route('admin.sellers.approve', $seller) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Approve Seller</button>
                        </form>
                        <button x-data @click="$dispatch('open-reject-modal')" style="font-size: 13px; font-weight: 500; color: #d72c0d; cursor: pointer; background: none; border: 1px solid #e3e3e3; padding: 0.5rem; border-radius: 0.5rem; width: 100%;">Reject</button>
                    </div>
                @elseif($seller->status === 'approved')
                    <button x-data @click="$dispatch('open-suspend-modal')" style="font-size: 13px; font-weight: 500; color: #d72c0d; cursor: pointer; background: none; border: 1px solid #e3e3e3; padding: 0.5rem; border-radius: 0.5rem; width: 100%;">Suspend Seller</button>
                @elseif($seller->status === 'suspended')
                    <form action="{{ route('admin.sellers.approve', $seller) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Reactivate Seller</button>
                    </form>
                @endif
            </div>

            <!-- Contact Info -->
            <div class="card" style="padding: 1.25rem;">
                <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Contact Information</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Owner</span>
                        <span style="font-weight: 500; color: #303030;">{{ $seller->user->name ?? 'N/A' }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Email</span>
                        <span style="font-weight: 500; color: #303030;">{{ $seller->user->email ?? 'N/A' }}</span>
                    </div>
                    @if($seller->phone)
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Phone</span>
                            <span style="font-weight: 500; color: #303030;">{{ $seller->phone }}</span>
                        </div>
                    @endif
                    @if($seller->address)
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Address</span>
                            <span style="font-weight: 500; color: #303030; text-align: right;">{{ $seller->address }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Payout Info -->
            <div class="card" style="padding: 1.25rem;">
                <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Payout Information</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Method</span>
                        <span style="font-weight: 500; color: #303030;">{{ ucfirst(str_replace('_', ' ', $seller->payout_method ?? 'Not set')) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Available Balance</span>
                        <span style="font-weight: 500; color: #1a7a2e;">@price($seller->available_balance ?? 0)</span>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card" style="padding: 1.25rem;">
                <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Timeline</h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 13px;">
                        <div style="width: 0.5rem; height: 0.5rem; background: #e3e3e3; border-radius: 50%;"></div>
                        <div>
                            <p style="color: #303030; margin: 0;">Joined</p>
                            <p style="color: #616161; margin: 0;">{{ $seller->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                    @if($seller->approved_at)
                        <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 13px;">
                            <div style="width: 0.5rem; height: 0.5rem; background: #1a7a2e; border-radius: 50%;"></div>
                            <div>
                                <p style="color: #303030; margin: 0;">Approved</p>
                                <p style="color: #616161; margin: 0;">{{ $seller->approved_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                    @endif
                    @if($seller->suspended_at)
                        <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 13px;">
                            <div style="width: 0.5rem; height: 0.5rem; background: #d72c0d; border-radius: 50%;"></div>
                            <div>
                                <p style="color: #303030; margin: 0;">Suspended</p>
                                <p style="color: #616161; margin: 0;">{{ $seller->suspended_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div x-data="{ open: false }" @open-reject-modal.window="open = true">
        <div x-show="open" x-cloak style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center;">
            <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5);" @click="open = false"></div>
            <div style="position: relative; background: white; border-radius: 0.75rem; box-shadow: 0 20px 60px rgba(0,0,0,0.15); width: 100%; max-width: 28rem; margin: 0 1rem; padding: 1.5rem;">
                <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Reject Seller</h3>
                <form action="{{ route('admin.sellers.reject', $seller) }}" method="POST">
                    @csrf
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Reason</label>
                        <textarea name="rejection_reason" rows="4" required
                                  class="form-input" style="width: 100%;" placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" @click="open = false" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background: #d72c0d; border-color: #d72c0d;">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Suspend Modal -->
    <div x-data="{ open: false }" @open-suspend-modal.window="open = true">
        <div x-show="open" x-cloak style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center;">
            <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5);" @click="open = false"></div>
            <div style="position: relative; background: white; border-radius: 0.75rem; box-shadow: 0 20px 60px rgba(0,0,0,0.15); width: 100%; max-width: 28rem; margin: 0 1rem; padding: 1.5rem;">
                <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Suspend Seller</h3>
                <form action="{{ route('admin.sellers.suspend', $seller) }}" method="POST">
                    @csrf
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Reason</label>
                        <textarea name="suspension_reason" rows="4" required
                                  class="form-input" style="width: 100%;" placeholder="Please provide a reason for suspension..."></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" @click="open = false" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background: #d72c0d; border-color: #d72c0d;">Suspend</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
