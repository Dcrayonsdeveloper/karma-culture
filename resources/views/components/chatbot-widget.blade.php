{{--
    Karmaa Kulture AI Shopping Assistant Widget
    Floating chatbot in the bottom-right corner.
    Z-index z-[75] - above quick-view modal (z-70).
    Alpine.js: chatbotWidget() - defined in <script> below.
--}}

@php
    // Bundled mark kept in its own variable so a custom logo whose file has gone
    // missing degrades to it instead of leaving an empty disc in the chat header.
    $kkBotLogoFallback = asset_v('images/karmaa-kulture-logo.png');
    $kkBotLogo = \App\Models\Setting::get('site_logo', '')
        ? asset_v('storage/' . \App\Models\Setting::get('site_logo'))
        : $kkBotLogoFallback;

    // Same heading as the full page, so the widget and /chat do not introduce
    // themselves as two different things.
    $kkSiteName = \App\Models\Setting::get('site_name', 'Karmaa Kulture');

    // One definition of the openers, shared with the full-page chat.
    $kkQuickChips = app(\App\Http\Controllers\ChatbotController::class)->quickChips();
@endphp
<style>
    /* --kk-chat-stack is everything the panel sits on top of inside the flex
       column: the launcher (3.5rem), the column gap (0.75rem) and the root's
       own bottom offset. The panel subtracts it - plus the sticky header -
       from the viewport so it never rides up over the header. */
    .chatbot-widget-root {
        bottom: 73px !important;
        right: 1rem !important;
        --kk-chat-stack: calc(3.5rem + 0.75rem + 73px);
    }
    @media (min-width: 640px) {
        .chatbot-widget-root {
            bottom: 1.5rem !important;
            right: 1.5rem !important;
            --kk-chat-stack: calc(3.5rem + 0.75rem + 1.5rem);
        }
    }
    /* --kk-header-h is measured and published by partials/header.blade.php.
       The 6.5rem fallback is a touch taller than the real header, so the panel
       still clears it if that script has not run yet. */
    .chatbot-panel {
        height: max(220px, min(520px, calc(100dvh - var(--kk-header-h, 6.5rem) - var(--kk-chat-stack) - 0.75rem)));
    }

    /* Message typography.

       The storefront base sheet sets every <p> to 18px and forces
       font-weight 700 on p/span/li/textarea (resources/css/app.css). Both
       reached inside the chat bubbles: a reply rendered a size larger than
       the bullet list directly above it, and the whole conversation read as
       bold. The rules below are unlayered, so they outrank those layered
       base and utility declarations. Scoped to the bubbles and the composer
       so the surrounding chrome -- headings, chips, buttons -- keeps its own
       weights. */
    .kk-chat-msg,
    .kk-chat-msg p,
    .kk-chat-msg li,
    .kk-chat-msg a,
    .kk-chat-msg span { font-weight: 400 !important; }
    .kk-chat-msg strong { font-weight: 600 !important; }
    .kk-chat-msg,
    .kk-chat-msg p,
    .kk-chat-msg li { font-size: 13px; line-height: 1.55; }
    /* Consecutive paragraphs carry no margin under the Tailwind reset, so a
       multi-paragraph reply ran together as one block. */
    .kk-chat-msg p + p,
    .kk-chat-msg ul + p { margin-top: 0.45em; }

    /* On a phone the panel used to be a 520px-tall card floating above the
       launcher, so a strip of the page showed underneath it and the footer
       could be read through the gap while chatting. Below the sm breakpoint an
       open panel now fills the viewport: the root loses its corner offsets, the
       panel drops its width cap, its rounding and its computed height, and the
       launcher is taken out of the flow because the panel header carries its
       own close button.

       100dvh, not 100vh: vh ignores the mobile URL bar, which is what left the
       composer under the fold on the full-page chat. */
    @media (max-width: 639px) {
        .chatbot-widget-root.is-fullscreen {
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            gap: 0 !important;
        }

        .chatbot-widget-root.is-fullscreen .chatbot-panel {
            /* 100% of the root, not 100vw: vw counts the scrollbar gutter, so
               the panel hung a scrollbar-width past the right edge and took the
               close button with it. The root is inset 0, so its box is exactly
               the viewport. */
            width: 100% !important;
            max-width: none !important;
            height: 100% !important;
            border-radius: 0 !important;
            border: 0 !important;
        }

        .chatbot-widget-root.is-fullscreen .chatbot-launcher {
            display: none !important;
        }
    }

    .kk-chat-header-title { font-weight: 600 !important; }
    .kk-chat-input,
    .kk-chat-input::placeholder { font-weight: 400 !important; }
    .kk-chat-input { font-size: 13px; line-height: 1.55; }
