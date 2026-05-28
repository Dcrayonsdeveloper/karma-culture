<x-layouts.admin>
    <x-slot name="title">Newsletter Subscribers</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Newsletter</h1>
            <a href="{{ route('admin.newsletter.export') }}{{ request()->hasAny(['status']) ? '?' . request()->getQueryString() : '' }}"
               class="btn btn-secondary" style="font-size: 13px;">
                <svg style="width: 16px; height: 16px; margin-right: 6px; display: inline-block; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export CSV
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.625rem 1rem; background: #cdfee1; border: 1px solid #1a7a2e; border-radius: 0.5rem; font-size: 13px; color: #1a7a2e; margin-bottom: 1rem;">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Total Subscribers</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Active</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['active']) }}</div>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <div style="font-size: 12px; color: #616161;">Unsubscribed</div>
            <div style="font-size: 1.25rem; font-weight: 600; color: #616161;">{{ number_format($stats['inactive']) }}</div>
        </div>
    </div>

    {{-- Card with tabs + search + table --}}
    <div style="background: white; border-radius: 0.75rem; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;"
         x-data="{
             tab: '{{ request('status', 'all') }}',
             search: '{{ request('search', '') }}',
             selected: [],
             toggleAll(checked, ids) { this.selected = checked ? ids : []; },
             toggle(id) {
                 const idx = this.selected.indexOf(id);
                 idx === -1 ? this.selected.push(id) : this.selected.splice(idx, 1);
             },
             navigate() {
                 let url = '{{ route('admin.newsletter.index') }}';
                 let params = [];
                 if (this.tab !== 'all') params.push('status=' + this.tab);
                 if (this.search) params.push('search=' + encodeURIComponent(this.search));
                 if (params.length) url += '?' + params.join('&');
                 window.location.href = url;
             }
         }">

        {{-- Tabs --}}
        <div style="display: flex; border-bottom: 1px solid #e3e3e3; padding: 0 1rem;">
            @php
                $statusTab = request('status', 'all');
                $tabItems = [
                    'all' => 'All',
                    'active' => 'Active',
                    'inactive' => 'Unsubscribed',
                ];
            @endphp
            @foreach($tabItems as $key => $label)
                <a href="{{ route('admin.newsletter.index', array_merge(request()->except('status', 'page'), $key !== 'all' ? ['status' => $key] : [])) }}"
                   style="padding: 0.75rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ $statusTab === $key ? '#303030' : 'transparent' }}; color: {{ $statusTab === $key ? '#303030' : '#616161' }}; margin-bottom: -1px;"
                   onmouseover="if('{{ $statusTab }}' !== '{{ $key }}') this.style.color='#303030'"
                   onmouseout="if('{{ $statusTab }}' !== '{{ $key }}') this.style.color='#616161'">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
            <form action="{{ route('admin.newsletter.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <svg style="width: 16px; height: 16px; color: #616161; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search subscribers..."
                       style="flex: 1; border: none; outline: none; font-size: 13px; color: #303030; background: transparent;">
                @if(request('search'))
                    <a href="{{ route('admin.newsletter.index', request()->except('search', 'page')) }}" style="color: #616161; font-size: 12px; text-decoration: none; white-space: nowrap;"
                       onmouseover="this.style.color='#303030'" onmouseout="this.style.color='#616161'">Clear</a>
                @endif
            </form>
        </div>

        {{-- Bulk Actions Bar --}}
        <div x-show="selected.length > 0" x-cloak
             style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 1rem; background: #f1f1f1; border-bottom: 1px solid #e3e3e3;">
            <span style="font-size: 13px; font-weight: 500; color: #303030;">
                <span x-text="selected.length"></span> selected
            </span>
            <form method="POST" action="{{ route('admin.newsletter.bulk-action') }}"
                  @submit.prevent="$el.querySelector('input[name=ids]').value = JSON.stringify(selected); $el.submit()"
                  style="display: flex; align-items: center; gap: 0.5rem;">
                @csrf
                <input type="hidden" name="ids" value="">
                <button type="submit" name="action" value="activate" class="btn btn-sm btn-secondary" style="color: #1a7a2e;">Activate</button>
                <button type="submit" name="action" value="deactivate" class="btn btn-sm btn-secondary" style="color: #b98900;">Deactivate</button>
                <button type="submit" name="action" value="delete"
                        onclick="return confirm('Delete selected subscribers?')"
                        class="btn btn-sm btn-secondary" style="color: #d72c0d;">Delete</button>
            </form>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; width: 40px;">
                            @php $ids = $subscribers->pluck('id')->toArray(); @endphp
                            <input type="checkbox"
                                   @change="toggleAll($event.target.checked, {{ json_encode($ids) }})"
                                   :checked="selected.length === {{ count($ids) }} && {{ count($ids) }} > 0">
                        </th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Email</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Name</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Source</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-weight: 500; color: #616161; font-size: 12px;">Subscribed</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-weight: 500; color: #616161; font-size: 12px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $subscriber)
                        <tr style="border-bottom: 1px solid #e3e3e3; cursor: pointer;"
                            onmouseover="this.style.background='#f6f6f7'"
                            onmouseout="this.style.background='transparent'">
                            <td style="padding: 0.625rem 1rem;">
                                <input type="checkbox"
                                       :checked="selected.includes({{ $subscriber->id }})"
                                       @change="toggle({{ $subscriber->id }})">
                            </td>
                            <td style="padding: 0.625rem 1rem; font-weight: 500; color: #303030;">
                                {{ $subscriber->email }}
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">
                                {{ $subscriber->name ?? '—' }}
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #f1f1f1; color: #616161;">{{ ucfirst($subscriber->source) }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($subscriber->is_active)
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                                @else
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #f1f1f1; color: #616161;">Unsubscribed</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">
                                {{ ($subscriber->subscribed_at ?? $subscriber->created_at)->format('M d, Y') }}
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem;">
                                    <form action="{{ route('admin.newsletter.toggle-status', $subscriber) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn-icon"
                                                title="{{ $subscriber->is_active ? 'Unsubscribe' : 'Reactivate' }}">
                                            @if($subscriber->is_active)
                                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            @else
                                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.newsletter.destroy', $subscriber) }}" method="POST"
                                          onsubmit="return confirm('Remove this subscriber?')" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon" style="color: #d72c0d;" title="Delete"
                                                onmouseover="this.style.background='#ffe0db'"
                                                onmouseout="this.style.background='transparent'">
                                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 3rem; height: 3rem; background: #f1f1f1; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 1.5rem; height: 1.5rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <p style="font-weight: 500; color: #303030; margin-bottom: 0.25rem;">No subscribers found</p>
                                    <p style="font-size: 13px; color: #616161;">
                                        @if(request()->hasAny(['search', 'status', 'source']))
                                            No subscribers match your current filters.
                                        @else
                                            Subscribers will appear here once people sign up.
                                        @endif
                                    </p>
                                    @if(request()->hasAny(['search', 'status', 'source']))
                                        <a href="{{ route('admin.newsletter.index') }}" class="btn btn-secondary btn-sm" style="margin-top: 0.75rem;">Clear Filters</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subscribers->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $subscribers->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
