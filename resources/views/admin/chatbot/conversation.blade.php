<x-layouts.admin>
    <x-slot name="title">Conversation</x-slot>

<div style="max-width: 820px;">

    <a href="{{ route('admin.chatbot.analytics') }}" style="font-size: 13px; color: #005bd3; text-decoration: none;">&larr; Chat Analytics</a>

    <div style="display: flex; align-items: baseline; gap: 0.75rem; margin: 0.5rem 0 1rem;">
        <h1 style="font-size: 20px; font-weight: 600; color: #303030; margin: 0;">Conversation #{{ $conversation->id }}</h1>
        @if($conversation->last_intent === 'handoff')
            <span class="badge" style="background: #fde8e6; color: #d72c0d;">Needs a human</span>
        @elseif($conversation->is_lead)
            <span class="badge" style="background: #e3f5e9; color: #0a7d3f;">Lead</span>
        @endif
    </div>

    <div class="card" style="margin-bottom: 1rem;">
        <div style="padding: 0.75rem 1rem; display: flex; flex-wrap: wrap; gap: 1.5rem; font-size: 13px;">
            <div>
                <span style="color: #616161;">Customer:</span>
                <strong style="color: #303030;">{{ $conversation->user?->full_name ?? 'Guest' }}</strong>
            </div>
            <div>
                <span style="color: #616161;">Messages:</span>
                <strong style="color: #303030;">{{ $conversation->message_count }}</strong>
            </div>
            <div>
                <span style="color: #616161;">Last activity:</span>
                <strong style="color: #303030;">{{ $conversation->last_message_at?->diffForHumans() ?? '—' }}</strong>
            </div>
            @if($conversation->lead)
                <div>
                    <span style="color: #616161;">Contact:</span>
                    <strong style="color: #303030;">{{ $conversation->lead->email ?: $conversation->lead->phone }}</strong>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
            @foreach($conversation->messages as $m)
                @php $isCustomer = $m->role === 'user'; @endphp
                <div style="display: flex; {{ $isCustomer ? 'justify-content: flex-end' : 'justify-content: flex-start' }};">
                    <div style="max-width: 78%; padding: 0.6rem 0.85rem; border-radius: 0.75rem; font-size: 13px; line-height: 1.5;
                                {{ $isCustomer
                                    ? 'background: #8C5C34; color: #fff; border-bottom-right-radius: 0.25rem;'
                                    : 'background: #f4f4f4; color: #303030; border-bottom-left-radius: 0.25rem;' }}">
                        {!! nl2br(e($m->content)) !!}
                        @if(! $isCustomer && $m->product_ids)
                            <div style="margin-top: 0.4rem; font-size: 11px; opacity: 0.75;">
                                Showed {{ count($m->product_ids) }} {{ \Illuminate\Support\Str::plural('product', count($m->product_ids)) }}
                                @if($m->response_ms) &middot; {{ number_format($m->response_ms / 1000, 1) }}s @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
</x-layouts.admin>
