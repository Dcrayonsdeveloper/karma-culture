<x-layouts.seller>
    <x-slot name="title">Conversation</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('seller.messages.index') }}" class="hover:text-primary-600">Messages</a>
        <span>/</span>
        <span>{{ $conversation->user->first_name ?? '' }} {{ $conversation->user->last_name ?? '' }}</span>
    </div>

    <div class="max-w-3xl">
        <!-- Header -->
        <div class="card p-4 mb-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                        <span class="font-medium text-primary-600">
                            {{ substr($conversation->user->first_name ?? '?', 0, 1) }}
                        </span>
                    </div>
                    <div>
                        <p class="font-medium text-neutral-900">
                            {{ $conversation->user->first_name ?? '' }} {{ $conversation->user->last_name ?? '' }}
                        </p>
                        @if($conversation->subject)
                            <p class="text-sm text-neutral-600">{{ $conversation->subject }}</p>
                        @endif
                    </div>
                </div>
                <span class="badge {{ $conversation->status === 'open' ? 'badge-success' : 'badge-neutral' }}">
                    {{ ucfirst($conversation->status ?? 'open') }}
                </span>
            </div>
        </div>

        <!-- Messages -->
        <div class="card mb-4">
            <div class="p-4 space-y-4 max-h-[500px] overflow-y-auto">
                @forelse($conversation->messages as $message)
                    <div class="flex {{ $message->sender_type === 'seller' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[75%] {{ $message->sender_type === 'seller' ? 'bg-primary-600 text-white' : 'bg-neutral-100 text-neutral-900' }} rounded-lg px-4 py-3">
                            <p class="text-sm">{{ $message->content }}</p>
                            <p class="text-xs mt-1 {{ $message->sender_type === 'seller' ? 'text-primary-200' : 'text-neutral-600' }}">
                                {{ $message->created_at->format('M d, H:i') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-neutral-600 py-8">No messages in this conversation yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Reply Form -->
        <div class="card p-4">
            <form action="{{ route('seller.messages.reply', $conversation) }}" method="POST" class="flex gap-3">
                @csrf
                <div class="flex-1">
                    <textarea name="message" rows="2" required
                              class="form-input w-full @error('message') border-error-300 @enderror"
                              placeholder="Type your reply...">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary self-end">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</x-layouts.seller>
