<x-layouts.admin>
    <x-slot name="title">Customers</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Customers</h1>
            {{-- Plain links, so neither of them raises a nesting question next
                 to the two forms in the filter bar. Carrying the live query
                 string is what stops an admin who has narrowed the table from
                 being handed the whole customer book. --}}
            @php
                // The filters currently on screen, carried into the download.
                // 'page' goes because a cursor from page 3 means nothing to a
                // file; 'format' goes so each link sets its own.
                $kkExportParams = request()->except(['page', 'format']);
            @endphp
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                {{-- No format on this one: anything that is not xlsx or excel
                     is a CSV, so the plain link needs nothing added. --}}
                <a href="{{ route('admin.customers.export', $kkExportParams) }}"
                   class="btn btn-secondary btn-sm">Export CSV</a>
                <a href="{{ route('admin.customers.export', array_merge($kkExportParams, ['format' => 'xlsx'])) }}"
                   class="btn btn-secondary btn-sm"
                   title="{{ $xlsxRowCapNotice }}">Export Excel</a>
            </div>
        </div>
    </x-slot>

    {{-- The filters are validated now, so a rejected window or an out-of-range
         per_page bounces back here with a reason rather than a wrong table. --}}
    <x-admin.form-errors title="That filter could not be applied" />

    {{-- Stats. Counted off the same filtered query as the table below, so a
         tile can never contradict the rows it sits above. --}}
    <div class="kk-stats-3" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            {{-- Two labels and two numbers, because they are two different
                 questions. "Customers matching" has to be the rows underneath -
                 status clause included - or ?status=inactive reads "Customers
                 matching 500" above twelve of them. --}}
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">{{ $filtersApplied ? 'Customers matching' : 'Total customers' }}</p>
            <p style="font-size: 1.5rem; font-weight: 600; color: #303030;">{{ number_format($filtersApplied ? $stats['matching'] : $stats['total']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Active</p>
            <p style="font-size: 1.5rem; font-weight: 600; color: #303030;">{{ number_format($stats['active']) }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">New this month</p>
            <p style="font-size: 1.5rem; font-weight: 600; color: #303030;">{{ number_format($stats['new_this_month']) }}</p>
        </div>
    </div>

    {{-- Customers card --}}
    <div class="card">
        {{-- Filter bar. Two forms sitting side by side, never one inside the
             other: the date-range component renders a form of its own, and a
             browser drops the fields of a nested one (which is what
             AdminFormNestingTest exists to catch). flex-wrap lets the second
             form drop to its own line on a phone instead of crushing the
             search box.

             The component is named in prose on purpose - a component tag in
             angle brackets is compiled even inside a comment, and the failure
             is a runtime "unable to locate a class or view" that a syntax
             check on the compiled file cannot see. --}}
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; flex-wrap: wrap;">
            <form action="{{ route('admin.customers.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1; flex-wrap: wrap;">
                {{-- The calendar window belongs to the sibling form, so it has
                     to ride through this one as hidden inputs. Without them,
                     pressing Search silently widened the table back out to
                     every customer ever - the same class of failure as an
                     export that ignores the filters on screen. `status` is a
                     real control below, so it is deliberately not carried here:
                     a hidden input and a <select> of the same name would submit
                     the field twice. --}}
                @foreach(['from', 'to', 'per_page'] as $carry)
                    @if(request()->filled($carry))
                        <input type="hidden" name="{{ $carry }}" value="{{ request($carry) }}">
                    @endif
                @endforeach

                <div style="position: relative; flex: 1; min-width: 12rem; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999; width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    {{-- maxlength matches the max:120 rule, so a pasted blob is
                         caught here rather than by a redirect that empties the
                         box. --}}
                    <input type="text" name="search" value="{{ request('search') }}" maxlength="120"
                           placeholder="Search name, email or phone"
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>

                {{-- ?status has been honoured by the controller since before this
                     screen had a control for it, and the "Clear all" link below
                     has always tested for it. --}}
                <select name="status" class="form-input" aria-label="Account status"
                        style="font-size: 13px; padding: 0.375rem 0.5rem; width: auto;">
                    <option value="">All customers</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>

                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>

            {{-- Sibling of the search form, never inside it. It carries search,
                 status and per_page through as hidden inputs of its own, so
                 applying a window does not drop them. --}}
            <x-admin.date-range-filter :action="route('admin.customers.index')" fromName="from" toName="to" />

            @if(request()->anyFilled(['search', 'status', 'from', 'to', 'per_page']))
                <a href="{{ route('admin.customers.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
            @endif

            {{-- Measured against the rows the download will actually carry, not
                 against the window total: warning an admin who has filtered to
                 forty rows that their spreadsheet will be cut at two thousand
                 teaches them to ignore the notice. --}}
            @if($stats['matching'] > $xlsxRowCap)
                {{-- Said before the download rather than inside it: an admin
                     should not find out about the ceiling from row 2,001 of a
                     spreadsheet they have already started adding up. --}}
                <p style="flex-basis: 100%; margin: 0.25rem 0 0 0; font-size: 12px; color: #616161;">{{ $xlsxRowCapNotice }}</p>
            @endif
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding-left: 1rem;">Customer name</th>
                        <th style="text-align: left;">Email</th>
                        {{-- Was headed "Location" while the cell underneath has
                             always rendered the phone number. Nothing on this
                             screen has ever shown where a customer is; the
                             export carries the real address. Renamed on the box
                             and here at the same time, independently - the
                             wording is prod's. --}}
                        <th style="text-align: left;">Phone no</th>
                        <th style="text-align: left;">Signed up</th>
                        <th style="text-align: right;">Orders</th>
                        <th style="text-align: right; padding-right: 1rem;">Amount spent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr style="cursor: pointer;" onclick="window.location='{{ route('admin.customers.show', $customer) }}'">
                            <td style="padding-left: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #e3e3e3; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span style="font-size: 11px; font-weight: 600; color: #616161;">{{ strtoupper(substr($customer->first_name, 0, 1)) }}</span>
                                    </div>
                                    {{-- A real link, not just link-blue text: the
                                         row's onclick is mouse-only, so this was
                                         the whole keyboard path to a customer
                                         record and there wasn't one. --}}
                                    <a href="{{ route('admin.customers.show', $customer) }}"
                                       style="font-size: 13px; font-weight: 500; color: #005bd3; text-decoration: none;">{{ $customer->full_name }}</a>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #616161;">{{ $customer->email }}</span>
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #616161;">{{ $customer->phone ?? '-' }}</span>
                            </td>
                            <td>
                                {{-- The axis the calendar filter cuts on. It was
                                     not on screen at all, so a narrowed table
                                     had nothing to show for its own filter. --}}
                                <span style="font-size: 13px; color: #616161; white-space: nowrap;">{{ $customer->created_at?->format('d M Y') ?? '-' }}</span>
                            </td>
                            <td style="text-align: right;">
                                <span style="font-size: 13px; color: #303030;">{{ $customer->orders_count }}</span>
                            </td>
                            <td style="text-align: right; padding-right: 1rem;">
                                <span style="font-size: 13px; color: #303030;">@price($customer->orders_sum_total ?? 0)</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; position: sticky; left: 0; max-width: calc(100vw - 4rem);">
                                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;">
                                        <svg style="width: 1.25rem; height: 1.25rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    @if(request()->anyFilled(['search', 'status', 'from', 'to']))
                                        <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No customers match these filters</h3>
                                        <p style="font-size: 13px; color: #616161;">Try a wider date range, or clear the filters above.</p>
                                    @else
                                        <h3 style="font-size: 15px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">No customers yet</h3>
                                        <p style="font-size: 13px; color: #616161;">Customers will appear here once they register.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: center;">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
    @push('styles')
    <style>
        /* Phones: the shared CSS turns the 3-tile strip into 2 columns; the odd
           last tile spans the row so no empty grey cell is left behind. */
        @media (max-width: 767.98px) {
            .kk-stats-3 > :last-child { grid-column: 1 / -1; }
        }
        /* Touch: the compact Search button reaches a 36px target (.btn-sm is
           excluded from the shared phone min-height rule). */
        @media (pointer: coarse) {
            .layout-admin main .btn-sm { min-height: 2.25rem; }
        }
    </style>
    @endpush
</x-layouts.admin>
