<x-layouts.admin>
    <x-slot name="title">Chat Analytics</x-slot>

<div style="max-width: 1200px;">

    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
        <div>
            <h1 style="font-size: 20px; font-weight: 600; color: #303030; margin: 0;">Chat Analytics</h1>
            <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">What customers ask the shopping assistant, and what it leads to.</p>
        </div>
        <form method="GET" action="{{ route('admin.chatbot.analytics') }}">
            <select name="days" onchange="this.form.submit()" class="form-select" style="font-size: 13px;">
                @foreach($ranges as $value => $label)
                    <option value="{{ $value }}" @selected($days === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Headline numbers --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem;">
        @php
            $cards = [
                ['Conversations', number_format($stats['conversations']), '#303030'],
                ['Questions asked', number_format($stats['questions']), '#303030'],
                ['Engaged (2+ turns)', number_format($stats['engaged']), '#0064a4'],
                ['Product clicks', number_format($stats['clicks']), '#0064a4'],
                ['Leads captured', number_format($stats['leads']), '#0a7d3f'],
                ['Needs a human', number_format($stats['handoffs']), $stats['handoffs'] > 0 ? '#d72c0d' : '#616161'],
            ];
        @endphp
        @foreach($cards as [$label, $value, $colour])
            <div class="card" style="padding: 0.9rem 1rem;">
                <p style="font-size: 12px; color: #616161; margin: 0 0 0.25rem 0;">{{ $label }}</p>
                <p style="font-size: 22px; font-weight: 600; color: {{ $colour }}; margin: 0;">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    @if($stats['conversations'] === 0)
        <div class="card" style="padding: 2.5rem; text-align: center;">
            <p style="font-size: 14px; font-weight: 500; color: #303030; margin: 0 0 0.25rem 0;">No conversations yet</p>
            <p style="font-size: 13px; color: #616161; margin: 0;">
                Numbers appear here as soon as customers start chatting. Nothing is recorded from before the assistant went live.
            </p>
        </div>
    @else

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1rem;">

        {{-- What people ask about --}}
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">What customers ask about</h2>
            </div>
            <div style="padding: 1rem;">
                @forelse($topQuestionWords as $theme => $count)
                    @php $pct = $stats['questions'] > 0 ? round(($count / $stats['questions']) * 100) : 0; @endphp
                    <div style="margin-bottom: 0.65rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px; color: #303030; margin-bottom: 3px;">
                            <span>{{ $theme }}</span>
                            <span style="color: #616161;">{{ $count }}</span>
                        </div>
                        <div style="height: 6px; background: #f1f1f1; border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: {{ min(100, $pct) }}%; background: #8C5C34;"></div>
                        </div>
                    </div>
                @empty
                    <p style="font-size: 13px; color: #616161; margin: 0;">No recognised topics yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Products the assistant showed --}}
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Products customers ask about</h2>
            </div>
            <div style="padding: 0.5rem 1rem 1rem;">
                @forelse($shownProducts as $p)
                    <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.45rem 0; border-bottom: 1px solid #f4f4f4; font-size: 13px;">
                        <span style="color: #303030; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $p['name'] }}</span>
                        <span style="color: #616161; flex-shrink: 0;">{{ $p['count'] }}×</span>
                    </div>
                @empty
                    <p style="font-size: 13px; color: #616161; margin: 0.5rem 0 0 0;">No products surfaced yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Clicks --}}
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Recommendation clicks</h2>
                <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Suggestions customers actually opened</p>
            </div>
            <div style="padding: 0.5rem 1rem 1rem;">
                @forelse($clickedProducts as $c)
                    <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.45rem 0; border-bottom: 1px solid #f4f4f4; font-size: 13px;">
                        <span style="color: #303030; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $c->product?->name ?? 'Deleted product' }}</span>
                        <span style="color: #0064a4; flex-shrink: 0;">{{ $c->clicks }}</span>
                    </div>
                @empty
                    <p style="font-size: 13px; color: #616161; margin: 0.5rem 0 0 0;">No clicks yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Leads --}}
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Leads from chat</h2>
                    <a href="{{ route('admin.chatbot.leads') }}" style="font-size:12px; color:#005bd3; text-decoration:none;">View all</a>
                </div>
            </div>
            <div style="padding: 0.5rem 1rem 1rem;">
                @forelse($recentLeads as $lead)
                    <div style="padding: 0.5rem 0; border-bottom: 1px solid #f4f4f4;">
                        <div style="font-size: 13px; color: #303030;">{{ $lead->email ?: $lead->phone }}</div>
                        <div style="font-size: 12px; color: #616161;">
                            {{ ucfirst($lead->stage) }} &middot; {{ $lead->created_at->diffForHumans() }}
                            @if($lead->tags) &middot; {{ implode(', ', array_slice((array) $lead->tags, 0, 2)) }} @endif
                        </div>
                    </div>
                @empty
                    <p style="font-size: 13px; color: #616161; margin: 0.5rem 0 0 0;">No leads captured yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Conversations a human should pick up --}}
    @if($needsHuman->isNotEmpty())
        <div class="card" style="margin-top: 1rem; border-left: 3px solid #d72c0d;">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Waiting on a human</h2>
                <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">The assistant could not resolve these</p>
            </div>
            <div style="padding: 0.5rem 1rem 1rem;">
                @foreach($needsHuman as $c)
                    <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.5rem 0; border-bottom: 1px solid #f4f4f4; font-size: 13px;">
                        <span style="color: #303030;">
                            {{ $c->user?->full_name ?? 'Guest' }}
                            <span style="color: #616161;">&middot; {{ $c->message_count }} messages</span>
                        </span>
                        <a href="{{ route('admin.chatbot.conversation', $c) }}" style="color: #005bd3; text-decoration: none; flex-shrink: 0;">Read</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Latest questions verbatim --}}
    <div class="card" style="margin-top: 1rem;">
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Latest questions</h2>
            <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">In the customer's own words &mdash; useful for spotting gaps in your product pages</p>
        </div>
        <div style="padding: 0.5rem 1rem 1rem;">
            @foreach($recentQuestions as $q)
                <div style="padding: 0.45rem 0; border-bottom: 1px solid #f4f4f4; font-size: 13px; color: #303030;">
                    &ldquo;{{ \Illuminate\Support\Str::limit($q, 160) }}&rdquo;
                </div>
            @endforeach
        </div>
    </div>

    <p style="font-size: 12px; color: #616161; margin-top: 1rem;">
        Average reply time: {{ $stats['avg_response_ms'] > 0 ? number_format($stats['avg_response_ms'] / 1000, 1) . 's' : '—' }}
        &middot; {{ number_format($stats['signed_in']) }} of {{ number_format($stats['conversations']) }} conversations were from signed-in customers
    </p>
    @endif
</div>
</x-layouts.admin>
