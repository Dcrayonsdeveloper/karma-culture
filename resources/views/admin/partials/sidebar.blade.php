{{--
    Admin sidebar: an off-canvas drawer below lg, a static column from lg up.

    Three things about the drawer are deliberate and easy to undo by accident.

    1. STACKING. The backdrop must stay below the drawer. It does not do that
       on its own - see the `z-index: 20` rule further down and the comment
       above it. This was why the drawer opened but could not be used at all.

    2. HEIGHT. The drawer and its backdrop are `position: fixed`, and a fixed
       box sized with `inset-y-0` is measured against the initial containing
       block. On a phone that block is the *large* viewport - the one with the
       browser's URL bar retracted - so the drawer's bottom edge fell outside
       the visible screen. The nav is the scrolling element inside the drawer,
       so its scrollport ended off-screen too and the sections below Online
       Store could not be scrolled into view however far you dragged. An
       explicit `dvh` height tracks the visible viewport instead; the `vh`
       line above it is the fallback for engines without `dvh`.

    3. OPEN/CLOSED STATE. The drawer used to choose its transform with
       `:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"`, so it
       carried *neither* class during the first paint - before Alpine boots -
       and flashed open across the page on every admin page load. Closed is
       now the CSS default and Alpine only adds `.is-open`, so the very first
       paint is already right.
--}}
<style>
.admin-nav-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.375rem 0.5rem;
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 400;
    color: #b5b5b5;
    text-decoration: none;
    transition: all 0.1s ease;
}
.admin-nav-item:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #e0e0e0;
    text-decoration: none;
}
.admin-nav-item.active {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    font-weight: 500;
}
.admin-nav-section {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #686868;
    padding: 0 0.5rem;
    margin-bottom: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
/* In the off-canvas drawer, and on any touch screen, each nav row reaches a
   40px target; the compact 30px rows stay for a mouse on a wide screen. */
@media (max-width: 1023.98px), (pointer: coarse) {
    .admin-nav-item {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
        min-height: 2.5rem;
    }
}

/* --- The drawer -----------------------------------------------------------
   Written mobile-first on purpose. Writing it the other way round - drawer
   rules inside `@media (max-width: 1023.98px)` - leaves a hairline range
   between 1023.98px and Tailwind's `lg` (min-width: 1024px) where neither the
   drawer rules nor `lg:static` apply, and a fixed box with no height there
   collapses to its content. Defaulting to the drawer and resetting at exactly
   `lg` cannot fall through that gap. */
.admin-sidebar { background: #1a1a1a; }

.admin-sidebar,
.admin-sidebar__backdrop {
    height: 100vh;
    height: 100dvh;
}

.admin-sidebar {
    /* 240px is comfortable from 360px up; on a 320px screen an unbounded
       drawer would leave no page showing behind it to tap back to. */
    max-width: 85vw;
    transform: translateX(-100%);
    /* Off-canvas links must not be reachable by Tab or a screen reader. The
       zero-length visibility transition is delayed so the drawer stays
       painted through the closing slide. */
    visibility: hidden;
    transition: transform 0.2s ease-in-out, visibility 0s linear 0.2s;
}

.admin-sidebar.is-open {
    transform: translateX(0);
    visibility: visible;
    transition: transform 0.2s ease-in-out, visibility 0s;
}

@media (prefers-reduced-motion: reduce) {
    .admin-sidebar,
    .admin-sidebar.is-open { transition: none; }
}

/* From lg up it is a plain static column in the shell's flex row again.
   `.is-open` is listed alongside the bare class because it is (0,2,0) and
   would otherwise outrank this (0,1,0) reset even from inside the media
   query - the drawer flag can still be set here after a resize. */
@media (min-width: 1024px) {
    .admin-sidebar,
    .admin-sidebar.is-open {
        height: auto;
        max-width: none;
        transform: none;
        visibility: visible;
        transition: none;
    }
}

/* The nav is the only scrolling box in the drawer, so it needs a floor of zero
   to be allowed to shrink inside the flex column. The safe-area term resolves
   to 0px as things stand - the layout's viewport meta has no viewport-fit=cover,
   so the browser already insets the viewport past the home indicator - and is
   here so the last nav row keeps its clearance if that ever changes. */
.admin-sidebar__nav {
    min-height: 0;
    padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px));
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
}

