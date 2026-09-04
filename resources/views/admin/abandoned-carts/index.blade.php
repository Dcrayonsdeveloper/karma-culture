<x-layouts.admin>
    <x-slot name="title">Abandoned Carts</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Abandoned Carts</h1>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <a href="{{ route('admin.abandoned-carts.export', request()->query()) }}" class="btn btn-secondary btn-sm">Export CSV</a>
                <a href="{{ route('admin.abandoned-carts.settings') }}" class="btn btn-secondary btn-sm">Settings</a>
                <form action="{{ route('admin.abandoned-carts.scan') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Scan now</button>
                </form>
            </div>
        </div>
    </x-slot>

    <x-admin.form-errors />

    {{-- Stats strip. repeat(6, 1fr) is one of the widths the responsive rules
         in app.css know how to collapse; an unusual count falls straight to a
         single column on a phone. --}}
    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Open carts</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['open']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Value at risk</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #b98900;">@price($stats['open_value'])</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Today</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #005bd3;">{{ number_format($stats['today']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">This week</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #005bd3;">{{ number_format($stats['this_week']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Recovered</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['recovered']) }} <span style="font-size: 13px; font-weight: 500; color: #616161;">({{ $stats['recovery_rate'] }}%)</span></p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Recovered revenue</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">@price($stats['recovered_revenue'])</p>
        </div>
    </div>

    @php
        $tabs = [
            '' => 'All',
            'recent' => 'Recently abandoned',
            'pending' => 'Recovery pending',
            'reminder_sent' => 'Recovery attempted',
            'contacted' => 'Contacted',
            'recovered' => 'Recovered',
            'expired' => 'Expired',
            'archived' => 'Archived',
        ];
        $activeStatus = request('status', '');
        $filterKeys = ['search', 'status', 'from', 'to', 'min_total', 'max_total', 'min_items', 'customer', 'product'];
        $advancedKeys = ['from', 'to', 'min_total', 'max_total', 'min_items', 'customer', 'product'];
        $advancedCount = count(array_filter(request()->only($advancedKeys), fn ($v) => $v !== null && $v !== ''));
    @endphp

    <div class="card" x-data="{ showFilters: false, selected: [] }">
        {{-- Tabs --}}
        <div style="border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; overflow-x: auto;">
            @foreach($tabs as $key => $label)
                @php $isActive = $activeStatus === $key; @endphp
                <a href="{{ route('admin.abandoned-carts.index', ($key === '' ? [] : ['status' => $key]) + request()->except('status', 'page')) }}"
                   style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; white-space: nowrap; border-bottom: 2px solid {{ $isActive ? '#303030' : 'transparent' }}; color: {{ $isActive ? '#303030' : '#616161' }}; margin-bottom: -1px;">
                    {{ $label }}
                    <span style="font-size: 11px; color: #999;">{{ number_format($counts[$key === '' ? 'all' : $key] ?? 0) }}</span>
                </a>
            @endforeach
        </div>

        {{-- Search + filter bar --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; flex-wrap: wrap;">
            <form action="{{ route('admin.abandoned-carts.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                @foreach(array_diff($filterKeys, ['search']) as $carry)
                    @if(request()->filled($carry))<input type="hidden" name="{{ $carry }}" value="{{ request($carry) }}">@endif
                @endforeach
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999; width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search customer, email, phone or cart ID"
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>
            {{-- The count is built in PHP rather than with an inline @if.
                 Blade only compiles a directive at a non-word boundary, so
                 "Filters@if(...)" leaves the @if as literal text and then
                 compiles the matching @endif on its own - a parse error in the
                 compiled view, which only shows up when the page is rendered. --}}
            <button type="button" @click="showFilters = !showFilters" class="btn btn-secondary btn-sm">
                {{ $advancedCount > 0 ? "Filters ({$advancedCount})" : 'Filters' }}
            </button>
            @if(request()->hasAny($filterKeys))
                <a href="{{ route('admin.abandoned-carts.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
            @endif
        </div>

        {{-- Advanced filters. Starts closed regardless of the query string -
             AdminFilterPanelTest pins that, because a drawer that auto-opened
             pushed the table off the first screen on every filtered visit. --}}
        <div x-show="showFilters" x-cloak x-transition style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; background: #fafafa;">
            <form action="{{ route('admin.abandoned-carts.index') }}" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: 0.75rem; align-items: end;">
                @if(request()->filled('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                @if(request()->filled('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                <div>
                    <label class="form-label" style="font-size: 12px;">Abandoned from</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" style="font-size: 12px;">Abandoned to</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" style="font-size: 12px;">Min cart value</label>
                    <input type="number" step="0.01" min="0" name="min_total" value="{{ request('min_total') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" style="font-size: 12px;">Max cart value</label>
                    <input type="number" step="0.01" min="0" name="max_total" value="{{ request('max_total') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" style="font-size: 12px;">Min items</label>
                    <input type="number" min="1" name="min_items" value="{{ request('min_items') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" style="font-size: 12px;">Customer ID</label>
                    <input type="number" min="1" name="customer" value="{{ request('customer') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" style="font-size: 12px;">Product ID</label>
                    <input type="number" min="1" name="product" value="{{ request('product') }}" class="form-input">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">Apply</button>
                </div>
            </form>
        </div>

        {{-- Bulk actions. Native submit with one hidden ids[] per row and a
             named submit button, which is the Newsletter pattern - the Products
             one posts a JSON string and loses the button's name. --}}
        {{-- x-show sits on the wrapper, never on the flex row itself. Alpine
             shows an element by calling removeProperty('display'), so an
             element that declares its own inline `display: flex` loses that
             layout the first time it is revealed. --}}
        <div x-show="selected.length > 0" x-cloak
             style="padding: 0.625rem 1rem; border-bottom: 1px solid #e3e3e3; background: #f7f7f7;">
            <form action="{{ route('admin.abandoned-carts.bulk-action') }}" method="POST"
                  onsubmit="return confirm('Apply this action to the selected carts?')"
                  style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                @csrf
                <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                <span style="font-size: 13px; color: #616161;" x-text="selected.length + ' selected'"></span>
                <button type="submit" name="action" value="remind" class="btn btn-secondary btn-sm">Send reminder</button>
                <button type="submit" name="action" value="contacted" class="btn btn-secondary btn-sm">Mark contacted</button>
                <button type="submit" name="action" value="archive" class="btn btn-secondary btn-sm">Archive</button>
                <span style="font-size: 12px; color: #999;">Up to 25 at a time. Each reminder is a live send, so a full batch takes a moment.</span>
            </form>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 0.5rem 0.5rem 0.5rem 1rem; width: 2rem;">
                            <input type="checkbox"
                                   @change="selected = $event.target.checked ? {{ $carts->pluck('id')->toJson() }} : []"
                                   :checked="selected.length > 0 && selected.length === {{ $carts->count() }}"
                                   aria-label="Select all carts on this page">
                        </th>
                        <th style="text-align: left;">Cart</th>
                        <th style="text-align: left;">Customer</th>
                        <th style="text-align: right;">Items</th>
                        <th style="text-align: right;">Value</th>
                        <th style="text-align: left;">Abandoned</th>
                        <th style="text-align: left;">Cart</th>
                        <th style="text-align: left;">Recovery</th>
                        <th style="text-align: left; padding: 0.5rem 1rem 0.5rem 0.75rem;">Reminders</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($carts as $cart)
                        <tr style="cursor: pointer;"
                            onclick="if(!event.target.closest('input,button,a,form,label')) window.location='{{ route('admin.abandoned-carts.show', $cart) }}'">
                            <td style="padding: 0.625rem 0.5rem 0.625rem 1rem;">
                                <input type="checkbox" value="{{ $cart->id }}" x-model.number="selected" aria-label="Select cart {{ $cart->id }}">
                            </td>
                            <td style="padding: 0.625rem 0.75rem;">
                                <span style="font-size: 13px; font-weight: 500; color: #005bd3;">#{{ $cart->id }}</span>
                                <span style="font-size: 12px; color: #999; display: block;">Cart {{ $cart->cart_id }}</span>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #303030;">{{ $cart->customerName() }}</span>
                                <span style="font-size: 12px; color: #616161; display: block; overflow-wrap: anywhere;">
                                    {{ $cart->contactEmail() ?? $cart->contactPhone() ?? 'No contact details' }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <span style="font-size: 13px; color: #616161;">{{ $cart->item_count }}</span>
                            </td>
                            <td style="text-align: right;">
                                <span style="font-size: 13px; font-weight: 500; color: #303030;">@price($cart->total)</span>
                                <span style="font-size: 11px; color: #999; display: block;">{{ $cart->currency }}</span>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #303030;">{{ $cart->timeSinceAbandonment() }} ago</span>
                                <span style="font-size: 12px; color: #616161; display: block;">{{ $cart->abandoned_at->format('M d, Y') }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $cart->cartStatusBadgeClass() }}">{{ ucfirst($cart->cartStatus()) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $cart->badgeClass() }}">{{ \App\Models\AbandonedCart::statusLabel($cart->recovery_status) }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem 0.625rem 0.75rem;">
                                @if($cart->last_reminder_error)
                                    <span class="badge badge-error">Send failed</span>
                                @elseif($cart->reminder_count > 0)
                                    <span style="font-size: 13px; color: #303030;">{{ $cart->reminder_count }}</span>
                                    <span style="font-size: 12px; color: #616161; display: block;">{{ $cart->last_reminder_at?->diffForHumans() }}</span>
                                @else
                                    <span style="font-size: 13px; color: #999;">None</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; position: sticky; left: 1rem; max-width: calc(100vw - 4rem);">
                                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 1.25rem; height: 1.25rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/>
                                        </svg>
                                    </div>
                                    <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No abandoned carts found</h3>
                                    <p style="font-size: 13px; color: #616161;">
                                        @if(request()->hasAny($filterKeys))
                                            Try adjusting your filters to find what you're looking for.
                                        @else
                                            Carts appear here once they have sat untouched for {{ $stats['total'] === 0 ? 'the configured threshold' : 'a while' }}. Use "Scan now" to check immediately.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($carts->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                {{ $carts->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
