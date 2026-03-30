<header class="h-14 bg-white flex items-center justify-between px-4 lg:px-6" style="border-bottom: 1px solid #e3e3e3;">
    <!-- Left side -->
    <div class="flex items-center gap-3">
        <!-- Mobile menu toggle -->
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-1.5 -ml-1 text-neutral-600 hover:text-neutral-900 rounded-lg hover:bg-neutral-100" aria-label="Toggle menu">
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
             class="fixed inset-0 z-50" style="background: rgba(0,0,0,0.4);" @click.self="isOpen = false">
            <div class="w-full max-w-lg mx-auto mt-[15vh]" @click.stop>
                <div class="bg-white rounded-xl overflow-hidden" style="box-shadow: 0 16px 70px rgba(0,0,0,0.2);">
                    <div class="flex items-center gap-3 px-4 py-3" style="border-bottom: 1px solid #e3e3e3;">
                        <svg style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; color: #999;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-ref="searchField" x-model="query" @keydown.escape="isOpen = false"
                               @keydown.enter="goToSearch()"
                               placeholder="Search products, orders, customers..."
                               class="flex-1 text-sm bg-transparent border-none outline-none" style="color: #303030;">
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
            $adminUser = auth('admin')->user();
            $unreadNotifications = \App\Models\Notification::where('user_id', $adminUser->id)->unread()->latest()->limit(5)->get();
            $unreadCount = \App\Models\Notification::where('user_id', $adminUser->id)->unread()->count();
        @endphp
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="relative p-2 rounded-lg text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 transition-colors" aria-label="Notifications">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                @if($unreadCount > 0)
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full" style="background: #e74c3c;"></span>
                @endif
            </button>

            <div x-cloak x-show="open" x-transition @click.away="open = false"
                 class="absolute right-0 mt-2 w-80 bg-white rounded-xl z-50" style="border: 1px solid #e3e3e3; box-shadow: 0 8px 30px rgba(0,0,0,0.12);">
                <div class="px-4 py-3 flex items-center justify-between" style="border-bottom: 1px solid #e3e3e3;">
                    <h3 class="text-sm font-semibold" style="color: #303030;">Notifications</h3>
                    @if($unreadCount > 0)
                        <span class="text-xs font-medium" style="color: #6F9CA2;">{{ $unreadCount }} new</span>
                    @endif
                </div>
                <div class="max-h-96 overflow-y-auto">
                    @forelse($unreadNotifications as $notification)
                        <a href="{{ route('admin.notifications.read', $notification) }}"
                           class="block px-4 py-3 hover:bg-neutral-50 transition-colors" style="border-bottom: 1px solid #f5f5f5;">
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
                                    <p class="text-sm font-medium" style="color: #303030;">{{ $notification->title }}</p>
                                    <p class="text-xs mt-0.5 truncate" style="color: #999;">{{ $notification->content }}</p>
                                    <p class="text-[10px] mt-1" style="color: #bbb;">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-4 text-center text-sm" style="color: #999;">
                            No new notifications
                        </div>
                    @endforelse
                </div>
                <div class="px-4 py-3" style="border-top: 1px solid #e3e3e3;">
                    <a href="{{ route('admin.notifications') }}" class="text-sm font-medium" style="color: #6F9CA2;">
                        View all notifications
                    </a>
                </div>
            </div>
        </div>

        <!-- User menu -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-1.5 p-1.5 rounded-lg hover:bg-neutral-100 transition-colors" aria-label="User menu">
                <div style="width: 1.75rem; height: 1.75rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; background: #1a7a2e;">
                    <span class="text-xs font-medium text-white">F</span>
                </div>
            </button>

            <div x-cloak x-show="open" x-transition @click.away="open = false"
                 class="absolute right-0 mt-2 w-52 bg-white rounded-xl z-50" style="border: 1px solid #e3e3e3; box-shadow: 0 8px 30px rgba(0,0,0,0.12);">
                <div class="px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                    <div class="text-sm font-medium" style="color: #303030;">{{ auth('admin')->user()->full_name }}</div>
                    <div class="text-xs" style="color: #999;">{{ auth('admin')->user()->email }}</div>
                </div>
                <div class="py-1">
                    <a href="{{ route('admin.profile') }}" class="block px-4 py-2 text-sm hover:bg-neutral-50 transition-colors" style="color: #303030;">
                        Profile Settings
                    </a>
                    <a href="{{ route('admin.stores.index') }}" class="block px-4 py-2 text-sm hover:bg-neutral-50 transition-colors" style="color: #303030;">
                        View Store
                    </a>
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
