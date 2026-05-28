<header class="h-16 bg-white border-b border-neutral-200 flex items-center justify-between px-6">
    <!-- Left side -->
    <div class="flex items-center gap-4">
        <!-- Mobile menu toggle -->
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 -ml-2 text-neutral-600 hover:text-neutral-900">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Page title -->
        <h1 class="text-lg font-semibold text-neutral-900">{{ $title ?? 'Dashboard' }}</h1>
    </div>

    <!-- Right side -->
    <div class="flex items-center gap-4">
        <!-- Quick actions -->
        <a href="{{ route('seller.products.create') }}" class="hidden md:flex btn-primary text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Product
        </a>

        <!-- Notifications -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="relative p-2 text-neutral-600 hover:text-neutral-900">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                @if($unreadNotificationsCount ?? 0)
                    <span class="absolute top-1 right-1 w-2 h-2 bg-error-500 rounded-full"></span>
                @endif
            </button>

            <div x-show="open" x-transition @click.away="open = false"
                 class="absolute right-0 mt-2 w-80 bg-white border border-neutral-200 rounded-lg shadow-lg z-50">
                <div class="px-4 py-3 border-b border-neutral-200 flex justify-between items-center">
                    <h3 class="font-semibold text-neutral-900">Notifications</h3>
                    @if($unreadNotificationsCount ?? 0)
                        <span class="text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full">
                            {{ $unreadNotificationsCount }} new
                        </span>
                    @endif
                </div>
                <div class="max-h-96 overflow-y-auto divide-y divide-neutral-100">
                    @forelse($notifications ?? [] as $notification)
                        <a href="{{ $notification->action_url ?? '#' }}"
                           class="block px-4 py-3 hover:bg-neutral-50 {{ !$notification->is_read ? 'bg-primary-50/50' : '' }}">
                            <div class="text-sm font-medium text-neutral-900">{{ $notification->title }}</div>
                            <div class="text-xs text-neutral-600 mt-1">{{ $notification->content }}</div>
                            <div class="text-xs text-neutral-600 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                        </a>
                    @empty
                        <div class="p-4 text-center text-sm text-neutral-600">
                            No notifications yet
                        </div>
                    @endforelse
                </div>
                <div class="px-4 py-3 border-t border-neutral-200">
                    <a href="{{ route('seller.notifications') }}" class="text-sm text-primary-600 hover:text-primary-700">
                        View all notifications
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="relative p-2 text-neutral-600 hover:text-neutral-900">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                @if($unreadMessagesCount ?? 0)
                    <span class="absolute top-1 right-1 w-2 h-2 bg-error-500 rounded-full"></span>
                @endif
            </button>

            <div x-show="open" x-transition @click.away="open = false"
                 class="absolute right-0 mt-2 w-80 bg-white border border-neutral-200 rounded-lg shadow-lg z-50">
                <div class="px-4 py-3 border-b border-neutral-200">
                    <h3 class="font-semibold text-neutral-900">Messages</h3>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <div class="p-4 text-center text-sm text-neutral-600">
                        No new messages
                    </div>
                </div>
                <div class="px-4 py-3 border-t border-neutral-200">
                    <a href="{{ route('seller.messages.index') }}" class="text-sm text-primary-600 hover:text-primary-700">
                        View all messages
                    </a>
                </div>
            </div>
        </div>

        <!-- Help -->
        <a href="{{ route('seller.help') }}" class="hidden md:block p-2 text-neutral-600 hover:text-neutral-900" title="Help Center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </a>

        <!-- User menu -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                    @if(auth()->user()->seller?->logo_url)
                        <img src="{{ auth()->user()->seller->logo_url }}" alt="{{ auth()->user()->seller->business_name }}" class="w-full h-full rounded-full object-cover">
                    @else
                        <span class="text-sm font-medium text-primary-600">
                            {{ substr(auth()->user()->seller?->business_name ?? 'S', 0, 1) }}
                        </span>
                    @endif
                </div>
                <div class="hidden md:block text-left">
                    <div class="text-sm font-medium text-neutral-900">{{ auth()->user()->seller?->business_name ?? 'My Store' }}</div>
                    <div class="text-xs text-neutral-600">Seller</div>
                </div>
                <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-transition @click.away="open = false"
                 class="absolute right-0 mt-2 w-56 bg-white border border-neutral-200 rounded-lg shadow-lg z-50">
                <div class="px-4 py-3 border-b border-neutral-100">
                    <div class="text-sm font-medium text-neutral-900">{{ auth()->user()->seller?->business_name ?? 'My Store' }}</div>
                    <div class="text-xs text-neutral-600">{{ auth()->user()->email }}</div>
                </div>
                <a href="{{ route('seller.settings.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Store Profile
                </a>
                <a href="{{ route('seller.settings.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Account Settings
                </a>
                <a href="{{ url('/') }}" target="_blank" class="flex items-center gap-2 px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    View Storefront
                </a>
                <div class="border-t border-neutral-100">
                    <form action="{{ route('seller.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