</style>
<div
    x-data="chatbotWidget()"
    x-init="init()"
    class="chatbot-widget-root fixed z-[75] flex flex-col items-end gap-3"
    :class="isOpen && 'is-fullscreen'"
    style="position: fixed; z-index: 75; display: flex; flex-direction: column; align-items: flex-end; gap: 0.75rem;"
    @keydown.escape.window="isOpen && close()"
>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- CHAT PANEL                                                          --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="isOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        {{-- w-80 is exactly 320px, and the panel sits 1rem from the right edge —
             so on a 320px screen it was 16px wider than the viewport and pushed
             the whole page sideways. Size it against the viewport instead, and
             cap it at the old 384px so nothing changes on desktop.

             Height: see .chatbot-panel in the <style> block above - it is
             capped so the top edge stays below the sticky header and above the
             launcher, with dvh tracking the mobile URL bar. --}}
        class="chatbot-panel w-[calc(100vw-2rem)] max-w-[384px] sm:w-96 bg-white rounded-2xl shadow-2xl border border-neutral-100 flex flex-col overflow-hidden"
        style="transform-origin: bottom right;"
        role="dialog"
        aria-label="Shopping Assistant"
    >
        {{-- ── Header ──────────────────────────────────────────────────── --}}
        {{-- Header palette: the espresso-to-tan gradient runs diagonally with the
             lighter end under the controls, and the teal hairline picks up the
             accent already used on the quick chips and product cards. The labels
             are white -- they were #111 and rgba(0,0,0,.55) on a dark brown
             fill, which left the title and status line barely legible. --}}
        <div class="px-4 py-3 flex items-center justify-between shrink-0"
             style="background: linear-gradient(135deg, #2D1810 0%, #6B4227 55%, #8C5C34 100%); border-bottom: 2px solid #6F9CA2;">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 shadow-sm" style="background: white;">
                    <img src="{{ $kkBotLogo }}" alt="Karmaa Kulture" class="w-5 h-5 object-contain" data-fallback="{{ $kkBotLogoFallback }}">
                </div>
                <div>
                    <p class="kk-chat-header-title text-sm leading-tight" style="color: #FFFFFF;">{{ $kkSiteName }}</p>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background: #86EFAC;"></span>
                        <span class="text-[10px] font-medium" style="color: rgba(255,255,255,0.72);">Shopping assistant • Online</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
            <a
                href="{{ route('chat') }}"
                class="w-9 h-9 sm:w-7 sm:h-7 rounded-full flex items-center justify-center transition-colors"
                style="background: rgba(255,255,255,0.2); color: white;"
                onmouseover="this.style.background='rgba(255,255,255,0.35)'"
                onmouseout="this.style.background='rgba(255,255,255,0.2)'"
                aria-label="Open full page chat"
                title="Open in full page"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                          d="M4 8V4h4M16 4h4v4M20 16v4h-4M8 20H4v-4"/>
                </svg>
            </a>
            <button
                @click="close()"
                class="w-9 h-9 sm:w-7 sm:h-7 rounded-full flex items-center justify-center transition-colors"
                style="background: rgba(255,255,255,0.2); color: white;"
                onmouseover="this.style.background='rgba(255,255,255,0.35)'"
                onmouseout="this.style.background='rgba(255,255,255,0.2)'"
                aria-label="Close chat"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            </div>
        </div>

        {{-- ── Message List ─────────────────────────────────────────────── --}}
        <div
            x-ref="messageList"
            {{-- overflow-x-hidden: a single long word (the assistant quotes full
                 product URLs) is wider than the panel and would otherwise give
                 the whole conversation a horizontal scrollbar.

                 overscroll-contain: without it, scrolling past either end of the
                 conversation hands the gesture to the page underneath, so the
                 storefront slides around behind an open chat panel. --}}
            class="flex-1 overflow-y-auto overflow-x-hidden overscroll-contain p-3 space-y-3 bg-neutral-50/60"
        >
            {{-- Empty / Welcome state --}}
            <template x-if="messages.length === 0">
                <div class="flex flex-col items-center justify-center h-full text-center px-4 py-6">
                    <div class="w-14 h-14 rounded-full bg-[#8C5C34]/10 flex items-center justify-center mb-3">
                        <img src="{{ $kkBotLogo }}" alt="Karmaa Kulture" class="w-9 h-9 object-contain" data-fallback="{{ $kkBotLogoFallback }}">
                    </div>
                    @auth
                        {{-- First name only: a full name makes the greeting stiff. --}}
                        <p class="text-sm font-semibold text-neutral-800 mb-1">Hi {{ auth()->user()->first_name ?: auth()->user()->full_name }}! 👋</p>
                        <p class="text-xs text-neutral-600 leading-relaxed mb-4">I'm your shopping assistant. Ask me about products, orders, sizes, offers, or anything about the store!</p>
                        <div class="flex flex-wrap gap-1.5 justify-center">
                            <template x-for="chip in quickChips" :key="chip.label">
                                <button
                                    @click="sendQuickChip(chip.message)"
                                    class="text-[11px] px-3 py-2.5 sm:py-1.5 rounded-full border border-[#6F9CA2]/40 text-[#6F9CA2] bg-white hover:bg-[#6F9CA2]/8 transition-colors font-medium whitespace-nowrap"
                                    x-text="chip.label"
                                ></button>
                            </template>
                        </div>
                    @else
                        {{-- The assistant reads the customer's own orders, so it needs to
                             know who is asking. Returning here after login keeps the
                             shopper on the product they were looking at. --}}
                        <p class="text-sm font-semibold text-neutral-800 mb-1">Hi there! 👋</p>
                        <p class="text-xs text-neutral-600 leading-relaxed mb-4">
                            Sign in to chat with our shopping assistant. It can check your orders,
                            find sizes and colours, and share current offers.
                        </p>
                        <a href="{{ route('login') }}?redirect={{ urlencode(request()->fullUrl()) }}"
                           class="inline-flex items-center gap-2 px-5 py-3 sm:py-2 rounded-full text-white text-xs font-semibold transition-colors"
                           style="background:#8C5C34;" onmouseover="this.style.background='#2D1810'" onmouseout="this.style.background='#8C5C34'">
                            Sign in to chat
                        </a>
                        <p class="text-[11px] text-neutral-500 mt-3">
                            New here? <a href="{{ route('register') }}" class="underline" style="color:#8C5C34;">Create an account</a>
                        </p>
                    @endauth
                </div>
            </template>

            {{-- Messages --}}
            <template x-for="(msg, index) in messages" :key="index">
                <div>
                    {{-- User bubble --}}
                    <template x-if="msg.role === 'user'">
                        <div class="flex justify-end">
                            <div
                                class="kk-chat-msg max-w-[82%] px-3.5 py-2.5 rounded-2xl rounded-br-sm whitespace-pre-wrap text-white [overflow-wrap:anywhere]"
                                style="background-color: #8C5C34;"
                                x-text="msg.content"
                            ></div>
                        </div>
                    </template>

                    {{-- Bot bubble --}}
                    <template x-if="msg.role === 'assistant'">
                        <div class="flex items-start gap-2">
                            <div class="w-7 h-7 rounded-full bg-white ring-1 ring-neutral-200 flex items-center justify-center shrink-0 mt-0.5">
                                <img src="{{ $kkBotLogo }}" alt="Karmaa Kulture" class="w-4 h-4 object-contain" data-fallback="{{ $kkBotLogoFallback }}">
                            </div>
                            <div class="flex-1 min-w-0">
                                <div
                                    class="kk-chat-msg max-w-full px-3.5 py-2.5 rounded-2xl rounded-tl-sm text-neutral-800 bg-white border border-neutral-100 shadow-sm [overflow-wrap:anywhere]"
                                    x-html="formatBotMessage(msg.content)"
                                ></div>
                                {{-- Product cards --}}
                                <template x-if="msg.products && msg.products.length > 0">
                                    <div class="mt-2 flex gap-2 overflow-x-auto pb-1 scrollbar-none">
                                        <template x-for="product in msg.products" :key="product.id">
                                            <a
                                                :href="product.url"
                                                @click="trackProductClick(product.id)"
                                                class="shrink-0 w-[108px] bg-white rounded-xl border border-neutral-100 overflow-hidden hover:shadow-md hover:border-[#6F9CA2]/30 transition-all block"
                                            >
                                                {{-- Contained: this strip is only 108px wide, so a cover
                                                     crop of an off-ratio shot left the customer looking at
                                                     a patch of sleeve. The blurred copy behind fills the
                                                     square so the tile still reads as a solid card. --}}
                                                <div class="kk-media w-full aspect-square">
                                                    <img
                                                        class="kk-media__fill"
                                                        :src="product.image || '{{ asset_v('images/no-product-image.svg') }}'"
                                                        alt=""
                                                        aria-hidden="true"
                                                        loading="lazy"
                                                    >
                                                    <img
                                                        :src="product.image || '{{ asset_v('images/no-product-image.svg') }}'"
                                                        :alt="product.name"
                                                        loading="lazy"
                                                        data-fallback="{{ asset_v('images/no-product-image.svg') }}"
                                                    >
                                                    <template x-if="product.has_discount">
                                                        <span class="absolute top-1 left-1 z-10 text-[8px] font-bold px-1.5 py-0.5 rounded-full text-white" style="background-color: #8C5C34;">SALE</span>
                                                    </template>
                                                    <template x-if="!product.in_stock">
                                                        <div class="absolute inset-0 z-10 bg-white/75 flex items-center justify-center">
                                                            <span class="text-[9px] font-semibold text-neutral-600 text-center leading-tight px-1">Out of Stock</span>
                                                        </div>
                                                    </template>
                                                </div>
                                                <div class="p-1.5">
                                                    <p class="text-[10px] font-medium text-neutral-800 leading-tight line-clamp-2 mb-1" x-text="product.name"></p>
                                                    <p class="text-[11px] font-bold text-[#222]" x-text="product.price"></p>
                                                    <template x-if="product.has_discount">
                                                        <p class="text-[9px] text-neutral-600 line-through" x-text="product.mrp"></p>
                                                    </template>
                                                    <div class="mt-1.5 text-center text-[9px] font-semibold text-[#6F9CA2] border border-[#6F9CA2]/40 rounded-md py-0.5 hover:bg-[#6F9CA2] hover:text-white hover:border-[#6F9CA2] transition-colors">
                                                        View →
                                                    </div>
                                                </div>
                                            </a>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Typing indicator --}}
            <template x-if="isTyping">
                <div class="flex items-start gap-2">
                    <div class="w-7 h-7 rounded-full bg-white ring-1 ring-neutral-200 flex items-center justify-center shrink-0 mt-0.5">
                        <img src="{{ $kkBotLogo }}" alt="Karmaa Kulture" class="w-4 h-4 object-contain" data-fallback="{{ $kkBotLogoFallback }}">
                    </div>
                    <div class="px-4 py-3 rounded-2xl rounded-tl-sm bg-white border border-neutral-100 shadow-sm flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-neutral-400 animate-bounce" style="animation-delay: 0ms; animation-duration: 0.9s;"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-neutral-400 animate-bounce" style="animation-delay: 150ms; animation-duration: 0.9s;"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-neutral-400 animate-bounce" style="animation-delay: 300ms; animation-duration: 0.9s;"></span>
                    </div>
                </div>
            </template>
        </div>

        {{-- ── Quick chips (compact row, visible after first message) ──── --}}
        <template x-if="messages.length > 0 && !isTyping">
            <div class="px-3 py-2 border-t border-neutral-100 flex gap-1.5 overflow-x-auto scrollbar-none shrink-0 bg-white">
                <template x-for="chip in quickChips" :key="chip.label">
                    <button
                        @click="sendQuickChip(chip.message)"
                        class="shrink-0 text-[10px] px-2.5 py-2 sm:py-1 rounded-full border border-neutral-200 text-neutral-600 bg-neutral-50 hover:bg-[#6F9CA2]/10 hover:border-[#6F9CA2]/40 hover:text-[#6F9CA2] transition-colors whitespace-nowrap"
                        x-text="chip.label"
                    ></button>
                </template>
            </div>
        </template>

        {{-- ── Input ────────────────────────────────────────────────────── --}}
        <div class="px-3 py-3 border-t border-neutral-100 bg-white shrink-0">
            @guest
                {{-- No input for a guest: the endpoint would refuse them anyway,
                     and a dead text box reads as a broken chat. --}}
                <a href="{{ route('login') }}?redirect={{ urlencode(request()->fullUrl()) }}"
                   class="flex items-center justify-center w-full px-4 py-3 sm:py-2 rounded-full text-white text-xs font-semibold transition-colors"
                   style="background:#8C5C34;" onmouseover="this.style.background='#2D1810'" onmouseout="this.style.background='#8C5C34'">
                    Sign in to start chatting
                </a>
            @else
            {{-- A textarea, not an input: Shift+Enter has to be able to add a
                 line. Enter on its own still sends -- see composerKeydown(). --}}
            <div class="flex items-end gap-2">
                <textarea
                    x-ref="chatInput"
                    x-model="inputText"
                    rows="1"
                    maxlength="300"
                    enterkeyhint="send"
                    placeholder="Ask about products, orders, offers..."
                    :disabled="isTyping"
                    @keydown="composerKeydown($event)"
                    @input="autoGrowInput()"
                    class="kk-chat-input flex-1 resize-none overflow-y-auto px-3.5 py-2 bg-neutral-100 rounded-2xl border-0 text-neutral-800 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#6F9CA2]/40 disabled:opacity-50 transition-all"
                    style="max-height: 96px;"
                    autocomplete="off"
                ></textarea>
                <button
                    @click="sendMessage()"
                    :disabled="!inputText.trim() || isTyping"
                    class="w-10 h-10 sm:w-9 sm:h-9 rounded-full bg-[#8C5C34] hover:bg-[#2D1810] flex items-center justify-center text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
                    aria-label="Send message"
                >
                    <svg class="w-4 h-4 -mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
            @endguest
            <p class="text-[9px] text-neutral-600 text-center mt-1.5 leading-none">AI · May occasionally make mistakes</p>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TOGGLE BUTTON                                                       --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="chatbot-launcher relative">
        {{-- Orbiting wave rings (always visible when closed) --}}
        <template x-if="!isOpen">
            <div class="absolute inset-0 pointer-events-none">
                <span class="chatbot-wave-ring chatbot-wave-ring-1"></span>
                <span class="chatbot-wave-ring chatbot-wave-ring-2"></span>
                <span class="chatbot-wave-ring chatbot-wave-ring-3"></span>
            </div>
        </template>

        <button
            @click="toggle()"
            class="w-14 h-14 rounded-full text-white shadow-lg hover:shadow-xl flex flex-col items-center justify-center transition-all duration-200 hover:scale-105 active:scale-95 relative z-10"
            :style="isOpen ? 'background:#525252' : 'background:linear-gradient(135deg, #8C5C34, #2D1810)'"
            :aria-label="isOpen ? 'Close shopping assistant' : 'Open shopping assistant'"
            :aria-expanded="isOpen.toString()"
        >
            <template x-if="isOpen">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </template>
            <template x-if="!isOpen">
                <div class="flex flex-col items-center gap-0.5">
                    <span class="w-7 h-7 rounded-full bg-white flex items-center justify-center">
                        <img src="{{ $kkBotLogo }}" alt="" class="w-5 h-5 object-contain" data-fallback="{{ $kkBotLogoFallback }}">
                    </span>
                    <span class="text-[7px] font-bold tracking-wider uppercase leading-none">Ask AI</span>
                </div>
            </template>
        </button>

        {{-- Unread message count badge --}}
        <template x-if="!isOpen && unreadCount > 0">
            <span
                class="absolute -top-1 -right-1 w-4 h-4 rounded-full border-2 border-white flex items-center justify-center text-white font-bold pointer-events-none z-20"
                style="background-color: #8C5C34; font-size: 8px;"
                x-text="unreadCount > 9 ? '9+' : unreadCount"
            ></span>
        </template>
    </div>
