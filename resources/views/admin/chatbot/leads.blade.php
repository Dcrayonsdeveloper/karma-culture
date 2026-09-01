<x-layouts.admin>
    <x-slot name="title">Chat Leads</x-slot>

<div style="max-width: 1200px;">

    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
        <div>
            <h1 style="font-size: 20px; font-weight: 600; color: #303030; margin: 0;">Chat Leads</h1>
            <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">
                Every customer who has used the shopping assistant, what they asked, and what it showed them.
            </p>
        </div>
        <a href="{{ route('admin.chatbot.analytics') }}" class="btn btn-secondary" style="font-size: 13px;">Chat Analytics</a>
    </div>

    @if($conversations->isEmpty())
        <div class="card" style="padding: 2.5rem; text-align: center;">
            <p style="font-size: 14px; font-weight: 500; color: #303030; margin: 0 0 0.25rem 0;">No leads yet</p>
            <p style="font-size: 13px; color: #616161; margin: 0;">
                A customer becomes a lead the moment they sign in and ask the assistant something.
            </p>
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @foreach($conversations as $c)
                @php
                    // Everything the assistant surfaced in this conversation.
                    $shownIds = $c->messages->pluck('product_ids')->filter()->flatten()->unique();
                    $clicked = ($clicks[$c->id] ?? collect())->pluck('product_id')->unique();
                    $questions = $c->messages->where('role', 'user')->pluck('content');
                @endphp
                <div class="card" style="padding: 1rem;">

                    {{-- Who --}}
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <strong style="font-size: 14px; color: #303030;">{{ $c->user?->full_name ?? 'Deleted customer' }}</strong>
                                @if($c->last_intent === 'handoff')
                                    <span class="badge" style="background:#fde8e6; color:#d72c0d;">Needs a human</span>
                                @elseif($c->is_lead)
                                    <span class="badge" style="background:#e3f5e9; color:#0a7d3f;">Buying intent</span>
                                @endif
                            </div>
                            <div style="font-size: 12px; color: #616161; margin-top: 2px;">
                                {{ $c->user?->email ?? '—' }}
                                @if($c->user?->phone) &middot; {{ $c->user->phone }} @endif
                                &middot; {{ $c->message_count }} messages
                                &middot; {{ $c->last_message_at?->diffForHumans() ?? '—' }}
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                            @if($c->user)
                                <a href="{{ route('admin.customers.show', $c->user) }}" style="font-size: 12px; color: #005bd3; text-decoration: none;">Customer</a>
                            @endif
                            <a href="{{ route('admin.chatbot.conversation', $c) }}" style="font-size: 12px; color: #005bd3; text-decoration: none;">Read chat</a>
                        </div>
                    </div>

                    {{-- What they asked --}}
                    @if($questions->isNotEmpty())
                        <div style="margin-top: 0.75rem; padding-top: 0.6rem; border-top: 1px solid #f4f4f4;">
                            <p style="font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #8a8a8a; margin: 0 0 0.35rem 0;">Asked about</p>
                            @foreach($questions->take(3) as $q)
                                <p style="font-size: 13px; color: #303030; margin: 0 0 2px 0;">&ldquo;{{ \Illuminate\Support\Str::limit($q, 120) }}&rdquo;</p>
                            @endforeach
                            @if($questions->count() > 3)
                                <p style="font-size: 12px; color: #616161; margin: 2px 0 0 0;">+{{ $questions->count() - 3 }} more</p>
                            @endif
                        </div>
                    @endif

                    {{-- What the assistant showed --}}
                    @if($shownIds->isNotEmpty())
                        <div style="margin-top: 0.75rem; padding-top: 0.6rem; border-top: 1px solid #f4f4f4;">
                            <p style="font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #8a8a8a; margin: 0 0 0.4rem 0;">Products shown</p>
                            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                @foreach($shownIds as $pid)
                                    @php
                                        $p = $products[$pid] ?? null;
                                        if (! $p) { continue; }
                                        $sizes = $p->variants->where('is_active', true)->where('stock_quantity', '>', 0)
                                            ->map(fn ($v) => \App\Models\ProductVariant::sizeLabel($v->name))
                                            ->filter()->unique()->values();
                                        $colours = collect(data_get($p->attributes, 'Colours', []))
                                            ->map(fn ($col) => is_array($col) ? ($col['name'] ?? '') : $col)
                                            ->filter()->values();
                                    @endphp
                                    <div style="display: flex; align-items: baseline; gap: 0.5rem; flex-wrap: wrap; font-size: 13px;">
                                        <a href="{{ route('admin.products.edit', $p->id) }}" style="color: #303030; text-decoration: none; font-weight: 500;">{{ $p->name }}</a>
                                        <span style="color: #616161;">@price($p->price)</span>
                                        @if($sizes->isNotEmpty())
                                            <span style="font-size: 12px; color: #616161;">Sizes: {{ $sizes->join(', ') }}</span>
                                        @endif
                                        @if($colours->isNotEmpty())
                                            <span style="font-size: 12px; color: #616161;">Colours: {{ $colours->join(', ') }}</span>
                                        @endif
                                        @if($clicked->contains($p->id))
                                            <span class="badge" style="background:#e7f0ff; color:#0064a4;">Opened it</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div style="margin-top: 1rem;">
            {{ $conversations->links() }}
        </div>
    @endif
</div>
</x-layouts.admin>
