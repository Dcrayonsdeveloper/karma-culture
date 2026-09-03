<x-layouts.app>
    <x-slot name="title">Notifications</x-slot>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            @include('account.partials.sidebar')

            <div class="flex-1">
                {{-- flex-wrap + gap: on a narrow phone the title and the
                     Mark all as read button would otherwise share one
                     non-wrapping row and collide. --}}
                <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                    <h1 class="text-2xl font-bold text-neutral-900">Notifications</h1>

                    @if($unreadCount > 0)
                        <form action="{{ route('account.notifications.read-all') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-neutral-200 text-neutral-700 text-[13px] font-semibold rounded-lg hover:bg-neutral-50 hover:text-neutral-900 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Mark all as read
                            </button>
                        </form>
                    @endif
                </div>

                <div class="space-y-3">
                    @forelse($notifications as $notification)
                        {{-- The text lives in the title and content COLUMNS. This card
                             used to read data['message'] with data['title'] as a fallback,
                             but no writer in the app puts 'message' in data at all, and
                             only NotificationService::notify() puts 'title' there - so the
                             rows written by notifyInApp() (order_cancelled, return_<status>)
                             and the direct Notification::create() for ticket_reply rendered
                             as a card whose entire text was the literal word "Notification",
                             and even the rows that did carry data['title'] lost their body,
                             because the description came from a data key nothing writes.

                             The row is a link so the customer can reach the order, return
                             or ticket it is about; that route marks it read on the way
                             through. Unread state is keyed off is_read rather than read_at
                             to agree with the model's scopeUnread and markAsRead. --}}
                        <a href="{{ route('account.notifications.read', $notification) }}"
                           class="card p-4 flex items-start gap-4 hover:bg-neutral-50 transition-colors {{ $notification->is_read ? 'opacity-60' : '' }}">
                            <div class="shrink-0 mt-1">
                                @if(!$notification->is_read)
                                    <span class="block w-2.5 h-2.5 rounded-full bg-primary-500"></span>
                                @else
                                    <span class="block w-2.5 h-2.5 rounded-full bg-neutral-300"></span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-neutral-900 font-medium">{{ $notification->title }}</p>
                                @if(filled($notification->content))
                                    <p class="text-sm text-neutral-600 mt-1">{{ $notification->content }}</p>
                                @endif
                                <p class="text-xs text-neutral-600 mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                        </a>
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
