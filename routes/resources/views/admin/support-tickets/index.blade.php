<x-layouts.admin>
    <x-slot name="title">Support Tickets</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Support Tickets</h1>
        </div>
    </x-slot>

    {{-- Stats --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Total</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Open</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ number_format($stats['open']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Answered</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['answered']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Closed</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #616161;">{{ number_format($stats['closed']) }}</div>
        </div>
    </div>

    {{-- Card with tabs + search + table --}}
    <div style="background: white; border-radius: 0.75rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">

        {{-- Tabs --}}
        <div style="display: flex; border-bottom: 1px solid #e3e3e3; padding: 0 1rem;">
            @php
                $statusTab = request('status', 'all');
                $tabItems = [
                    'all' => 'All',
                    'open' => 'Open',
                    'answered' => 'Answered',
                    'closed' => 'Closed',
                ];
            @endphp
            @foreach($tabItems as $key => $label)
                <a href="{{ route('admin.support-tickets.index', array_merge(request()->except('status', 'page'), $key !== 'all' ? ['status' => $key] : [])) }}"
                   style="padding: 0.75rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ $statusTab === $key ? '#303030' : 'transparent' }}; color: {{ $statusTab === $key ? '#303030' : '#616161' }}; margin-bottom: -1px;"
                   onmouseover="if('{{ $statusTab }}' !== '{{ $key }}') this.style.color='#303030'"
                   onmouseout="if('{{ $statusTab }}' !== '{{ $key }}') this.style.color='#616161'">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.support-tickets.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('priority'))
                    <input type="hidden" name="priority" value="{{ request('priority') }}">
                @endif
                <svg style="width: 16px; height: 16px; color: #616161; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by ID, subject, customer..."
                       style="flex: 1; border: none; outline: none; font-size: 13px; color: #303030; background: transparent;">
                {{-- Priority filter inline --}}
                <select name="priority" onchange="this.form.submit()"
                        style="border: 1px solid #c9cccf; border-radius: 0.5rem; padding: 0.25rem 0.5rem; font-size: 12px; color: #303030; background: white; cursor: pointer;">
                    <option value="">All Priority</option>
                    <option value="low" @selected(request('priority') === 'low')>Low</option>
                    <option value="normal" @selected(request('priority') === 'normal')>Normal</option>
                    <option value="high" @selected(request('priority') === 'high')>High</option>
                </select>
                @if(request()->hasAny(['search', 'priority']))
                    <a href="{{ route('admin.support-tickets.index', request()->only('status')) }}" style="color: #616161; font-size: 12px; text-decoration: none; white-space: nowrap;"
                       onmouseover="this.style.color='#303030'" onmouseout="this.style.color='#616161'">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">ID</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Customer</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Subject</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Category</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Priority</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Date</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr style="border-bottom: 1px solid #e3e3e3; cursor: pointer;"
                            onmouseover="this.style.background='#f6f6f7'"
                            onmouseout="this.style.background='transparent'"
                            onclick="window.location='{{ route('admin.support-tickets.show', $ticket) }}'">
                            <td style="padding: 0.625rem 1rem; color: #616161;">#{{ $ticket->id }}</td>
                            <td style="padding: 0.625rem 1rem;">
                                <div style="font-weight: 500; color: #303030;">{{ $ticket->user->full_name }}</div>
                                <div style="font-size: 12px; color: #616161;">{{ $ticket->user->email }}</div>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #303030;">
                                {{ Str::limit($ticket->subject, 40) }}
                            </td>
                            <td style="padding: 0.625rem 1rem; font-size: 12px; color: #616161; text-transform: capitalize;">
                                {{ $ticket->category }}
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @switch($ticket->priority)
                                    @case('high')
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">High</span>
                                        @break
                                    @case('normal')
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #e0f0ff; color: #005bd3;">Normal</span>
                                        @break
                                    @case('low')
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #f1f1f1; color: #616161;">Low</span>
                                        @break
                                @endswitch
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @switch($ticket->status)
                                    @case('open')
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #fff3cd; color: #8a6d00;">Open</span>
                                        @break
                                    @case('answered')
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Answered</span>
                                        @break
                                    @case('closed')
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #f1f1f1; color: #616161;">Closed</span>
                                        @break
                                @endswitch
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161; font-size: 12px;">
                                {{ $ticket->created_at->format('M d, Y') }}
                                <div>{{ $ticket->created_at->format('h:i A') }}</div>
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;" onclick="event.stopPropagation()">
                                <a href="{{ route('admin.support-tickets.show', $ticket) }}" style="color: #005bd3; font-size: 12px; font-weight: 500; text-decoration: none;"
                                   onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <svg style="width: 3rem; height: 3rem; color: #c9cccf; margin-bottom: 0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                    </svg>
                                    <p style="font-weight: 500; color: #303030; margin-bottom: 0.25rem;">No support tickets found</p>
                                    <p style="font-size: 13px; color: #616161;">Tickets will appear here when customers submit them.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tickets->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
