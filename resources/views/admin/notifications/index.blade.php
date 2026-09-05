<x-layouts.admin>
    <x-slot name="title">Notifications</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Notifications</h1>
            {{-- $unreadCount is the admin's whole unread set, not just this page, so
                 the button survives paging into a screen of already-read rows.
                 Rendered even at zero and hidden instead, because the poller can
                 take the count from 0 to 1 without a page load and the button
                 has to be able to arrive with it. --}}
            <form data-admin-read-all
                  action="{{ route('admin.notifications.read-all') }}"
                  method="POST"
                  style="{{ $unreadCount > 0 ? '' : 'display: none;' }}">
                @csrf
                <button type="submit" class="btn btn-secondary">Mark all as read</button>
            </form>
        </div>
    </x-slot>

    {{-- Single Card containing all notifications --}}
    <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; overflow: hidden;">

        {{-- Tab Filters. Only the Unread tab carries a count: the controller knows
             the unread total for certain, while an "All" total would only be right
             on the tab that is already showing it. The active tab's 2px border has
             to overlap the strip's 1px divider, hence margin-bottom: -1px. --}}
        @php
            $tabs = [
                ['filter' => null, 'label' => 'All', 'count' => null],
                ['filter' => 'unread', 'label' => 'Unread', 'count' => $unreadCount],
            ];
        @endphp
        <div style="display: flex; align-items: center; gap: 0; border-bottom: 1px solid #e3e3e3; padding: 0 1rem;">
            @foreach($tabs as $tab)
                @php $active = $filter === ($tab['filter'] ?? 'all'); @endphp
                <a href="{{ route('admin.notifications', $tab['filter'] ? array_merge(request()->only('per_page'), ['filter' => $tab['filter']]) : request()->only('per_page')) }}"
                   style="display: inline-flex; align-items: center; gap: 0.25rem; white-space: nowrap; padding: 0.625rem 0.75rem; margin-bottom: -1px; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: 2px solid {{ $active ? '#303030' : 'transparent' }}; color: {{ $active ? '#303030' : '#616161' }};">
                    {{ $tab['label'] }}
                    @if(!is_null($tab['count']))
                        {{-- The poller rewrites this from the same unread total
                             it puts on the bell, so the tab and the bell cannot
                             disagree between page loads. --}}
                        <span data-admin-unread-count style="color: #616161; font-size: 12px;">({{ $tab['count'] }})</span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Results info bar. Rendered even when there is nothing to count, and
             hidden instead, so that the first notification to arrive on a live
             page has a bar to appear in rather than having to wait for a reload
             to bring one into existence. --}}
        <div data-admin-list-summary style="padding: 0.625rem 1rem; border-bottom: 1px solid #e3e3e3; background: #f6f6f7;{{ $notifications->total() > 0 ? '' : ' display: none;' }}">
            <p style="font-size: 12px; color: #616161; margin: 0;">
                Showing <span data-admin-list-first-item style="font-weight: 600; color: #303030;">{{ $notifications->firstItem() ?? 0 }}</span>-<span data-admin-list-last-item style="font-weight: 600; color: #303030;">{{ $notifications->lastItem() ?? 0 }}</span> of <span data-admin-list-total style="font-weight: 600; color: #303030;">{{ $notifications->total() }}</span> notifications
            </p>
        </div>

        {{-- Notification List. Each row links through admin.notifications.read, the
             same route the header dropdown uses: it marks the row read and forwards
             to the record. Rows used to be plain divs with nothing to click, so this
             page - the one the bell's "View all" promises - opened and cleared nothing.
             The inline colour and text-decoration keep the admin's global anchor
             styling (blue, underline on hover) off a whole-row link. --}}
        {{-- The rows get a box of their own so the poller has somewhere to put a
             new one: insertBefore(row, list.firstChild) has to mean "above the
             newest row", not "above the tab strip". data-page is what keeps a
             new arrival off pages two and up, where it does not belong;
             data-per-page is what keeps page one exactly as long as the
             paginator says it is. --}}
        <div data-admin-list
             data-page="{{ $notifications->currentPage() }}"
             data-per-page="{{ $notifications->perPage() }}">
        @forelse($notifications as $notification)
            <a href="{{ route('admin.notifications.read', $notification) }}"
               data-notification-uuid="{{ $notification->uuid }}"
               class="transition-colors hover:bg-neutral-50"
               style="padding: 0.875rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: flex-start; gap: 0.75rem; color: inherit; text-decoration: none;{{ !$notification->is_read ? ' border-left: 3px solid #005bd3; background: #fafbff;' : '' }}">
                {{-- Icon Circle. These cases are the admin audience vocabulary that
                     NotificationService::notifyAdmins() actually writes; the previous
                     cases ('order', 'payment', 'review', 'stock') matched no type this
                     app has ever stored, so every row fell through to the grey bell. --}}
                <div style="flex-shrink: 0; margin-top: 0.125rem;">
                    @switch($notification->type)
                        @case('new_order')
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #e0f0ff; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1rem; height: 1rem; color: #005bd3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            @break
                        @case('new_return_request')
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #ffe0db; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1rem; height: 1rem; color: #b71c00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                            </div>
                            @break
                        @case('new_enquiry')
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #e8f5f5; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1rem; height: 1rem; color: #6F9CA2;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            @break
                        @case('new_ticket')
                        @case('ticket_customer_reply')
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #f3e8ff; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1rem; height: 1rem; color: #8b5cf6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                            </div>
                            @break
                        @case('new_review')
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #fff3cd; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1rem; height: 1rem; color: #8a6d00;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </div>
                            @break
                        @case('new_newsletter_subscriber')
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #cdfee1; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1rem; height: 1rem; color: #1a7a2e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                </svg>
                            </div>
                            @break
                        @default
                            <div style="width: 2rem; height: 2rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center;">
                                <svg style="width: 1rem; height: 1rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            </div>
                    @endswitch
                </div>

                {{-- Content --}}
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 0.375rem;">
                        <p style="margin: 0; font-size: 13px; font-weight: 500; color: #303030;">{{ $notification->title }}</p>
                        @if(!$notification->is_read)
                            <span style="display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #005bd3; flex-shrink: 0;"></span>
                        @endif
                    </div>
                    @if($notification->content)
                        <p style="margin: 0.25rem 0 0 0; font-size: 12px; color: #616161; line-height: 1.4;">{{ $notification->content }}</p>
                    @endif
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.375rem; flex-wrap: wrap;">
                        <span style="font-size: 11px; color: #616161;">{{ $notification->created_at->diffForHumans() }}</span>
                        <span style="display: inline-block; padding: 0.0625rem 0.375rem; font-size: 10px; font-weight: 600; border-radius: 1rem; background: #e0f0ff; color: #005bd3;">{{ $notification->channel }}</span>
                        <span style="display: inline-block; padding: 0.0625rem 0.375rem; font-size: 10px; font-weight: 600; border-radius: 1rem; background: #fff3cd; color: #8a6d00;">{{ $notification->type }}</span>
                    </div>
                </div>
            </a>
        @empty
            {{-- Empty State --}}
            <div data-admin-list-empty style="padding: 4rem 1rem; text-align: center;">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                    <div style="width: 3rem; height: 3rem; background: #f6f6f7; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 1.5rem; height: 1.5rem; color: #616161;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">{{ $filter === 'unread' ? 'Nothing unread' : 'No notifications yet' }}</p>
                    <p style="font-size: 12px; color: #616161; margin: 0;">{{ $filter === 'unread' ? 'Everything addressed to you has been read.' : 'Notifications will appear here when there is activity.' }}</p>
                </div>
            </div>
        @endforelse

        {{-- The shape the poller stamps out for a notification that arrives
             while this page is open. It sits in the view beside the rows it has
             to match rather than being assembled as a string in JavaScript, so
             there is one copy of this markup; every field is written with
             textContent, so a customer's enquiry subject cannot carry markup
             into the page. All the icons are present and hidden, and the poller
             reveals the one matching the type - the same fallback to the plain
             bell as the @switch above. --}}
        <template data-admin-list-row-template>
            <a href="#"
               data-notification-uuid=""
               class="transition-colors hover:bg-neutral-50"
               style="padding: 0.875rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: flex-start; gap: 0.75rem; color: inherit; text-decoration: none; border-left: 3px solid #005bd3; background: #fafbff;">
                <div style="flex-shrink: 0; margin-top: 0.125rem;">
                    <div data-slot="icon-wrap" style="width: 2rem; height: 2rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center;">
                        <span data-icon="new_order" data-bg="#e0f0ff" hidden>
                            <svg style="width: 1rem; height: 1rem; color: #005bd3; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </span>
                        <span data-icon="new_return_request" data-bg="#ffe0db" hidden>
                            <svg style="width: 1rem; height: 1rem; color: #b71c00; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </span>
                        <span data-icon="new_enquiry" data-bg="#e8f5f5" hidden>
                            <svg style="width: 1rem; height: 1rem; color: #6F9CA2; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <span data-icon="new_ticket" data-bg="#f3e8ff" hidden>
                            <svg style="width: 1rem; height: 1rem; color: #8b5cf6; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                        </span>
                        <span data-icon="new_review" data-bg="#fff3cd" hidden>
                            <svg style="width: 1rem; height: 1rem; color: #8a6d00; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </span>
                        <span data-icon="new_newsletter_subscriber" data-bg="#cdfee1" hidden>
                            <svg style="width: 1rem; height: 1rem; color: #1a7a2e; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                        </span>
                        <span data-icon="default" data-bg="#f1f1f1" hidden>
                            <svg style="width: 1rem; height: 1rem; color: #616161; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 0.375rem;">
                        <p data-slot="title" style="margin: 0; font-size: 13px; font-weight: 500; color: #303030;"></p>
                        <span style="display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #005bd3; flex-shrink: 0;"></span>
                    </div>
                    <p data-slot="content" style="margin: 0.25rem 0 0 0; font-size: 12px; color: #616161; line-height: 1.4;"></p>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.375rem; flex-wrap: wrap;">
                        <span data-slot="time" style="font-size: 11px; color: #616161;"></span>
                        <span data-slot="channel" style="display: inline-block; padding: 0.0625rem 0.375rem; font-size: 10px; font-weight: 600; border-radius: 1rem; background: #e0f0ff; color: #005bd3;"></span>
                        <span data-slot="type" style="display: inline-block; padding: 0.0625rem 0.375rem; font-size: 10px; font-weight: 600; border-radius: 1rem; background: #fff3cd; color: #8a6d00;"></span>
                    </div>
                </div>
            </a>
        </template>
        </div>

        {{-- Pagination --}}
        @if($notifications->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
