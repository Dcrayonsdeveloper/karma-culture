{{--
    Full-page shopping assistant.

    Same conversation as the floating widget (same endpoints, same message
    shape), but with room to actually read it: product cards get a real grid
    instead of a 108px horizontal strip, and the transcript is restored from
    chatbot_messages on load rather than starting empty.
--}}
@php
    $kkBotLogo = \App\Models\Setting::get('site_logo', '')
        ? asset('storage/' . \App\Models\Setting::get('site_logo'))
        : asset('images/karmaa-kulture-logo.png');
@endphp

<x-layouts.app>
    <x-slot name="title">Shopping Assistant - {{ config('app.name') }}</x-slot>

    <div class="bg-neutral-50 border-b border-neutral-100">
        <div class="container mx-auto px-4 py-3">
            <x-breadcrumb :items="[['label' => 'Shopping Assistant', 'url' => null]]" />
        </div>
    </div>

    <div
        x-data="chatPage()"
        x-init="loadHistory()"
        class="container mx-auto px-4 py-6 sm:py-8"
    >
        <div class="mx-auto max-w-3xl">

            {{-- Header --}}
            <div class="flex items-center gap-3 mb-5">
                <div class="w-11 h-11 rounded-full bg-[#2D1810] flex items-center justify-center shrink-0">
                    <img src="{{ $kkBotLogo }}" alt="" class="w-6 h-6 object-contain">
                </div>
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 leading-tight">Shopping Assistant</h1>
                    <p class="text-sm text-neutral-600 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        Online &middot; AI powered
                    </p>
                </div>
                <a href="{{ route('shop') }}"
                   class="ml-auto shrink-0 text-sm font-medium text-[#3A6166] hover:text-[#2A494D] transition-colors">
                    Back to shopping
                </a>
            </div>

            {{-- Conversation --}}
            <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm flex flex-col overflow-hidden"
                 {{-- dvh, not vh: vh ignores the mobile URL bar, so the composer sat
                      below the fold on a phone until the bar auto-hid. --}}
                 style="height: min(68dvh, 640px); min-height: 380px;">

                <div x-ref="messageList" class="flex-1 overflow-y-auto overflow-x-hidden p-4 sm:p-5 space-y-4 bg-neutral-50/50">

                    {{-- Restoring --}}
                    <template x-if="isLoading">
                        <div class="h-full flex items-center justify-center">
                            <div class="flex items-center gap-2 text-sm text-neutral-500">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/>
                                </svg>
                                Loading your conversation&hellip;
                            </div>
                        </div>
                    </template>

                    {{-- Empty state --}}
                    <template x-if="!isLoading && messages.length === 0">
                        <div class="h-full flex flex-col items-center justify-center text-center px-6">
                            <div class="w-16 h-16 rounded-full bg-neutral-100 flex items-center justify-center mb-4">
                                <img src="{{ $kkBotLogo }}" alt="" class="w-8 h-8 object-contain">
                            </div>
                            <p class="text-lg font-semibold text-neutral-900">Hi there! &#128075;</p>
                            <p class="mt-1 text-sm text-neutral-600 max-w-sm">
                                Ask me about sizes, colours, delivery, returns or your orders &mdash; or pick one of the
                                suggestions below to get started.
                            </p>
                        </div>
                    </template>

                    {{-- Messages --}}
                    <template x-for="(msg, i) in messages" :key="i">
                        <div>
                            {{-- Customer --}}
                            <template x-if="msg.role === 'user'">
                                <div class="flex justify-end">
                                    <div class="max-w-[80%] px-4 py-2.5 rounded-2xl rounded-br-sm bg-[#2D1810] text-white text-sm leading-relaxed [overflow-wrap:anywhere]"
                                         x-text="msg.content"></div>
                                </div>
                            </template>

                            {{-- Assistant --}}
                            <template x-if="msg.role === 'assistant'">
                                <div class="flex gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-[#2D1810] flex items-center justify-center shrink-0 mt-0.5">
                                        <img src="{{ $kkBotLogo }}" alt="" class="w-4 h-4 object-contain">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="px-4 py-3 rounded-2xl rounded-tl-sm bg-white border border-neutral-200 text-sm text-neutral-800 leading-relaxed shadow-sm [overflow-wrap:anywhere]"
                                             x-html="formatBotMessage(msg.content)"></div>

                                        {{-- Product cards: a real grid, not a 108px strip --}}
                                        <template x-if="msg.products && msg.products.length > 0">
                                            <div class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-3">
                                                <template x-for="product in msg.products" :key="product.id">
                                                    <a :href="product.url"
                                                       @click="trackProductClick(product.id)"
                                                       class="group bg-white rounded-xl border border-neutral-200 overflow-hidden hover:shadow-md hover:border-[#6F9CA2]/40 transition-all">
                                                        <div class="relative w-full aspect-square bg-neutral-50 overflow-hidden">
                                                            <img :src="product.image || '/images/no-product-image.svg'" :alt="product.name" loading="lazy"
                                                                 onerror="this.src='/images/no-product-image.svg'"
                                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                                            <template x-if="!product.in_stock">
                                                                <span class="absolute inset-x-0 bottom-0 bg-neutral-900/75 text-white text-[10px] text-center py-1">
                                                                    Out of stock
                                                                </span>
                                                            </template>
                                                        </div>
                                                        <div class="p-2.5">
                                                            <p class="text-xs font-medium text-neutral-800 line-clamp-2 leading-snug" x-text="product.name"></p>
                                                            <div class="mt-1.5 flex items-baseline gap-1.5">
                                                                <span class="text-sm font-semibold text-neutral-900" x-text="product.price"></span>
                                                                <template x-if="product.has_discount">
                                                                    <span class="text-[11px] text-neutral-500 line-through" x-text="product.mrp"></span>
                                                                </template>
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

                    {{-- Typing --}}
                    <template x-if="isTyping">
                        <div class="flex gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-[#2D1810] flex items-center justify-center shrink-0">
                                <img src="{{ $kkBotLogo }}" alt="" class="w-4 h-4 object-contain">
                            </div>
                            <div class="px-4 py-3 rounded-2xl rounded-tl-sm bg-white border border-neutral-200 shadow-sm">
                                <div class="flex gap-1">
                                    <span class="w-1.5 h-1.5 bg-neutral-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                                    <span class="w-1.5 h-1.5 bg-neutral-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                                    <span class="w-1.5 h-1.5 bg-neutral-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Suggestions --}}
                <template x-if="messages.length === 0 && !isLoading">
                    <div class="px-4 py-2.5 border-t border-neutral-100 flex gap-2 overflow-x-auto scrollbar-none bg-white">
                        @foreach($quickChips as $chip)
                            <button type="button"
                                    @click="send(@js($chip['message']))"
                                    class="shrink-0 px-3 py-1.5 text-xs font-medium text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-full transition-colors">
                                {{ $chip['label'] }}
                            </button>
                        @endforeach
                    </div>
                </template>

                {{-- Composer --}}
                <form @submit.prevent="send()" novalidate
                      class="p-3 border-t border-neutral-100 bg-white flex items-end gap-2">
                    <textarea
                        x-ref="chatInput"
                        x-model="inputText"
                        @keydown.enter.exact.prevent="send()"
                        rows="1"
                        placeholder="Ask about sizes, colours, delivery, returns…"
                        class="flex-1 resize-none px-4 py-2.5 bg-neutral-50 border border-neutral-300 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all max-h-32"
                    ></textarea>
                    <button type="submit"
                            :disabled="isTyping || !inputText.trim()"
                            class="shrink-0 w-11 h-11 rounded-xl bg-[#2D1810] text-white flex items-center justify-center hover:bg-[#1F1109] disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5M5 12l7-7 7 7"/>
                        </svg>
                    </button>
                </form>
            </div>

            <p class="mt-3 text-center text-xs text-neutral-500">AI &middot; May occasionally make mistakes</p>
        </div>
    </div>

    {{-- Inline, not @push: components/layouts/app.blade.php yields no
         stacks, so a pushed script is silently discarded and chatPage()
         would never be defined — the page renders as a dead shell. --}}
    <script>
        function chatPage() {
            return {
                messages: [],
                inputText: '',
                isTyping: false,
                isLoading: true,
                conversationId: null,

                async loadHistory() {
                    try {
                        const { data } = await axios.get('{{ route('chatbot.history') }}');
                        if (data.conversation_id) this.conversationId = data.conversation_id;
                        if (Array.isArray(data.messages)) this.messages = data.messages;
                    } catch (e) {
                        // An unreadable transcript must not block a new conversation.
                    } finally {
                        this.isLoading = false;
                        this.$nextTick(() => this.scrollToBottom());
                    }
                },

                async send(preset) {
                    const text = (preset ?? this.inputText).trim();
                    if (!text || this.isTyping) return;

                    this.inputText = '';
                    this.messages.push({ role: 'user', content: text });
                    this.isTyping = true;
                    this.$nextTick(() => this.scrollToBottom());

                    try {
                        const history = this.messages
                            .slice(0, -1)
                            .slice(-10)
                            .map(m => ({ role: m.role, content: m.content }));

                        const { data } = await axios.post('{{ route('chatbot.message') }}', {
                            _token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                            message: text,
                            history: history,
                        });

                        if (data && data.conversation_id) this.conversationId = data.conversation_id;
                        this.messages.push({
                            role: 'assistant',
                            content: data.reply || 'Sorry, I didn\'t get a response. Please try again.',
                            products: data.products || [],
                        });
                    } catch (error) {
                        let errorMsg = 'Something went wrong. Please try again.';
                        if (error.response?.status === 401) {
                            errorMsg = error.response.data?.reply || 'Your session expired. Please sign in again.';
                        } else if (error.response?.status === 429) {
                            errorMsg = 'You\'re chatting a little fast! Please wait a moment before sending another message.';
                        } else if (error.response?.status === 503) {
                            errorMsg = error.response.data?.reply || 'The assistant is temporarily unavailable. Please try again later.';
                        }
                        this.messages.push({ role: 'assistant', content: errorMsg, products: [] });
                    } finally {
                        this.isTyping = false;
                        this.$nextTick(() => {
                            this.scrollToBottom();
                            this.$refs.chatInput?.focus();
                        });
                    }
                },

                trackProductClick(productId) {
                    try {
                        fetch('{{ route('chatbot.product-click') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            },
                            body: JSON.stringify({ product_id: productId, conversation_id: this.conversationId }),
                            keepalive: true,
                        }).catch(() => {});
                    } catch (e) {}
                },

                scrollToBottom() {
                    const el = this.$refs.messageList;
                    if (el) el.scrollTop = el.scrollHeight;
                },

                formatBotMessage(text) {
                    if (!text) return '';

                    const escaped = String(text)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');

                    let html = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

                    const lines = html.split('\n');
                    const result = [];
                    let inList = false;

                    for (const line of lines) {
                        const trimmed = line.trim();
                        if (trimmed.startsWith('- ')) {
                            if (!inList) {
                                result.push('<ul class="list-disc list-inside mt-1 space-y-0.5">');
                                inList = true;
                            }
                            result.push(`<li>${trimmed.slice(2)}</li>`);
                        } else {
                            if (inList) {
                                result.push('</ul>');
                                inList = false;
                            }
                            if (trimmed) result.push(`<p class="leading-relaxed">${trimmed}</p>`);
                        }
                    }
                    if (inList) result.push('</ul>');

                    return result.join('');
                },
            };
        }
    </script>
</x-layouts.app>
