<x-layouts.app>
    <x-slot name="title">Notifications</x-slot>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            @include('account.partials.sidebar')

            <div class="flex-1">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-neutral-900">Notifications</h1>
                </div>

                <div class="space-y-3">
                    @forelse($notifications as $notification)
                        <div class="card p-4 flex items-start gap-4 {{ $notification->read_at ? 'opacity-60' : '' }}">
                            <div class="shrink-0 mt-1">
                                @if(!$notification->read_at)
                                    <span class="block w-2.5 h-2.5 rounded-full bg-primary-500"></span>
                                @else
                                    <span class="block w-2.5 h-2.5 rounded-full bg-neutral-300"></span>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-neutral-900">{{ $notification->data['message'] ?? $notification->data['title'] ?? 'Notification' }}</p>
                                @if(!empty($notification->data['description']))
                                    <p class="text-sm text-neutral-600 mt-1">{{ $notification->data['description'] }}</p>
                                @endif
                                <p class="text-xs text-neutral-600 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="card p-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <h3 class="text-lg font-medium text-neutral-900 mb-2">No notifications</h3>
                            <p class="text-neutral-600">You're all caught up! Check back later for updates.</p>
                        </div>
                    @endforelse
                </div>

                @if($notifications->hasPages())
                    <div class="mt-6">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
