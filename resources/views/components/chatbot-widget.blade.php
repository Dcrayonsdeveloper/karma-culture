{{--
    ForeverKids AI Shopping Assistant Widget
    Floating chatbot in the bottom-right corner.
    Z-index z-[75] — above quick-view modal (z-70).
    Alpine.js: chatbotWidget() — defined in <script> below.
--}}
<style>
    .chatbot-widget-root { bottom: 73px !important; right: 1rem !important; }
    @media (min-width: 640px) { .chatbot-widget-root { bottom: 1.5rem !important; right: 1.5rem !important; } }
</style>
<div
    x-data="chatbotWidget()"
    x-init="init()"
    class="chatbot-widget-root fixed z-[75] flex flex-col items-end gap-3"
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
        class="w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-neutral-100 flex flex-col overflow-hidden"
        style="height: 520px; transform-origin: bottom right;"
        role="dialog"
        aria-label="Shopping Assistant"
    >
        {{-- ── Header ──────────────────────────────────────────────────── --}}
        <div class="px-4 py-3 flex items-center justify-between shrink-0" style="background: linear-gradient(to right, #E91E63, #C2185B);">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 shadow-sm" style="background: white;">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="8" width="18" height="12" rx="3" fill="#E91E63"/>
                        <circle cx="8.5" cy="14" r="1.8" fill="white"/>
                        <circle cx="15.5" cy="14" r="1.8" fill="white"/>
                        <circle cx="8.5" cy="14" r="0.8" fill="#333"/>
                        <circle cx="15.5" cy="14" r="0.8" fill="#333"/>
                        <path d="M10 17.5c0-.28.45-.5 1-.5h2c.55 0 1 .22 1 .5s-.45.5-1 .5h-2c-.55 0-1-.22-1-.5z" fill="#333"/>
                        <rect x="10" y="4" width="4" height="4" rx="1" fill="#E91E63"/>
                        <line x1="12" y1="4" x2="12" y2="2" stroke="#E91E63" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="1.5" r="1" fill="#E91E63"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-sm leading-tight" style="color: #111;">Shopping Assistant</p>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background: #4ade80;"></span>
                        <span class="text-[10px] font-medium" style="color: rgba(0,0,0,0.55);">Online • AI Powered</span>
                    </div>
                </div>
            </div>
            <button
                @click="close()"
                class="w-7 h-7 rounded-full flex items-center justify-center transition-colors"
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

        {{-- ── Message List ─────────────────────────────────────────────── --}}
        <div
            x-ref="messageList"
            class="flex-1 overflow-y-auto p-3 space-y-3 bg-neutral-50/60"
        >
            {{-- Empty / Welcome state --}}
            <template x-if="messages.length === 0">
                <div class="flex flex-col items-center justify-center h-full text-center px-4 py-6">
                    <div class="w-14 h-14 rounded-full bg-[#E91E63]/10 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="8" width="18" height="12" rx="3" fill="#E91E63"/>
                            <circle cx="8.5" cy="14" r="1.8" fill="white"/>
                            <circle cx="15.5" cy="14" r="1.8" fill="white"/>
                            <circle cx="8.5" cy="14" r="0.8" fill="#333"/>
                            <circle cx="15.5" cy="14" r="0.8" fill="#333"/>
                            <path d="M10 17.5c0-.28.45-.5 1-.5h2c.55 0 1 .22 1 .5s-.45.5-1 .5h-2c-.55 0-1-.22-1-.5z" fill="#333"/>
                            <rect x="10" y="4" width="4" height="4" rx="1" fill="#E91E63"/>
                            <line x1="12" y1="4" x2="12" y2="2" stroke="#E91E63" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="12" cy="1.5" r="1" fill="#E91E63"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-neutral-800 mb-1">Hi there! 👋</p>
                    <p class="text-xs text-neutral-600 leading-relaxed mb-4">I'm your shopping assistant. Ask me about products, orders, sizes, offers, or anything about the store!</p>
                    <div class="flex flex-wrap gap-1.5 justify-center">
                        <template x-for="chip in quickChips" :key="chip.label">
                            <button
                                @click="sendQuickChip(chip.message)"
                                class="text-[11px] px-3 py-1.5 rounded-full border border-[#6F9CA2]/40 text-[#6F9CA2] bg-white hover:bg-[#6F9CA2]/8 transition-colors font-medium whitespace-nowrap"
                                x-text="chip.label"
                            ></button>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Messages --}}
            <template x-for="(msg, index) in messages" :key="index">
                <div>
                    {{-- User bubble --}}
                    <template x-if="msg.role === 'user'">
                        <div class="flex justify-end">
                            <div
                                class="max-w-[82%] px-3.5 py-2.5 rounded-2xl rounded-br-sm text-sm text-white leading-relaxed"
                                style="background-color: #E91E63;"
                                x-text="msg.content"
                            ></div>
                        </div>
                    </template>

                    {{-- Bot bubble --}}
                    <template x-if="msg.role === 'assistant'">
                        <div class="flex items-start gap-2">
                            <div class="w-7 h-7 rounded-full bg-[#E91E63] flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                    <rect x="4" y="9" width="16" height="10" rx="2.5" fill="white"/>
                                    <circle cx="9" cy="14" r="1.3" fill="#333"/>
                                    <circle cx="15" cy="14" r="1.3" fill="#333"/>
                                    <path d="M10.5 17c0-.2.3-.4.7-.4h1.6c.4 0 .7.2.7.4s-.3.4-.7.4h-1.6c-.4 0-.7-.2-.7-.4z" fill="#333"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div
                                    class="max-w-full px-3.5 py-2.5 rounded-2xl rounded-tl-sm text-sm text-neutral-800 bg-white border border-neutral-100 leading-relaxed shadow-sm"
                                    x-html="formatBotMessage(msg.content)"
                                ></div>
                                {{-- Product cards --}}
                                <template x-if="msg.products && msg.products.length > 0">
                                    <div class="mt-2 flex gap-2 overflow-x-auto pb-1 scrollbar-none">
                                        <template x-for="product in msg.products" :key="product.id">
                                            <a
                                                :href="product.url"
                                                class="shrink-0 w-[108px] bg-white rounded-xl border border-neutral-100 overflow-hidden hover:shadow-md hover:border-[#6F9CA2]/30 transition-all block"
                                            >
                                                <div class="relative w-full aspect-square bg-neutral-50 overflow-hidden">
                                                    <img
                                                        :src="product.image || '/images/no-product-image.svg'"
                                                        :alt="product.name"
                                                        class="w-full h-full object-cover"
                                                        loading="lazy"
                                                        onerror="this.src='/images/no-product-image.svg'"
                                                    >
                                                    <template x-if="product.has_discount">
                                                        <span class="absolute top-1 left-1 text-[8px] font-bold px-1.5 py-0.5 rounded-full text-white" style="background-color: #E91E63;">SALE</span>
                                                    </template>
                                                    <template x-if="!product.in_stock">
                                                        <div class="absolute inset-0 bg-white/75 flex items-center justify-center">
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
                    <div class="w-7 h-7 rounded-full bg-[#E91E63] flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <rect x="4" y="9" width="16" height="10" rx="2.5" fill="white"/>
                            <circle cx="9" cy="14" r="1.3" fill="#333"/>
                            <circle cx="15" cy="14" r="1.3" fill="#333"/>
                        </svg>
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
                        class="shrink-0 text-[10px] px-2.5 py-1 rounded-full border border-neutral-200 text-neutral-600 bg-neutral-50 hover:bg-[#6F9CA2]/10 hover:border-[#6F9CA2]/40 hover:text-[#6F9CA2] transition-colors whitespace-nowrap"
                        x-text="chip.label"
                    ></button>
                </template>
            </div>
        </template>

        {{-- ── Input ────────────────────────────────────────────────────── --}}
        <div class="px-3 py-3 border-t border-neutral-100 bg-white shrink-0">
            <div class="flex items-center gap-2">
                <input
                    x-ref="chatInput"
                    x-model="inputText"
                    type="text"
                    maxlength="300"
                    placeholder="Ask about products, orders, offers..."
                    :disabled="isTyping"
                    @keydown.enter.prevent="sendMessage()"
                    class="flex-1 px-3.5 py-2 text-sm bg-neutral-100 rounded-full border-0 text-neutral-800 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[#E91E63]/30 disabled:opacity-50 transition-all"
                    autocomplete="off"
                >
                <button
                    @click="sendMessage()"
                    :disabled="!inputText.trim() || isTyping"
                    class="w-9 h-9 rounded-full bg-[#E91E63] hover:bg-[#C2185B] flex items-center justify-center text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
                    aria-label="Send message"
                >
                    <svg class="w-4 h-4 -mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
            <p class="text-[9px] text-neutral-600 text-center mt-1.5 leading-none">AI · May occasionally make mistakes</p>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TOGGLE BUTTON                                                       --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="relative">
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
            :style="isOpen ? 'background:#525252' : 'background:linear-gradient(135deg, #E91E63, #C2185B)'"
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
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 5.58 2 10c0 2.24 1.12 4.26 2.92 5.7-.18 1.28-.8 2.42-1.74 3.3a.5.5 0 00.36.85c1.73-.04 3.36-.6 4.7-1.56.88.24 1.8.37 2.76.37 5.52 0 10-3.58 10-8S17.52 2 12 2z"/>
                        <circle cx="8.5" cy="10" r="1.2" fill="white"/>
                        <circle cx="12" cy="10" r="1.2" fill="white"/>
                        <circle cx="15.5" cy="10" r="1.2" fill="white"/>
                    </svg>
                    <span class="text-[7px] font-bold tracking-wider uppercase leading-none">Ask AI</span>
                </div>
            </template>
        </button>

        {{-- Unread message count badge --}}
        <template x-if="!isOpen && unreadCount > 0">
            <span
                class="absolute -top-1 -right-1 w-4 h-4 rounded-full border-2 border-white flex items-center justify-center text-white font-bold pointer-events-none z-20"
                style="background-color: #E91E63; font-size: 8px;"
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
        border-top-color: #E91E63;
        border-right-color: #E91E63;
        opacity: 0.6;
    }
    .chatbot-wave-ring-1 {
        animation: chatbot-orbit 2.5s linear infinite;
    }
    .chatbot-wave-ring-2 {
        inset: -12px;
        border-width: 2px;
        border-top-color: #F06292;
        border-right-color: #F06292;
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
        isOpen:       false,
        isTyping:     false,
        hasBeenOpened: false,
        inputText:    '',
        messages:     [],
        unreadCount:  0,

        quickChips: [
            { label: '📦 Track Order',    message: 'How can I track my order?' },
            { label: '🏷️ Current Offers', message: 'What offers and coupons are available right now?' },
            { label: '📏 Size Guide',      message: 'How do I find the right size for my child?' },
            { label: '↩️ Return Policy',   message: 'What is the return policy?' },
        ],

        init() {
            this.$watch('messages', () => this.$nextTick(() => this.scrollToBottom()));
            this.$watch('isTyping', () => this.$nextTick(() => this.scrollToBottom()));
        },

        toggle() {
            this.isOpen ? this.close() : this.openChat();
        },

        openChat() {
            this.isOpen       = true;
            this.hasBeenOpened = true;
            this.unreadCount  = 0;
            this.$nextTick(() => {
                this.scrollToBottom();
                this.$refs.chatInput?.focus();
            });
        },

        close() {
            this.isOpen = false;
        },

        async sendMessage() {
            const text = this.inputText.trim();
            if (!text || this.isTyping) return;

            this.inputText = '';
            this.messages.push({ role: 'user', content: text });
            this.isTyping = true;

            try {
                const history = this.messages
                    .slice(0, -1)
                    .slice(-10)
                    .map(m => ({ role: m.role, content: m.content }));

                const response = await axios.post('/chatbot/message', {
                    _token:  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    message: text,
                    history: history,
                });

                const data = response.data;
                this.messages.push({
                    role:     'assistant',
                    content:  data.reply || 'Sorry, I didn\'t get a response. Please try again.',
                    products: data.products || [],
                });

                if (!this.isOpen) {
                    this.unreadCount++;
                }

            } catch (error) {
                let errorMsg = 'Something went wrong. Please try again.';
                if (error.response?.status === 429) {
                    errorMsg = 'You\'re chatting a little fast! Please wait a moment before sending another message.';
                } else if (error.response?.status === 503) {
                    errorMsg = error.response.data?.reply || 'The assistant is temporarily unavailable. Please try again later.';
                }
                this.messages.push({ role: 'assistant', content: errorMsg, products: [] });

                if (!this.isOpen) {
                    this.unreadCount++;
                }
            } finally {
                this.isTyping = false;
                this.$nextTick(() => this.$refs.chatInput?.focus());
            }
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

            // 3. Build output line by line — detect bullet lists
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
