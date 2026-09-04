{{--
    Full-page shopping assistant.

    Same conversation as the floating widget (same endpoints, same message
    shape), but with room to actually read it: product cards get a real grid
    instead of a 108px horizontal strip, and the transcript is restored from
    chatbot_messages on load rather than starting empty.
--}}
@php
    // Bundled mark kept in its own variable so a custom logo whose file has gone
    // missing degrades to it instead of leaving empty discs down the transcript.
    $kkBotLogoFallback = asset_v('images/karmaa-kulture-logo.png');
    $kkBotLogo = \App\Models\Setting::get('site_logo', '')
        ? asset_v('storage/' . \App\Models\Setting::get('site_logo'))
        : $kkBotLogoFallback;

    // ChatbotController::message() validates 'message' => max:300 and answers
    // 422 for anything longer. The composer's own cap and the guard inside
    // send() are both rendered from this one number so the page cannot quietly
    // drift away from the rule the endpoint actually enforces.
    $kkChatMaxLength = 300;
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
            <div class="flex flex-wrap items-center gap-3 mb-5">
                <div class="w-11 h-11 rounded-full bg-[#2D1810] flex items-center justify-center shrink-0">
                    <img src="{{ $kkBotLogo }}" alt="" class="w-6 h-6 object-contain" data-fallback="{{ $kkBotLogoFallback }}">
                </div>
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 leading-tight">Shopping Assistant</h1>
                    <p class="text-sm text-neutral-600 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        Online &middot; AI powered
                    </p>
                </div>
                <a href="{{ route('shop') }}"
                   class="ml-auto shrink-0 py-2 text-sm font-medium text-[#3A6166] hover:text-[#2A494D] transition-colors">
                    Back to shopping
                </a>
            </div>

            {{-- Conversation --}}
            <div class="bg-white border border-neutral-200 rounded-2xl shadow-sm flex flex-col overflow-hidden"
                 {{-- dvh, not vh: vh ignores the mobile URL bar, so the composer sat
                      below the fold on a phone until the bar auto-hid. --}}
                 style="height: min(68dvh, 640px); min-height: 380px;">

                {{-- overscroll-contain stops a scroll past either end of the
                     conversation from scrolling the page behind it. --}}
                <div x-ref="messageList" class="flex-1 overflow-y-auto overflow-x-hidden overscroll-contain p-4 sm:p-5 space-y-4 bg-neutral-50/50">

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
                                <img src="{{ $kkBotLogo }}" alt="" class="w-8 h-8 object-contain" data-fallback="{{ $kkBotLogoFallback }}">
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
                                        <img src="{{ $kkBotLogo }}" alt="" class="w-4 h-4 object-contain" data-fallback="{{ $kkBotLogoFallback }}">
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
                                                        {{-- Contained: the assistant recommends whatever the
                                                             catalogue holds, and a cover crop of a flat-lay or a
                                                             size chart cut the product out of its own card. The
                                                             blurred copy behind fills the square. --}}
                                                        <div class="kk-media kk-media--zoom w-full aspect-square">
                                                            <img class="kk-media__fill" :src="product.image || '{{ asset_v('images/no-product-image.svg') }}'"
                                                                 alt="" aria-hidden="true" loading="lazy">
                                                            <img :src="product.image || '{{ asset_v('images/no-product-image.svg') }}'" :alt="product.name" loading="lazy"
                                                                 data-fallback="{{ asset_v('images/no-product-image.svg') }}">
                                                            <template x-if="!product.in_stock">
                                                                <span class="absolute inset-x-0 bottom-0 z-10 bg-neutral-900/75 text-white text-[10px] text-center py-1">
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
                                <img src="{{ $kkBotLogo }}" alt="" class="w-4 h-4 object-contain" data-fallback="{{ $kkBotLogoFallback }}">
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
                                    class="shrink-0 min-h-10 sm:min-h-0 px-3 py-1.5 text-xs font-medium text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-full transition-colors">
                                {{ $chip['label'] }}
                            </button>
                        @endforeach
                    </div>
                </template>

                {{-- Composer.

                     novalidate is deliberate and means what it means everywhere
                     else in this codebase: send() below does the whole job for
                     this form, so the site-wide validator in app.js keeps its
                     hands off and never prints a second note beside this one.

                     The flex row moved inside a wrapper so the refusal message
                     has a full-width line of its own underneath it - .kk-field-error
                     is flex-basis:100% and would otherwise have been squeezed
                     into the row between the box and the send button. --}}
                <form @submit.prevent="send()" novalidate
                      class="p-3 border-t border-neutral-100 bg-white">
                    <div class="flex items-end gap-2">
                        <textarea
                            x-ref="chatInput"
                            x-model="inputText"
                            @keydown="composerKeydown($event)"
                            {{-- Editing the message retires the note about it:
                                 a refusal that outlives the text it was about is
                                 just noise the shopper has to read past. --}}
                            @input="composerError = ''"
                            rows="1"
                            maxlength="{{ $kkChatMaxLength }}"
                            enterkeyhint="send"
                            placeholder="Ask about sizes, colours, delivery, returns…"
                            :aria-invalid="composerError ? 'true' : 'false'"
                            :aria-describedby="composerError ? 'kk-chat-composer-error' : null"
                            class="flex-1 resize-none px-4 py-2.5 bg-neutral-50 border border-neutral-300 rounded-xl text-sm text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-[#3A6166]/40 focus:border-[#3A6166] transition-all max-h-32"
                        ></textarea>
                        <button type="submit"
                                :disabled="isTyping || !inputText.trim()"
                                class="shrink-0 w-11 h-11 rounded-xl bg-[#2D1810] text-white flex items-center justify-center hover:bg-[#1F1109] disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5M5 12l7-7 7 7"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Anything wrong with the message itself is said here, next
                         to the box it is about, and never as a bubble in the
                         transcript as well - the assistant did not say it. x-if
                         rather than x-show so the node is inserted at the moment
                         it applies, which is what makes role="alert" announce. --}}
                    <template x-if="composerError">
                        <p id="kk-chat-composer-error" role="alert" class="kk-field-error" x-text="composerError"></p>
                    </template>
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

                // The single slot for anything wrong with the composer, whether
                // this page worked it out before sending or the server said so.
                // One slot means one message: a length note cannot pile up
                // underneath the refusal from the previous attempt.
                composerError: '',

                // Mirrors the max:300 the endpoint enforces - rendered from the
                // one constant at the top of this file, so the guard in send()
                // and the cap on the box can never say different numbers.
                maxLength: {{ $kkChatMaxLength }},

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

                {{-- Alpine has no `.exact` modifier, so `@keydown.enter.exact`
                     read "exact" as a second key name and the handler never
                     fired -- Enter just inserted a newline. Match the key by
                     hand instead, and skip keydowns raised mid-IME composition
                     (Android soft keyboards fire those with keyCode 229) so a
                     predictive-text commit is never mistaken for "send".

                     Kept as a Blade comment: as a JS comment it shipped to the
                     browser, where the words above are indistinguishable from a
                     live `.exact` binding. --}}
                composerKeydown(event) {
                    if (event.key !== 'Enter' && event.keyCode !== 13) return;
                    if (event.shiftKey || event.isComposing || event.keyCode === 229) return;

                    event.preventDefault();
                    this.send();
                },

                async send(preset) {
                    const text = (preset ?? this.inputText).trim();
                    if (!text || this.isTyping) return;

                    // The refusal on screen belongs to the attempt before this
                    // one. It goes now, at the start of the submission, rather
                    // than when an answer comes back - otherwise a stale note
                    // sits under the box for the whole round trip and reads as
                    // if it were about the message just sent.
                    this.composerError = '';

                    // The endpoint validates the message at max:300. Nothing on
                    // this page used to: the box had no cap and send() only
                    // checked the text was non-empty, so a long paste was POSTed,
                    // came back 422, and the catch below - which had no branch
                    // for that status - told the shopper "Something went wrong",
                    // which is both untrue and gives them nothing to act on.
                    // Check the one rule we know here, say the thing that fixes
                    // it, and make no request at all.
                    if (text.length > this.maxLength) {
                        this.composerError = 'Please shorten your message to ' + this.maxLength + ' characters or fewer (it is currently ' + text.length + ').';
                        this.$nextTick(() => this.$refs.chatInput?.focus());
                        return;
                    }

                    this.inputText = '';
                    // Held by reference: if the server refuses the message the
                    // catch below has to find this exact bubble again to take it
                    // back out, and matching on content would pick the wrong one
                    // when the same question is asked twice.
                    const pending = { role: 'user', content: text };
                    this.messages.push(pending);
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
                        const failure = this.describeFailure(error);

                        if (failure.composer) {
                            // The server refused the message itself, so it never
                            // became part of the conversation. Leaving the bubble
                            // where it is would show it as sent and then answered
                            // by silence, so take it back out, put the text back
                            // where it can be edited, and let the composer carry
                            // the reason.
                            const at = this.messages.indexOf(pending);
                            if (at !== -1) this.messages.splice(at, 1);

                            // Only when nothing new has been typed meanwhile -
                            // the box stays enabled while a send is in flight,
                            // and a restore must not overwrite what is in it.
                            if (!this.inputText.trim()) this.inputText = text;

                            this.composerError = failure.composer;
                            this.$nextTick(() => this.$refs.chatInput?.focus());
                        } else {
                            this.messages.push({ role: 'assistant', content: failure.reply, products: [] });
                        }
                    } finally {
                        this.isTyping = false;
                        this.$nextTick(() => {
                            this.scrollToBottom();
                            this.$refs.chatInput?.focus();
                        });
                    }
                },

                {{-- The one place a failed send becomes a sentence, and the one
                     place that decides where the sentence goes: `composer` is a
                     note about what was typed and belongs under the box,
                     `reply` is something the conversation has to say and belongs
                     in the transcript. Exactly one of the two is ever set, so
                     the same words can never appear in both places.

                     window.kkApiError owns the status-to-English map and the
                     rule that matters most here: a message from a 4xx is
                     something the endpoint deliberately chose to say, while a
                     message from a 5xx is the exception's own text and must
                     never reach a shopper. Three things it cannot know are
                     added on top - this endpoint writes its deliberate wording
                     into `reply` rather than `message`, its 429 comes from the
                     throttle middleware rather than the controller, and a 422
                     about `message` is about the composer. --}}
                describeFailure(error) {
                    const failure = window.kkApiError(error);
                    const body = error?.response?.data;

                    if (failure.status === 422) {
                        // Only the composer's own message is trusted for
                        // display. Laravel's top-level 422 text is whichever
                        // rule failed first, which for a bad history entry
                        // reads "The history.3.content field must not be
                        // greater than 2000 characters" - the request's
                        // internal shape, not a sentence for a shopper.
                        return failure.fields.message
                            ? { composer: failure.fields.message, reply: '' }
                            : { composer: '', reply: 'That message could not be sent. Please rephrase it and try again.' };
                    }

                    if (failure.status === 429) {
                        // Sent by the rate limiter, not by the controller, so
                        // the body carries the framework's "Too Many Attempts."
                        // Mid-conversation that reads as an accusation; this
                        // says the same thing in the assistant's voice.
                        return { composer: '', reply: 'You\'re chatting a little fast! Please wait a moment before sending another message.' };
                    }

                    if (failure.status >= 500) {
                        // Nothing a 5xx body says is fit to show - on a real
                        // outage it is a stack trace, an upstream HTML page or
                        // a database error, not the controller's own words.
                        return { composer: '', reply: 'The assistant is unavailable right now. Please try again in a moment.' };
                    }

                    // Below 500 the endpoint's own wording wins where it has
                    // any: the 401 body is the sign-in prompt written for a
                    // session that expired mid-conversation. Everything else -
                    // 403, 404, an expired CSRF token, or a request that never
                    // reached the server at all - falls to kkApiError's
                    // sentence for it.
                    const deliberate = body && typeof body.reply === 'string' ? body.reply.trim() : '';

                    return { composer: '', reply: deliberate || failure.message };
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
