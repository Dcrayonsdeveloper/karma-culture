<x-layouts.admin>
    <x-slot name="title">Orders</x-slot>

    @php
        // What the two download links carry. Every date name is stripped out of
        // the raw query string and replaced by $window, the one the controller
        // resolved: the file is then cut exactly where the table is.
        //
        // Building the links from request()->except('page', 'format') alone was
        // not equivalent. http_build_query() drops a null, so on
        // ?from=&to=&date_from=2026-03-01&date_to=2026-03-31 - which is what
        // pressing Apply with an empty calendar on a legacy bookmark submits -
        // the empty pair vanished from the href, the old aliases survived it,
        // and the download came back cut to March while the page showed the
        // whole table.
        $kkExportParams = request()->except(['page', 'format', 'from', 'to', 'date_from', 'date_to']) + $window;
    @endphp

    <x-slot name="header">
        <div class="page-header">
            <h1>Orders</h1>
            {{-- The filters on screen ride along, minus ?page - an export is
                 never one page of the table - and minus ?format, which each
                 link sets for itself. --}}
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <a href="{{ route('admin.orders.export', $kkExportParams) }}"
                   class="btn btn-secondary btn-sm">Export CSV</a>
                <a href="{{ route('admin.orders.export', $kkExportParams + ['format' => 'xlsx']) }}"
                   class="btn btn-secondary btn-sm"
                   title="{{ \App\Http\Controllers\Admin\OrderController::xlsxRowCapNotice() }}">Export Excel</a>
            </div>
        </div>
    </x-slot>

    {{-- A filter this screen refuses - a status outside the enum, a page size
         outside the clamp, a window that reads backwards - throws and redirects
         back, and without this the page simply re-rendered unchanged with the
         search box empty and no reason given. --}}
    <x-admin.form-errors title="That filter could not be applied" />

    {{-- Stats row. These count the same orders the table below does: the
         filters apply, and only the status clause is left off, so a tile still
         says how many orders are in a status while a tab narrows the list to
         one of them. --}}
    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Total</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ number_format($stats['total']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Confirmed</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ number_format($stats['confirmed']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Processing</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #005bd3;">{{ number_format($stats['processing']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Shipped</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #7c3aed;">{{ number_format($stats['shipped']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Completed</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ number_format($stats['completed']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Cancelled</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #d72c0d;">{{ number_format($stats['cancelled']) }}</p>
        </div>
    </div>

    @php
        // Every parameter that narrows the table, named once. Three lists used
        // to be kept by hand - the search form's hidden inputs, the "Clear all"
        // test and the empty state's - and the first was already a filter
        // behind: it carried only ?status, so typing in the search box wiped
        // the payment and date filters the controller had been honouring all
        // along.
        $kkFilterKeys = ['search', 'status', 'payment_status', 'from', 'to', 'date_from', 'date_to'];

        // filled(), not hasAny(): the search box and the payment select post on
        // every submit whether or not they hold anything, so hasAny() said
        // "filters are applied" - and offered to clear them - after a search for
        // nothing at all.
        $kkHasFilters = collect($kkFilterKeys)->contains(fn ($kkKey) => request()->filled($kkKey));

        $kkPaymentStatuses = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            'partial_refund' => 'Partially refunded',
        ];
    @endphp

    {{-- Orders card --}}
    <div class="card">
        {{-- Tab filters --}}
        <div style="border-bottom: 1px solid #e3e3e3; display: flex; align-items: center;">
            <a href="{{ route('admin.orders.index', request()->except('status', 'page')) }}"
               style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ !request('status') ? '#303030' : 'transparent' }}; color: {{ !request('status') ? '#303030' : '#616161' }}; margin-bottom: -1px;">All</a>
            @foreach(['confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $st)
                <a href="{{ route('admin.orders.index', ['status' => $st] + request()->except('status', 'page')) }}"
                   style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ request('status') === $st ? '#303030' : 'transparent' }}; color: {{ request('status') === $st ? '#303030' : '#616161' }}; margin-bottom: -1px;">{{ ucfirst($st) }}</a>
            @endforeach
        </div>

        {{-- Search + Filter bar. Two forms and a link side by side, hence
             flex-wrap: a narrow admin window drops the calendar onto its own
             row rather than squashing the search box to nothing. --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; flex-wrap: wrap;">
            <form action="{{ route('admin.orders.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                {{-- Carry every filter this form does not own its own control
                     for. payment_status is deliberately absent from the list:
                     it has a <select> below, and sending it twice would put two
                     values in the query string. --}}
                @foreach(['status', 'from', 'to', 'date_from', 'date_to', 'per_page'] as $kkCarry)
                    @if(request()->filled($kkCarry))<input type="hidden" name="{{ $kkCarry }}" value="{{ request($kkCarry) }}">@endif
                @endforeach
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999; width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    {{-- maxlength matches the max:120 rule, so the common case
                         is caught in the box rather than by a redirect that
                         empties it. --}}
                    <input type="text" name="search" value="{{ request('search') }}" maxlength="120"
                           placeholder="Search orders"
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                {{-- The controller has always honoured ?payment_status and the
                     page has never had a way to set it, so "Clear all" was
                     offering to clear a filter nothing could apply. --}}
                <select name="payment_status" class="form-input" aria-label="Payment status"
                        style="font-size: 13px; padding: 0.375rem 0.5rem; width: auto;">
                    <option value="">All payments</option>
                    @foreach($kkPaymentStatuses as $kkValue => $kkLabel)
                        <option value="{{ $kkValue }}" @selected(request('payment_status') === $kkValue)>{{ $kkLabel }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>

            {{-- A SIBLING of the search form, never a child: the component
                 renders its own <form>, and a browser hoists a nested form's
                 fields into the outer one. --}}
            <x-admin.date-range-filter :action="route('admin.orders.index')" />

            @if($kkHasFilters)
                <a href="{{ route('admin.orders.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
            @endif
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 0.5rem 0.75rem 0.5rem 1rem;">Order</th>
                        <th style="text-align: left;">Date</th>
                        <th style="text-align: left;">Customer</th>
                        <th style="text-align: left;">Payment</th>
                        <th style="text-align: left;">Fulfillment</th>
                        <th style="text-align: right;">Items</th>
                        <th style="text-align: right; padding: 0.5rem 1rem 0.5rem 0.75rem;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr style="cursor: pointer;" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td style="padding: 0.625rem 0.75rem 0.625rem 1rem;">
                                <span style="font-size: 13px; font-weight: 500; color: #005bd3;">{{ $order->order_number }}</span>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #616161;">{{ $order->created_at->format('M d, Y') }}</span>
                            </td>
                            <td>
                                {{-- The accessor, so a guest order shows the
                                     name it was placed with rather than the
                                     word "Guest" - and so does the order of a
                                     customer who has since been deleted. --}}
                                <span style="font-size: 13px; color: #303030;">{{ $order->customer_name }}</span>
                            </td>
                            <td>
                                @php
                                    $paymentBadge = match($order->payment_status) {
                                        'paid' => 'badge-success',
                                        'pending' => 'badge-warning',
                                        'failed' => 'badge-error',
                                        'refunded', 'partial_refund' => 'badge-neutral',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                {{-- str_replace as well as ucfirst, or the
                                     partial_refund member reads
                                     "Partial_refund" - the fulfilment column
                                     below has always done it properly. --}}
                                <span class="badge {{ $paymentBadge }}">{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</span>
                            </td>
                            <td>
                                @php
                                    $statusBadge = match($order->status) {
                                        'delivered', 'completed' => 'badge-success',
                                        'confirmed' => 'badge-warning',
                                        'processing', 'packed' => 'badge-info',
                                        'shipped', 'out_for_delivery' => 'badge-info',
                                        'cancelled', 'returned' => 'badge-error',
                                        default => 'badge-neutral',
                                    };
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                            </td>
                            <td style="text-align: right;">
                                {{-- withCount() on the query, so this number
                                     costs one subquery rather than every
                                     OrderItem on the page. --}}
                                <span style="font-size: 13px; color: #616161;">{{ $order->items_count }} items</span>
                            </td>
                            <td style="text-align: right; padding: 0.625rem 1rem 0.625rem 0.75rem;">
                                <span style="font-size: 13px; font-weight: 500; color: #303030;">@price($order->total)</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; position: sticky; left: 1rem; max-width: calc(100vw - 4rem);">
                                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 1.25rem; height: 1.25rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                    </div>
                                    <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No orders found</h3>
                                    <p style="font-size: 13px; color: #616161;">
                                        @if($kkHasFilters)
                                            Try adjusting your filters to find what you're looking for.
                                        @else
                                            Orders will appear here when customers place them.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
