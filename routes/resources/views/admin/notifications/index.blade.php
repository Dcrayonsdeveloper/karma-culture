<x-layouts.admin>
    <x-slot name="title">Notifications</x-slot>

    <x-slot name="header">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <h1 style="margin: 0; font-size: 1.25rem; font-weight: 600; color: #303030;">Notifications</h1>
        </div>
    </x-slot>

    {{-- Single Card containing all notifications --}}
    <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; overflow: hidden;">

        {{-- Results info bar --}}
        @if($notifications->total() > 0)
            <div style="padding: 0.625rem 1rem; border-bottom: 1px solid #e3e3e3; background: #f6f6f7;">
                <p style="font-size: 12px; color: #616161; margin: 0;">
                    Showing <span style="font-weight: 600; color: #303030;">{{ $notifications->firstItem() }}</span>&ndash;<span style="font-weight: 600; color: #303030;">{{ $notifications->lastItem() }}</span> of <span style="font-weight: 600; color: #303030;">{{ $notifications->total() }}</span> notifications
                </p>
            </div>
        @endif

        {{-- Notification List --}}
        @forelse($notifications as $notification)
            <div style="padding: 0.875rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: flex-start; gap: 0.75rem;{{ !$notification->is_read ? ' border-left: 3px solid #005bd3; background: #fafbff;' : '' }}">
                {{-- Icon Circle --}}
                <div style="flex-shrink: 0; margin-top: 0.125rem;">
                    @switch($notification->type)
                        @case('order')
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #e0f0ff; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1rem; height: 1rem; color: #005bd3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            @break
                        @case('payment')
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #cdfee1; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1rem; height: 1rem; color: #1a7a2e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            @break
                        @case('review')
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #fff3cd; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1rem; height: 1rem; color: #8a6d00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </div>
                            @break
                        @case('stock')
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #ffe0db; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1rem; height: 1rem; color: #b71c00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                            @break
                        @default
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1rem; height: 1rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </div>
                    @endswitch
                </div>

                {{-- Content --}}
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 0.375rem;">
                        <p style="margin: 0; font-size: 13px; font-weight: 500; color: #303030;">{{ $notification->title }}</p>
                        @if(!$notification->is_read)
                            <span style="display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #005bd3; flex-shrink: 0;"></span>
                        @endif
                    </div>
                    @if($notification->content)
                        <p style="margin: 0.25rem 0 0 0; font-size: 12px; color: #616161; line-height: 1.4;">{{ $notification->content }}</p>
                    @endif
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.375rem;">
                        <span style="font-size: 11px; color: #616161;">{{ $notification->created_at->diffForHumans() }}</span>
                        <span style="display: inline-block; padding: 0.0625rem 0.375rem; font-size: 10px; font-weight: 600; border-radius: 1rem; background: #e0f0ff; color: #005bd3;">{{ $notification->channel }}</span>
                        <span style="display: inline-block; padding: 0.0625rem 0.375rem; font-size: 10px; font-weight: 600; border-radius: 1rem; background: #fff3cd; color: #8a6d00;">{{ $notification->type }}</span>
                    </div>
                </div>
            </div>
        @empty
            {{-- Empty State --}}
            <div style="padding: 4rem 1rem; text-align: center;">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                    <div style="width: 3rem; height: 3rem; background: #f6f6f7; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 1.5rem; height: 1.5rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">No notifications yet</p>
                    <p style="font-size: 12px; color: #616161; margin: 0;">Notifications will appear here when there is activity.</p>
                </div>
            </div>
        @endforelse

        {{-- Pagination --}}
        @if($notifications->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
