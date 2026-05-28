<x-layouts.admin>
    <x-slot name="title">Enquiry #{{ $enquiry->id }}</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.enquiries.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Enquiries
        </a>
    </div>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Enquiry #{{ $enquiry->id }}</h1>
            <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">{{ $enquiry->created_at->format('M d, Y h:i A') }}</p>
        </div>
        <form action="{{ route('admin.enquiries.destroy', $enquiry) }}" method="POST"
              onsubmit="return confirm('Delete this enquiry?')">
            @csrf
            @method('DELETE')
            <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; cursor: pointer; background: none; border: none; padding: 0.5rem 0;">Delete</button>
        </form>
    </div>

    @if(session('success'))
        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #cdfee1; border: 1px solid #1a7a2e; border-radius: 0.5rem; font-size: 13px; color: #1a7a2e;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        <!-- Main Content -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Original Message -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">{{ $enquiry->subject }}</h2>
                </div>
                <div style="padding: 1rem;">
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                        <div style="width: 2rem; height: 2rem; background: #d4edfc; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="font-size: 11px; font-weight: 600; color: #0064a4;">{{ strtoupper(substr($enquiry->name, 0, 1)) }}</span>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <span style="font-size: 13px; font-weight: 500; color: #303030;">{{ $enquiry->name }}</span>
                                <span style="font-size: 12px; color: #616161;">{{ $enquiry->created_at->diffForHumans() }}</span>
                            </div>
                            <div style="font-size: 13px; color: #303030; line-height: 1.6;">
                                {!! nl2br(e($enquiry->message)) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Status & Notes -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Update Status</h2>
                </div>
                <form action="{{ route('admin.enquiries.status', $enquiry) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Status</label>
                            <select name="status" class="form-select" style="width: 100%;">
                                <option value="new" @selected($enquiry->status === 'new')>New</option>
                                <option value="read" @selected($enquiry->status === 'read')>Read</option>
                                <option value="replied" @selected($enquiry->status === 'replied')>Replied</option>
                                <option value="closed" @selected($enquiry->status === 'closed')>Closed</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Admin Notes <span style="color: #616161; font-weight: 400;">(internal only)</span></label>
                            <textarea name="admin_notes" rows="3" placeholder="Internal notes (not visible to customer)..."
                                      class="form-input" style="width: 100%;">{{ old('admin_notes', $enquiry->admin_notes) }}</textarea>
                        </div>
                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn btn-secondary" style="font-size: 13px;">Update Status</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Sender Details -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Sender Details</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 2.5rem; height: 2.5rem; background: #d4edfc; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="font-size: 13px; font-weight: 600; color: #0064a4;">{{ strtoupper(substr($enquiry->name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $enquiry->name }}</p>
                            <p style="font-size: 12px; color: #616161; margin: 0;">{{ $enquiry->email }}</p>
                        </div>
                    </div>

                    @if($enquiry->phone)
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 13px; color: #616161;">
                            <svg style="width: 1rem; height: 1rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <a href="tel:{{ $enquiry->phone }}" style="color: #005bd3; text-decoration: none;">{{ $enquiry->phone }}</a>
                        </div>
                    @endif

                    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 13px; color: #616161;">
                        <svg style="width: 1rem; height: 1rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:{{ $enquiry->email }}" style="color: #005bd3; text-decoration: none;">{{ $enquiry->email }}</a>
                    </div>
                </div>
            </div>

            <!-- Status Info -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Status</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Current Status</span>
                        @switch($enquiry->status)
                            @case('new')
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">New</span>
                                @break
                            @case('read')
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #d4edfc; color: #0064a4;">Read</span>
                                @break
                            @case('replied')
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Replied</span>
                                @break
                            @case('closed')
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ebebeb; color: #616161;">Closed</span>
                                @break
                        @endswitch
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px;">
                        <span style="color: #616161;">Received</span>
                        <span style="font-weight: 500; color: #303030;">{{ $enquiry->created_at->format('M d, Y') }}</span>
                    </div>
                    @if($enquiry->read_at)
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Read at</span>
                            <span style="font-weight: 500; color: #303030;">{{ $enquiry->read_at->format('M d, Y h:i A') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