/* THE ONE THAT MADE THE DRAWER USELESS. app.css carries an unlayered modal
   rule, `.layout-admin [x-show].fixed { z-index: 50 }`. Alpine leaves `x-show`
   on the element, so this backdrop matched it and was lifted to 50 - above the
   z-30 drawer. Tailwind's `.z-20` could not hold it down: that utility lives in
   `@layer utilities`, and an unlayered declaration outranks a layered one
   whatever the specificity (it also loses (0,1,0) to (0,3,0) on specificity
   alone). So the drawer slid in *underneath* its own scrim: the nav could not
   be dragged, because every touch landed on the backdrop, and a tap ran the
   backdrop's @click and shut the drawer. The washed-out look in the bug report
   is the same scrim. The extra class here makes the selector (0,4,0), so it
   wins wherever this rule block ends up in the document. */
.layout-admin [x-show].admin-sidebar__backdrop.fixed { z-index: 20; }
</style>

<!-- Mobile drawer backdrop -->
<div x-cloak
     x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     aria-hidden="true"
     class="admin-sidebar__backdrop fixed inset-x-0 top-0 bg-black/50 z-20 lg:hidden"></div>

<!-- Sidebar -->
<aside id="admin-sidebar"
       :class="{ 'is-open': sidebarOpen }"
       class="admin-sidebar fixed top-0 left-0 z-30 w-60 flex flex-col lg:static">

    <!-- Store name + logo -->
    <div class="flex items-center gap-2.5 h-14 shrink-0 px-4" style="border-bottom: 1px solid #2a2a2a;">
        <div style="width: 1.75rem; height: 1.75rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #333;">
            <svg style="width: 1rem; height: 1rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
            </svg>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-white truncate">
            {{ config('app.name') }}
        </a>
        {{-- A way out from inside the drawer: the backdrop strip left of it is
             only ~15% of a phone screen and is easy to miss. --}}
        <button type="button"
                @click="sidebarOpen = false"
                class="lg:hidden ml-auto -mr-1.5 p-1.5 rounded-lg shrink-0"
                style="color: #b5b5b5;"
                aria-label="Close menu">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="admin-sidebar__nav flex-1 px-2 py-3 space-y-0.5 overflow-y-auto scrollbar-dark" aria-label="Admin">
        @php $user = auth('admin')->user(); @endphp

        <!-- Dashboard -->
        @if($user->canAccessSection('dashboard'))
        <a href="{{ route('admin.dashboard') }}"
           class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Home
        </a>
        @endif

        {{-- Ungated on purpose: the notifications route carries no admin.section
             middleware, so every admin can open the page the header bell links to. --}}
        <a href="{{ route('admin.notifications') }}"
           class="admin-nav-item {{ request()->routeIs('admin.notifications') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            Notifications
        </a>

        <!-- Orders Section -->
        {{-- The heading follows the Customers block below: it shows when the
             user can reach ANY item under it, so a recovery-desk staff member
             holding only `abandoned_carts` still gets a labelled group instead
             of a stray link floating under Notifications. --}}
        @if($user->canAccessSection('orders') || $user->canAccessSection('abandoned_carts'))
        <div class="pt-4 pb-1">
            <p class="admin-nav-section">Orders</p>
        </div>
        @endif
        @if($user->canAccessSection('orders'))
        <a href="{{ route('admin.orders.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            Orders
        </a>
        <a href="{{ route('admin.returns.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.returns.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
            </svg>
            Returns
        </a>
        @endif
        @if($user->canAccessSection('abandoned_carts'))
        <a href="{{ route('admin.abandoned-carts.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.abandoned-carts.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/>
            </svg>
            Abandoned Carts
        </a>
        @endif

        <!-- Products Section -->
        @if($user->canAccessSection('catalog'))
        <div class="pt-4 pb-1">
            <p class="admin-nav-section">Products</p>
        </div>
        <a href="{{ route('admin.products.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            Products
        </a>
        <a href="{{ route('admin.categories.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Collections
        </a>
        <a href="{{ route('admin.brands.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.brands.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            Brands
        </a>
        <a href="{{ route('admin.inventory.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Inventory
        </a>
        @endif

        <!-- Customers Section -->
        @if($user->canAccessSection('customers') || $user->canAccessSection('sellers') || $user->canAccessSection('staff') || $user->canAccessSection('delivery_partners'))
        <div class="pt-4 pb-1">
            <p class="admin-nav-section">Customers</p>
        </div>
        @if($user->canAccessSection('customers'))
        <a href="{{ route('admin.customers.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Customers
        </a>
        @endif
        @if($user->canAccessSection('staff'))
        <a href="{{ route('admin.staff.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Staff
        </a>
        @endif
        @endif

        <!-- Marketing Section -->
        @if($user->canAccessSection('marketing'))
        <div class="pt-4 pb-1">
            <p class="admin-nav-section">Marketing</p>
        </div>
        <a href="{{ route('admin.coupons.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
            </svg>
            Discounts
        </a>
        <a href="{{ route('admin.flash-sales.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.flash-sales.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Flash Sales
        </a>
        <a href="{{ route('admin.banners.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Banners
        </a>
        <a href="{{ route('admin.newsletter.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.newsletter.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Newsletter
        </a>
        @endif

        <!-- Online Store / Storefront -->
        @if($user->canAccessSection('storefront'))
        <div class="pt-4 pb-1">
            <p class="admin-nav-section">Online Store</p>
        </div>
        <a href="{{ route('admin.homepage.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.homepage.index') || request()->routeIs('admin.homepage.sections*') || request()->routeIs('admin.homepage.hero*') || request()->routeIs('admin.homepage.navigation*') || request()->routeIs('admin.homepage.site-settings*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Homepage
        </a>
        <a href="{{ route('admin.homepage.shop-filters') }}"
           class="admin-nav-item {{ request()->routeIs('admin.homepage.shop-filters*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6h18M6 12h12M10 18h4"/>
            </svg>
            Shop Filters
        </a>
        <a href="{{ route('admin.homepage.qualities') }}"
           class="admin-nav-item {{ request()->routeIs('admin.homepage.qualities*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3l2.6 6 6.4.6-5 4.6 1.4 6.4L12 17l-5.4 3.6L8 14.2 3 9.6l6.4-.6L12 3z"/>
            </svg>
            Our Qualities
        </a>
        @endif

        <!-- Content Section -->
        @if($user->canAccessSection('content'))
        <div class="pt-4 pb-1">
            <p class="admin-nav-section">Content</p>
        </div>
        <a href="{{ route('admin.pages.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Pages
        </a>
        <a href="{{ route('admin.blog-posts.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.blog-posts.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
            </svg>
            Blog Posts
        </a>
        <a href="{{ route('admin.reviews.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            Reviews
        </a>
        @endif

        <!-- Support -->
        <div class="pt-4 pb-1">
            <p class="admin-nav-section">Support</p>
        </div>
        <a href="{{ route('admin.enquiries.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.enquiries.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Enquiries
            @php $unreadEnquiries = \App\Models\Enquiry::where('status', 'new')->count(); @endphp
            @if($unreadEnquiries > 0)
                <span class="ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none" style="background: #e74c3c; color: white;">{{ $unreadEnquiries }}</span>
            @endif
        </a>
        <a href="{{ route('admin.support-tickets.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.support-tickets.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
            </svg>
            Tickets
            @php $openTickets = \App\Models\SupportTicket::where('status', 'open')->count(); @endphp
            @if($openTickets > 0)
                <span class="ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none" style="background: #e74c3c; color: white;">{{ $openTickets }}</span>
            @endif
        </a>

        <!-- Analytics -->
        @if($user->canAccessSection('reports'))
        <div class="pt-4 pb-1">
            <p class="admin-nav-section">Analytics</p>
        </div>
        <a href="{{ route('admin.reports.sales') }}"
           class="admin-nav-item {{ request()->routeIs('admin.reports.sales') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Sales Report
        </a>
        <a href="{{ route('admin.reports.analytics') }}"
           class="admin-nav-item {{ request()->routeIs('admin.reports.analytics') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
            </svg>
            Analytics
        </a>
        @endif

        <!-- Chat Analytics -->
        <a href="{{ route('admin.chatbot.analytics') }}"
           class="admin-nav-item {{ request()->routeIs('admin.chatbot.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.9 9.9 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            Chat Analytics
        </a>

        <!-- Audit Log -->
        <a href="{{ route('admin.audit-logs.index') }}"
           class="admin-nav-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            Audit Log
        </a>

        <!-- Settings -->
        @if($user->canAccessSection('settings'))
        <div class="pt-4 pb-1">
            <p class="admin-nav-section">Settings</p>
        </div>
        <a href="{{ route('admin.settings.general') }}"
           class="admin-nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Settings
        </a>
        @endif
    </nav>
</aside>
