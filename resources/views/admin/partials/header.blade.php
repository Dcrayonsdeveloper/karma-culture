<header class="h-14 bg-white flex items-center justify-between px-4 lg:px-6" style="border-bottom: 1px solid #e3e3e3;">
    <!-- Left side -->
    <div class="flex items-center gap-3">
        <!-- Mobile menu toggle -->
        <button type="button"
                @click="sidebarOpen = !sidebarOpen"
                class="lg:hidden p-1.5 -ml-1 text-neutral-600 hover:text-neutral-900 rounded-lg hover:bg-neutral-100"
                aria-controls="admin-sidebar"
                :aria-expanded="sidebarOpen ? 'true' : 'false'"
                aria-label="Toggle menu">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>

    <!-- Center: Search bar (Shopify style) -->
    <div class="flex-1 max-w-xl mx-4" x-data="adminSearch()">
        <button @click="openSearch()" class="admin-search-bar w-full flex items-center gap-2">
            <svg style="width: 1rem; height: 1rem; flex-shrink: 0; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <span class="text-sm" style="color: #999;">Search</span>
            <span class="ml-auto hidden sm:inline-flex items-center gap-0.5 text-[11px] px-1.5 py-0.5 rounded" style="background: #e8e8e8; color: #666;">
                <kbd>Ctrl</kbd><span>+</span><kbd>K</kbd>
            </span>
        </button>

        <!-- Search modal overlay -->
        <div x-cloak x-show="isOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto px-3 sm:px-0" style="background: rgba(0,0,0,0.4);" @click.self="isOpen = false">
            <div class="w-full max-w-lg mx-auto mt-[15vh]" @click.stop>
                <div class="bg-white rounded-xl overflow-hidden" style="box-shadow: 0 16px 70px rgba(0,0,0,0.2);">
                    <div class="flex items-center gap-3 px-4 py-3" style="border-bottom: 1px solid #e3e3e3;">
                        <svg style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-ref="searchField" x-model="query" @keydown.escape="isOpen = false"
                               @keydown.enter="goToSearch()"
                               placeholder="Search products, orders, customers..."
                               class="flex-1 min-w-0 text-sm bg-transparent border-none outline-none" style="color: #303030;">
                        <button @click="isOpen = false" class="text-xs px-2 py-1 rounded" style="background: #f1f1f1; color: #666;">ESC</button>
                    </div>
                    <div class="px-4 py-3">
                        <p class="text-[11px] font-medium mb-2" style="color: #999; text-transform: uppercase; letter-spacing: 0.05em;">Search in</p>
                        <div class="flex gap-1.5">
                            <button @click="section = 'products'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                    :style="section === 'products' ? 'background: #303030; color: white;' : 'background: #f1f1f1; color: #666;'">Products</button>
                            <button @click="section = 'orders'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                    :style="section === 'orders' ? 'background: #303030; color: white;' : 'background: #f1f1f1; color: #666;'">Orders</button>
                            <button @click="section = 'customers'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                    :style="section === 'customers' ? 'background: #303030; color: white;' : 'background: #f1f1f1; color: #666;'">Customers</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right side -->
    <div class="flex items-center gap-1">
        <!-- Notifications -->
        @php
            // This bell shows the admin audience only. Both audiences live in the one
            // notifications table and are keyed by user_id alone - an admin is a users
            // row with role = 'admin' - so before ->forAdmin() an admin who also shopped
            // saw their own "Your order has been confirmed" rows mixed into the admin
            // bell. The conditions are declared once and the badge count clones them, so
            // the count and the list cannot drift apart on a partial that renders on
            // every admin page load. $adminUser is guarded because a layout includes
            // this partial, and nothing here guarantees an authenticated admin.
            $adminUser = auth('admin')->user();
            $bellNotifications = collect();
            $unreadCount = 0;

            if ($adminUser) {
                $adminBell = \App\Models\Notification::query()
                    ->where('user_id', $adminUser->id)
                    ->forAdmin();

                $unreadCount = (clone $adminBell)->unread()->count();

                // The LATEST five, read or not - the badge is what counts unread.
                //
                // The list used to be ->unread() too, so opening a notification
                // took it off the bell and the bell fell back to whatever was
                // still unread underneath. The newest order sat at the top of
                // /admin/notifications while the bell showed cancellations from
                // ten minutes earlier, which reads as the bell being broken
                // rather than as the newest ones having been read.
                $bellNotifications = $adminBell->latest()->limit(5)->get();
            }

            // The unread marker is a bare coloured dot with no text, so the accessible
            // name has to carry the count or the bell announces identically at 0 and 40.
            $bellLabel = $unreadCount > 0
                ? "Notifications, {$unreadCount} unread"
                : 'Notifications, none unread';
        @endphp
        <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
            <button @click="open = !open"
                    :aria-expanded="open ? 'true' : 'false'"
                    aria-haspopup="true"
                    aria-controls="admin-notifications-panel"
                    data-admin-bell-button
                    class="relative p-2 rounded-lg text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 transition-colors" aria-label="{{ $bellLabel }}">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                {{-- A number rather than the bare dot it replaced: the poller
                     rewrites this every ten seconds, and "something is unread"
                     going to "something else is unread" is a change the dot
                     could not show. Hidden rather than absent at zero, so the
                     poller has a node to write into without rebuilding the
                     button. The accessible count lives on the button's
                     aria-label, which the poller keeps in step. --}}
                <span data-admin-bell-badge
                      aria-hidden="true"
                      style="position: absolute; top: 0.125rem; right: 0.125rem; min-width: 1rem; height: 1rem; padding: 0 0.25rem; border-radius: 9999px; background: #e74c3c; color: #fff; font-size: 10px; font-weight: 700; line-height: 1rem; text-align: center;{{ $unreadCount > 0 ? '' : ' display: none;' }}">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
            </button>

            {{-- The panel is a flex column capped against the viewport so that only the
                 list scrolls and the "View all notifications" footer stays reachable.
                 The admin shell is h-screen overflow-hidden, so on a short window
                 anything the panel spills past the viewport was hard-clipped and the
                 footer could not be reached at all. Below sm the panel is a fixed sheet
                 under the header and from sm it anchors to the bell - keep both, that
                 pair is what stopped it clipping off the side of a phone. --}}
            <div x-cloak x-show="open" x-transition @click.away="open = false"
                 id="admin-notifications-panel"
                 role="region"
                 aria-labelledby="admin-notifications-heading"
                 class="fixed left-3 right-3 top-14 mt-2 sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:w-80 flex flex-col max-h-[calc(100vh-5rem)] overflow-hidden bg-white rounded-xl z-50" style="border: 1px solid #e3e3e3; box-shadow: 0 8px 30px rgba(0,0,0,0.12);">
                <div class="px-4 py-3 shrink-0 flex items-center justify-between" style="border-bottom: 1px solid #e3e3e3;">
                    <h3 id="admin-notifications-heading" class="text-sm font-semibold" style="color: #303030;">Notifications</h3>
                    <span data-admin-bell-unread-label class="text-xs font-medium" style="color: #6F9CA2;{{ $unreadCount > 0 ? '' : ' display: none;' }}">{{ $unreadCount }} new</span>
                </div>
                <div data-admin-bell-list class="min-h-0 max-h-96 overflow-y-auto">
                    @forelse($bellNotifications as $notification)
                        {{-- Read rows stay on the bell, on a plain background; the
                             dot beside the title is what separates them from the
                             ones still waiting. --}}
                        <a href="{{ route('admin.notifications.read', $notification) }}"
                           data-notification-uuid="{{ $notification->uuid }}"
                           class="block px-4 py-3 hover:bg-neutral-50 transition-colors"
                           style="border-bottom: 1px solid #f5f5f5;{{ $notification->is_read ? '' : ' background: #f7fbfb;' }}">
                            <div class="flex items-start gap-3">
                                <div style="width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; {{ $notification->type === 'new_enquiry' ? 'background:#e8f5f5;' : ($notification->type === 'new_ticket' ? 'background:#f3e8ff;' : 'background:#f5f5f5;') }}">
                                    @if($notification->type === 'new_enquiry')
                                        <svg style="width: 1rem; height: 1rem; color: #6F9CA2;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    @elseif($notification->type === 'new_ticket')
                                        <svg style="width: 1rem; height: 1rem; color: #8b5cf6;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                        </svg>
                                    @else
                                        <svg style="width: 1rem; height: 1rem; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium" style="color: #303030;">
                                        {{ $notification->title }}
                                        @unless($notification->is_read)
                                            {{-- Same marker the list uses, so the two screens
                                                 read the same. aria-hidden with a word behind
                                                 it: a bare dot announces as nothing. --}}
                                            <span aria-hidden="true" style="display: inline-block; width: 0.4rem; height: 0.4rem; border-radius: 9999px; background: #2563eb; vertical-align: middle; margin-left: 0.25rem;"></span>
                                            <span class="sr-only">(unread)</span>
                                        @endunless
                                    </p>
                                    <p class="text-xs mt-0.5 truncate" style="color: #999;">{{ $notification->content }}</p>
                                    <p class="text-[10px] mt-1" style="color: #bbb;">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div data-admin-bell-empty class="p-4 text-center text-sm" style="color: #999;">
                            No notifications yet
                        </div>
                    @endforelse

                    {{-- The shape the poller stamps out for a notification that
                         arrives between page loads. It lives here, beside the
                         rows it has to sit next to, rather than being built as a
                         string in JavaScript: the markup stays in Blade with the
                         rest of the bell, and every field is written with
                         textContent rather than innerHTML, so a customer who
                         names their enquiry "<img onerror=...>" cannot reach the
                         DOM through it. All three icons are present and hidden;
                         the poller reveals the one matching the type. --}}
                    <template data-admin-bell-row-template>
                        <a href="#"
                           data-notification-uuid=""
                           class="block px-4 py-3 hover:bg-neutral-50 transition-colors"
                           style="border-bottom: 1px solid #f5f5f5; background: #f7fbfb;">
                            <div class="flex items-start gap-3">
                                <div data-slot="icon-wrap" style="width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #f5f5f5;">
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
                                    <span data-icon="default" data-bg="#f5f5f5" hidden>
                                        <svg style="width: 1rem; height: 1rem; color: #999; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium" style="color: #303030;">
                                        <span data-slot="title"></span>
                                        <span aria-hidden="true" style="display: inline-block; width: 0.4rem; height: 0.4rem; border-radius: 9999px; background: #2563eb; vertical-align: middle; margin-left: 0.25rem;"></span>
                                        <span class="sr-only">(unread)</span>
                                    </p>
                                    <p data-slot="content" class="text-xs mt-0.5 truncate" style="color: #999;"></p>
                                    <p data-slot="time" class="text-[10px] mt-1" style="color: #bbb;"></p>
                                </div>
                            </div>
                        </a>
                    </template>
                </div>
                <div class="px-4 py-3 shrink-0" style="border-top: 1px solid #e3e3e3;">
                    <a href="{{ route('admin.notifications') }}" class="text-sm font-medium" style="color: #6F9CA2;">
                        View all notifications
                    </a>
                </div>
            </div>
        </div>

        <!-- User menu -->
        <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
            <button @click="open = !open"
                    :aria-expanded="open ? 'true' : 'false'"
                    aria-haspopup="true"
                    aria-controls="admin-user-menu"
                    class="flex items-center gap-1.5 p-1.5 rounded-lg hover:bg-neutral-100 transition-colors" aria-label="User menu">
                <div style="width: 1.75rem; height: 1.75rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; background: #1a7a2e;">
                    <span class="text-xs font-medium text-white">F</span>
                </div>
            </button>

            <div x-cloak x-show="open" x-transition @click.away="open = false"
                 id="admin-user-menu"
                 role="region"
                 aria-labelledby="admin-user-menu-name"
                 class="absolute right-0 mt-2 w-52 bg-white rounded-xl z-50" style="border: 1px solid #e3e3e3; box-shadow: 0 8px 30px rgba(0,0,0,0.12);">
                <div class="px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                    <div id="admin-user-menu-name" class="text-sm font-medium" style="color: #303030; overflow-wrap: anywhere;">{{ $adminUser?->full_name }}</div>
                    <div class="text-xs" style="color: #999; overflow-wrap: anywhere;">{{ $adminUser?->email }}</div>
                </div>
                <div class="py-1">
                    <a href="{{ route('admin.profile') }}" class="block px-4 py-2 text-sm hover:bg-neutral-50 transition-colors" style="color: #303030;">
                        Profile Settings
                    </a>
                    {{-- This is the Stores module's only entry point anywhere in
                         the admin, and its route sits inside the
                         admin.section:settings group - which no staff role is
                         granted by default (User::getDefaultStaffPermissions).
                         Unguarded, it offered every staff admin a menu item that
                         answered with a hard 403. Every sidebar section is
                         guarded the same way. --}}
                    @if($adminUser->canAccessSection('settings'))
                        <a href="{{ route('admin.stores.index') }}" class="block px-4 py-2 text-sm hover:bg-neutral-50 transition-colors" style="color: #303030;">
                            View Store
                        </a>
                    @endif
                </div>
                <div style="border-top: 1px solid #f0f0f0;">
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2.5 text-sm hover:bg-neutral-50 transition-colors" style="color: #303030;">
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

@push('scripts')
<script>
function adminSearch() {
    return {
        isOpen: false,
        query: '',
        section: 'products',

        openSearch() {
            this.isOpen = true;
            this.$nextTick(() => this.$refs.searchField?.focus());
        },

        goToSearch() {
            if (this.query.trim()) {
                window.location.href = '{{ url("admin") }}/' + this.section + '?search=' + encodeURIComponent(this.query);
            }
        },

        init() {
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.openSearch();
                }
            });
        }
    };
}
</script>
@endpush