</div>

<style>
    .chatbot-wave-ring {
        position: absolute;
        inset: -6px;
        border-radius: 50%;
        border: 2.5px solid transparent;
        border-top-color: #8C5C34;
        border-right-color: #8C5C34;
        opacity: 0.6;
    }
    .chatbot-wave-ring-1 {
        animation: chatbot-orbit 2.5s linear infinite;
    }
    .chatbot-wave-ring-2 {
        inset: -12px;
        border-width: 2px;
        border-top-color: #B08050;
        border-right-color: #B08050;
        opacity: 0.4;
        animation: chatbot-orbit 3.5s linear infinite reverse;
    }
    .chatbot-wave-ring-3 {
        inset: -18px;
        border-width: 1.5px;
        border-top-color: #F48FB1;
        border-right-color: #F48FB1;
        opacity: 0.25;
        animation: chatbot-orbit 4.5s linear infinite;
    }
    @keyframes chatbot-orbit {
        0%   { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<script>
function chatbotWidget() {
    return {
            // Set from the first reply; ties later clicks to this conversation.
            conversationId: null,

            trackProductClick(productId) {
                // Fire and forget: a failed beacon must never block the link.
                try {
                    const body = JSON.stringify({
                        product_id: productId,
                        conversation_id: this.conversationId,
                    });
                    fetch('/chatbot/product-click', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body,
                        keepalive: true,
                    }).catch(() => {});
                } catch (e) {}
            },
        isOpen:       false,
        isTyping:     false,
        hasBeenOpened: false,
        inputText:    '',
        messages:     [],
        unreadCount:  0,

        {{-- Rendered from ChatbotController::quickChips(), which is what its own
             docblock always claimed - the widget in fact kept a second copy here,
             and the two had drifted: this list went on offering the coupon opener
             after the assistant stopped answering it, and still asked about sizing
             "for my child" from the shop this was forked from.

             A Blade comment rather than a JS one: this block sits inside <script>,
             so a // comment ships to the browser - and a test asserting the page no
             longer names that opener would match the comment instead of the code. --}}
        quickChips: @json($kkQuickChips),

        historyLoaded: false,

        init() {
            this.$watch('messages', () => this.$nextTick(() => this.scrollToBottom()));
            this.$watch('isTyping', () => this.$nextTick(() => this.scrollToBottom()));
        },

        /**
         * Restore the saved conversation.
         *
         * This component is rebuilt from scratch on every page load and keeps
         * nothing client-side, so without this the customer saw an empty chat
         * each time they navigated — even though every turn was already stored
         * in chatbot_messages. Loaded lazily on first open so visitors who
         * never touch the widget cost nothing.
         */
        async loadHistory() {
            if (this.historyLoaded) return;
            this.historyLoaded = true;

            try {
                const { data } = await axios.get('{{ route('chatbot.history') }}');
                if (data.conversation_id) this.conversationId = data.conversation_id;
                if (Array.isArray(data.messages) && data.messages.length) {
                    // Anything typed before the response landed stays last.
                    this.messages = data.messages.concat(this.messages);
                }
            } catch (e) {
                // A missing transcript must not stop a new conversation.
            } finally {
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        toggle() {
            this.isOpen ? this.close() : this.openChat();
        },

        openChat() {
            this.isOpen       = true;
            this.hasBeenOpened = true;
            this.unreadCount  = 0;
            // Bring back whatever was said before this page load.
            //
            // Signed-in visitors only: /chatbot/history sits behind the `auth`
            // middleware, and the global axios interceptor turns a 401 into a
            // navigation to /login. The launcher is shown to guests as well -
            // the layout gates only on the assistant being configured - so
            // without this guard, merely OPENING the chat panel threw a guest
            // off the page they were reading and onto the sign-in form.
            @auth
            this.loadHistory();
            @endauth
            this.$nextTick(() => {
                this.scrollToBottom();
                this.$refs.chatInput?.focus();
            });
        },

        close() {
            this.isOpen = false;
        },

        /**
         * Enter sends, Shift+Enter starts a new line.
         *
         * Matched by hand rather than with a keydown modifier: Alpine has no
         * `exact` modifier, and a plain enter binding fires for the Shift
         * chord too, so the newline would never survive. Keydowns raised
         * mid-IME composition (Android soft keyboards report keyCode 229) are
         * a predictive-text commit, not a send.
         */
        composerKeydown(event) {
            if (event.key !== 'Enter' && event.keyCode !== 13) return;
            if (event.shiftKey || event.isComposing || event.keyCode === 229) return;

            event.preventDefault();
            this.sendMessage();
        },

        // The composer opens one row tall and grows with the text until it
        // hits the max-height on the element, which then scrolls.
        autoGrowInput() {
            const el = this.$refs.chatInput;
            if (!el) return;
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 'px';
        },

        async sendMessage() {
            const text = this.inputText.trim();
            if (!text || this.isTyping) return;

            this.inputText = '';
            // Collapse the box back to one row now that it is empty.
            this.$nextTick(() => this.autoGrowInput());
            this.messages.push({ role: 'user', content: text });
            this.isTyping = true;

            try {
                const history = this.messages
                    .slice(0, -1)
                    .slice(-10)
                    .map(m => ({ role: m.role, content: m.content }));

                // No _token in the body: bootstrap.js sets X-CSRF-TOKEN on every
                // axios request from the same meta tag, so sending it here as well
                // just put a field in the payload the endpoint never reads.
                const response = await axios.post('/chatbot/message', {
                    message: text,
                    history: history,
                });

                const data = response.data;

                // Ties any later product click to this conversation.
                if (data && data.conversation_id) this.conversationId = data.conversation_id;
                this.messages.push({
                    role:     'assistant',
                    content:  data.reply || 'Sorry, I didn\'t get a response. Please try again.',
                    products: data.products || [],
                });

                if (!this.isOpen) {
                    this.unreadCount++;
                }

            } catch (error) {
                this.messages.push({ role: 'assistant', content: this.failureReply(error), products: [] });

                if (!this.isOpen) {
                    this.unreadCount++;
                }
            } finally {
                this.isTyping = false;
                this.$nextTick(() => this.$refs.chatInput?.focus());
            }
        },

        /**
         * Turn a failed send into the sentence the assistant says back.
         *
         * The hand-rolled chain this replaces mapped 429 and 503 and nothing
         * else, so the two failures a shopper is most likely to hit both came
         * out as "Something went wrong. Please try again." A session that
         * expired mid-conversation got that instead of the sign-in prompt the
         * server had actually sent - the full-page chat handled the same 401
         * correctly, so the two surfaces disagreed about the same response -
         * and a message the endpoint rejected got it instead of the reason.
         *
         * window.kkApiError now owns the status-to-English map, so both
         * surfaces answer identically, and with it comes the rule that a
         * message from a 4xx is something the endpoint deliberately chose to
         * say while a message from a 5xx is the exception's own text and must
         * never be shown. Only what kkApiError cannot know is added here: this
         * endpoint writes its deliberate wording into `reply` rather than
         * `message`, and its 429 comes from the rate limiter rather than the
         * controller.
         */
        failureReply(error) {
            const failure = window.kkApiError(error);
            const body = error?.response?.data;

            if (failure.status === 422) {
                // The composer is capped at maxlength=300 to match the rule the
                // endpoint enforces, so this is all but unreachable - but when
                // it is reached the reason for the refusal is the only useful
                // thing to say. Laravel's top-level 422 text is skipped: it is
                // whichever rule failed first, which for a stale history entry
                // names an internal field like history.3.content.
                return failure.fields.message
                    || 'That message could not be sent. Please rephrase it and try again.';
            }

            if (failure.status === 429) {
                // Sent by the rate limiter, not by the controller, so the body
                // carries the framework's "Too Many Attempts." Mid-conversation
                // that reads as an accusation; this says it in the assistant's
                // own voice.
                return 'You\'re chatting a little fast! Please wait a moment before sending another message.';
            }

            if (failure.status >= 500) {
                // Nothing a 5xx body says is fit to show: on a real outage it is
                // a stack trace, a database error or an upstream HTML page
                // rather than the controller's own words.
                return 'The assistant is unavailable right now. Please try again in a moment.';
            }

            // Below 500 the endpoint's own wording wins where it has any - the
            // 401 body is the sign-in prompt this widget was missing. Anything
            // else, including a request that never reached the server at all,
            // falls to kkApiError's sentence for it.
            const deliberate = body && typeof body.reply === 'string' ? body.reply.trim() : '';

            return deliberate || failure.message;
        },

        sendQuickChip(message) {
            this.inputText = message;
            this.sendMessage();
        },

        scrollToBottom() {
            const el = this.$refs.messageList;
            if (el) el.scrollTop = el.scrollHeight;
        },

        formatBotMessage(text) {
            if (!text) return '';

            // 1. Escape HTML entities first for safety
            const escaped = String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');

            // 2. Convert **bold** → <strong>
            let html = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

            // 3. Build output line by line - detect bullet lists
            const lines = html.split('\n');
            const result = [];
            let inList = false;

            for (const line of lines) {
                const trimmed = line.trim();
                if (trimmed.startsWith('- ')) {
                    if (!inList) {
                        result.push('<ul class="list-disc list-inside mt-1 space-y-0.5 text-sm">');
                        inList = true;
                    }
                    result.push(`<li>${trimmed.slice(2)}</li>`);
                } else {
                    if (inList) {
                        result.push('</ul>');
                        inList = false;
                    }
                    if (trimmed) {
                        result.push(`<p class="leading-relaxed">${trimmed}</p>`);
                    }
                }
            }
            if (inList) result.push('</ul>');

            return result.join('');
        },
    };
}
</script>
