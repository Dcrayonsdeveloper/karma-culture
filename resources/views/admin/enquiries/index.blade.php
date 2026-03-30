<x-layouts.admin>
    <x-slot name="title">Enquiries</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Enquiries</h1>
        </div>
    </x-slot>

    {{-- Stats Row --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Total</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">New</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #d72c0d;">{{ number_format($stats['new']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Read</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #005bd3;">{{ number_format($stats['read']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Replied</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['replied']) }}</div>
        </div>
    </div>

    {{-- Single Card --}}
    <div class="card" style="overflow: hidden;">
        {{-- Tab Filters --}}
        <div style="display: flex; align-items: center; gap: 0; border-bottom: 1px solid #e3e3e3; padding: 0 1rem;">
            <a href="{{ route('admin.enquiries.index', request()->only('search')) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }};">
                All <span style="color: #616161; font-size: 12px;">({{ $stats['total'] }})</span>
            </a>
            <a href="{{ route('admin.enquiries.index', array_merge(request()->only('search'), ['status' => 'new'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'new' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'new' ? '#303030' : '#616161' }};">
                New <span style="color: #616161; font-size: 12px;">({{ $stats['new'] }})</span>
            </a>
            <a href="{{ route('admin.enquiries.index', array_merge(request()->only('search'), ['status' => 'read'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'read' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'read' ? '#303030' : '#616161' }};">
                Read <span style="color: #616161; font-size: 12px;">({{ $stats['read'] }})</span>
            </a>
            <a href="{{ route('admin.enquiries.index', array_merge(request()->only('search'), ['status' => 'replied'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'replied' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'replied' ? '#303030' : '#616161' }};">
                Replied <span style="color: #616161; font-size: 12px;">({{ $stats['replied'] }})</span>
            </a>
            <a href="{{ route('admin.enquiries.index', array_merge(request()->only('search'), ['status' => 'closed'])) }}"
               style="padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === 'closed' ? '#303030' : 'transparent' }}; color: {{ request('status') === 'closed' ? '#303030' : '#616161' }};">
                Closed
            </a>
        </div>

        {{-- Search Bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.enquiries.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email or subject..."
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem;">
                </div>
                @if(request('search'))
                    <a href="{{ route('admin.enquiries.index', request()->only('status')) }}" style="font-size: 13px; color: #616161; text-decoration: none; padding: 0.375rem 0.5rem;">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Sender</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Subject</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Date</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enquiries as $enquiry)
                        <tr style="cursor: default; border-bottom: 1px solid #e3e3e3; {{ !$enquiry->is_read ? 'background: #f0f7ff;' : '' }}"
                            onmouseover="this.style.backgroundColor='{{ !$enquiry->is_read ? '#e6f0fa' : '#f6f6f7' }}'"
                            onmouseout="this.style.backgroundColor='{{ !$enquiry->is_read ? '#f0f7ff' : 'transparent' }}'">
                            <td style="padding: 0.5rem 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    @unless($enquiry->is_read)
                                        <span style="width: 8px; height: 8px; background: #005bd3; border-radius: 50%; flex-shrink: 0;"></span>
                                    @endunless
                                    <div>
                                        <div style="font-weight: {{ !$enquiry->is_read ? '600' : '400' }}; color: #303030;">{{ $enquiry->name }}</div>
                                        <div style="font-size: 12px; color: #616161;">{{ $enquiry->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 0.5rem 1rem;">
                                <div style="font-weight: {{ !$enquiry->is_read ? '600' : '400' }}; color: #303030;">{{ Str::limit($enquiry->subject, 50) }}</div>
                                <div style="font-size: 12px; color: #616161; margin-top: 2px;">{{ Str::limit($enquiry->message, 60) }}</div>
                            </td>
                            <td style="padding: 0.5rem 1rem;">
                                @switch($enquiry->status)
                                    @case('new')
                                        <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">New</span>
                                        @break
                                    @case('read')
                                        <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 12px; font-weight: 500; background: #e0f0ff; color: #005bd3;">Read</span>
                                        @break
                                    @case('replied')
                                        <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Replied</span>
                                        @break
                                    @case('closed')
                                        <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 12px; font-weight: 500; background: #f1f1f1; color: #616161;">Closed</span>
                                        @break
                                @endswitch
                            </td>
                            <td style="padding: 0.5rem 1rem; color: #616161;">
                                {{ $enquiry->created_at->format('M d, Y') }}
                                <div style="font-size: 12px;">{{ $enquiry->created_at->format('h:i A') }}</div>
                            </td>
                            <td style="padding: 0.5rem 1rem; text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem;">
                                    <a href="{{ route('admin.enquiries.show', $enquiry) }}" title="View"
                                       style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 12px; font-weight: 500; color: #005bd3; text-decoration: none;"
                                       onmouseover="this.style.backgroundColor='#e0f0ff'" onmouseout="this.style.backgroundColor='transparent'">
                                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                    <form action="{{ route('admin.enquiries.toggle-read', $enquiry) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" title="{{ $enquiry->is_read ? 'Mark as unread' : 'Mark as read' }}"
                                                style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 12px; font-weight: 500; color: #616161; background: none; border: none; cursor: pointer;"
                                                onmouseover="this.style.backgroundColor='#f1f1f1'" onmouseout="this.style.backgroundColor='transparent'">
                                            @if($enquiry->is_read)
                                                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                </svg>
                                                Unread
                                            @else
                                                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5"/>
                                                </svg>
                                                Read
                                            @endif
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.enquiries.destroy', $enquiry) }}" method="POST" style="display: inline;"
                                          onsubmit="return confirm('Delete this enquiry?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete"
                                                style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 12px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;"
                                                onmouseover="this.style.backgroundColor='#ffe0db'" onmouseout="this.style.backgroundColor='transparent'">
                                            <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 3rem 1rem; text-align: center; color: #616161;">
                                <svg style="width: 48px; height: 48px; margin: 0 auto 0.75rem; color: #c9cccf;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <p style="font-size: 13px;">No enquiries found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($enquiries->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $enquiries->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
