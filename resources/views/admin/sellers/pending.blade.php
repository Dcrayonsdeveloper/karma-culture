<x-layouts.admin>
    <x-slot name="title">Pending Sellers</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.sellers.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Sellers
        </a>
    </div>

    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Pending Approvals</h1>
    <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 1rem 0;">Review and approve new seller applications</p>

    @if($sellers->isEmpty())
        <div class="card" style="padding: 2rem; text-align: center;">
            <div style="width: 48px; height: 48px; background: #cdfee1; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
                <svg width="24" height="24" fill="none" stroke="#1a7a2e" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 style="font-size: 14px; font-weight: 600; color: #303030; margin: 0 0 0.25rem 0;">All caught up!</h3>
            <p style="font-size: 13px; color: #616161; margin: 0;">There are no pending seller applications to review.</p>
        </div>
    @else
        @if($sellers->total() > 0)
            <div class="card" style="margin-bottom: 1rem;">
                <div style="padding: 0.625rem 1rem;">
                    {{ $sellers->links('vendor.pagination.info-bar') }}
                </div>
            </div>
        @endif

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($sellers as $seller)
                <div class="card" x-data="{ showRejectModal: false }">
                    <div style="padding: 1rem;">
                        <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                            <div style="display: flex; align-items: flex-start; gap: 1rem;">
                                <div style="width: 48px; height: 48px; background: #e0f0ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <span style="font-size: 16px; font-weight: 500; color: #005bd3;">
                                        {{ strtoupper(substr($seller->store_name, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <h3 style="font-size: 14px; font-weight: 600; color: #303030; margin: 0;">{{ $seller->store_name }}</h3>
                                    <p style="font-size: 13px; color: #616161; margin: 0.125rem 0 0 0;">{{ $seller->user->name ?? 'N/A' }} &bull; {{ $seller->user->email ?? 'N/A' }}</p>
                                    <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">Applied {{ $seller->created_at->diffForHumans() }}</p>

                                    <div style="margin-top: 1rem; display: grid; grid-template-columns: repeat(4, auto); gap: 1rem;">
                                        @if($seller->business_name)
                                            <div>
                                                <p style="font-size: 12px; color: #616161; margin: 0;">Business Name</p>
                                                <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0.125rem 0 0 0;">{{ $seller->business_name }}</p>
                                            </div>
                                        @endif
                                        @if($seller->phone)
                                            <div>
                                                <p style="font-size: 12px; color: #616161; margin: 0;">Phone</p>
                                                <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0.125rem 0 0 0;">{{ $seller->phone }}</p>
                                            </div>
                                        @endif
                                        @if($seller->address)
                                            <div style="grid-column: span 2;">
                                                <p style="font-size: 12px; color: #616161; margin: 0;">Address</p>
                                                <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0.125rem 0 0 0;">{{ $seller->address }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    @if($seller->store_description)
                                        <div style="margin-top: 1rem;">
                                            <p style="font-size: 12px; color: #616161; margin: 0;">Store Description</p>
                                            <p style="font-size: 13px; color: #303030; margin: 0.25rem 0 0 0;">{{ Str::limit($seller->store_description, 200) }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                                <a href="{{ route('admin.sellers.show', $seller) }}" class="btn btn-secondary" style="font-size: 13px;">
                                    View Details
                                </a>
                                <form action="{{ route('admin.sellers.approve', $seller) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="font-size: 13px; background: #1a7a2e; border-color: #1a7a2e;">
                                        Approve
                                    </button>
                                </form>
                                <button @click="showRejectModal = true" class="btn btn-secondary" style="font-size: 13px; color: #d72c0d;">
                                    Reject
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Reject Modal -->
                    <div x-show="showRejectModal" x-cloak style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center;">
                        <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5);" @click="showRejectModal = false"></div>
                        <div style="position: relative; background: white; border-radius: 0.75rem; box-shadow: 0 20px 60px rgba(0,0,0,0.15); width: 100%; max-width: 28rem; margin: 0 1rem; padding: 1.5rem;">
                            <h3 style="font-size: 14px; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Reject Seller Application</h3>
                            <form action="{{ route('admin.sellers.reject', $seller) }}" method="POST">
                                @csrf
                                <div style="margin-bottom: 1rem;">
                                    <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Rejection Reason</label>
                                    <textarea name="rejection_reason" rows="4" required
                                              class="form-textarea" style="width: 100%; font-size: 13px;"
                                              placeholder="Please provide a reason for rejection..."></textarea>
                                </div>
                                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                                    <button type="button" @click="showRejectModal = false" class="btn btn-secondary">Cancel</button>
                                    <button type="submit" class="btn btn-primary" style="background: #d72c0d; border-color: #d72c0d;">Reject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($sellers->hasPages())
            <div style="margin-top: 1rem;">
                {{ $sellers->links() }}
            </div>
        @endif
    @endif
</x-layouts.admin>
