<x-layouts.seller>
    <x-slot name="title">Messages</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Messages</h1>
            <p class="text-neutral-600">Conversations with your customers</p>
        </div>
    </div>

    <div class="card overflow-hidden">
        @forelse($conversations as $conversation)
            <a href="{{ route('seller.messages.show', $conversation) }}"
               class="block p-4 hover:bg-neutral-50 border-b border-neutral-200 last:border-b-0 {{ $conversation->seller_unread_count > 0 ? 'bg-primary-50' : '' }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="font-medium text-primary-600">
                                {{ substr($conversation->user->first_name ?? '?', 0, 1) }}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-neutral-900">
                                    {{ $conversation->user->first_name ?? '' }} {{ $conversation->user->last_name ?? '' }}
                                </p>
                                @if($conversation->seller_unread_count > 0)
                                    <span class="bg-primary-600 text-white text-xs rounded-full px-2 py-0.5">
                                        {{ $conversation->seller_unread_count }}
                                    </span>
                                @endif
                            </div>
                            @if($conversation->subject)
                                <p class="text-sm text-neutral-700 font-medium">{{ $conversation->subject }}</p>
                            @endif
                            <p class="text-sm text-neutral-600 truncate">
                                {{ $conversation->latestMessage->content ?? 'No messages yet' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0 ml-4">
                        <p class="text-xs text-neutral-600">
                            {{ $conversation->last_message_at?->diffForHumans() ?? $conversation->created_at->diffForHumans() }}
                        </p>
                        <span class="badge mt-1 {{ $conversation->status === 'open' ? 'badge-success' : 'badge-neutral' }}">
                            {{ ucfirst($conversation->status ?? 'open') }}
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <div class="p-12 text-center">
                <svg class="w-12 h-12 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <h3 class="text-lg font-medium text-neutral-900 mb-1">No messages yet</h3>
                <p class="text-neutral-600">Customer conversations will appear here.</p>
            </div>
        @endforelse

        @if($conversations->hasPages())
            <div class="p-4 border-t border-neutral-200">
                {{ $conversations->links() }}
            </div>
        @endif
    </div>
</x-layouts.seller>
