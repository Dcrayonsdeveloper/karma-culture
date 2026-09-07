{{--
    The return window, told live.

    This used to be a server-rendered `diffForHumans()` string - "Returns open 3
    seconds from now." - printed once and then frozen. With the waiting period
    now measured in minutes rather than hours, that sentence is usually stale
    before the customer has finished reading it, and the Request Return button
    it promises never appeared until the page was reloaded by hand. The customer
    was left watching a number that would not move, waiting for a button that
    would not come.

    So the wait is counted down in the browser, and the button is rendered up
    front and revealed the moment the countdown reaches zero. Nothing is fetched
    and nothing is reloaded.

    Clock skew is the trap. The countdown targets a moment in SERVER time but
    runs against the visitor's clock, which is routinely minutes out - and a
    browser that is behind would keep the button hidden long after the server
    had begun allowing the return. So the server stamps its own "now" beside the
    deadline, and every tick measures elapsed time from that stamp rather than
    trusting the visitor's absolute clock.

    The three states are decided on the server, not in Alpine, so the first
    paint is already correct: an order that is open shows a plain button with no
    JavaScript at all, and only an order that is genuinely still waiting pays
    for a timer.

    Eligibility remains the server's decision - the target page re-checks it -
    so the worst a button revealed a second early can do is show a form.
--}}

@props([
    'order',
    // 'sidebar' is the narrow column on the account order page; 'card' is the
    // full-width panel on the public tracking page.
    'variant' => 'sidebar',
    'href' => null,
])

@php
    $kkWindowDays = (int) \App\Models\Setting::get('return_window_days', 7);
    $kkWaitMinutes = (int) \App\Models\Setting::get('return_min_minutes', 0);

    $kkDeliveredAt = $order->status === 'delivered' ? $order->delivered_at : null;
    $kkOpensAt = $kkDeliveredAt?->copy()->addMinutes($kkWaitMinutes);
    $kkClosesAt = $kkDeliveredAt?->copy()->addDays($kkWindowDays);

    $kkClosed = $kkClosesAt && $kkClosesAt->isPast();
    $kkWaiting = ! $kkClosed && $kkOpensAt && $kkOpensAt->isFuture();
    $kkOpen = ! $kkClosed && ! $kkWaiting && $kkDeliveredAt;

    $kkHref = $href ?: route('account.returns.create', ['order' => $order->id]);
@endphp

@if($kkDeliveredAt)
    @if($kkClosed)
        {{-- Settled: this cannot become untrue while the page is open, so it is
             a plain sentence with no timer behind it. The tracking page said
             nothing at all in this state, which read as "returns do not exist". --}}
        <p @class([
            'text-[12px] text-neutral-500 text-center px-2' => $variant === 'sidebar',
            'text-[13px] text-neutral-500 mb-4' => $variant !== 'sidebar',
        ])>
            The {{ $kkWindowDays }}-day return window for this order has closed.
        </p>

    @elseif($kkOpen)
        {{-- Open already. No countdown, no x-cloak, no flash of a zeroed timer
             on the way to the same button. --}}
        @if($variant === 'sidebar')
            <a href="{{ $kkHref }}"
               class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-neutral-200 text-neutral-700 text-[13px] font-medium rounded-lg hover:bg-neutral-50 hover:border-neutral-300 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
                Request Return
            </a>
        @else
            <div class="bg-white border border-neutral-100 rounded-xl p-5 mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-[15px] font-semibold text-neutral-900">Not quite right?</h2>
                    <p class="text-[13px] text-neutral-600 mt-0.5">You can request a return or exchange until {{ $kkClosesAt->format('d M Y') }}.</p>
                </div>
                <a href="{{ $kkHref }}"
                   class="shrink-0 inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors"
                   style="background:#2D1810;">
                    Request a Return
                </a>
            </div>
        @endif

    @else
        {{-- Still waiting. The only state that needs a clock. --}}
        <div
            x-data="{
                opensAt: {{ (int) $kkOpensAt->getPreciseTimestamp(3) }},
                serverNow: {{ (int) now()->getPreciseTimestamp(3) }},
                clientStart: 0,
                remaining: 1,
                timer: null,

                init() {
                    this.clientStart = Date.now();
                    this.tick();

                    if (this.remaining > 0) {
                        this.timer = setInterval(() => this.tick(), 1000);
                    }
                },

                {{-- Elapsed time is measured on the client but added to the
                     SERVER's clock, so a machine that is ten minutes fast still
                     counts the same number of seconds. --}}
                tick() {
                    const serverNowEstimate = this.serverNow + (Date.now() - this.clientStart);
                    this.remaining = Math.max(0, this.opensAt - serverNowEstimate);

                    if (this.remaining === 0 && this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                },

                get isOpen() {
                    return this.remaining === 0;
                },

                {{-- Coarse units while the wait is long, seconds once it is
                     short. A frozen 'in 2 days' reads as broken; a seconds
                     counter two days out is only noise. --}}
                get label() {
                    const totalSeconds = Math.ceil(this.remaining / 1000);
                    const days = Math.floor(totalSeconds / 86400);
                    const hours = Math.floor((totalSeconds % 86400) / 3600);
                    const minutes = Math.floor((totalSeconds % 3600) / 60);
                    const seconds = totalSeconds % 60;

                    if (days > 0) {
                        return days + (days === 1 ? ' day ' : ' days ') + hours + 'h';
                    }
                    if (hours > 0) {
                        return hours + 'h ' + String(minutes).padStart(2, '0') + 'm';
                    }
                    if (minutes > 0) {
                        return minutes + 'm ' + String(seconds).padStart(2, '0') + 's';
                    }
                    return seconds + 's';
                },
            }"
            @class(['mb-4' => $variant !== 'sidebar'])
        >
            @if($variant === 'sidebar')
                <a x-show="isOpen" x-cloak href="{{ $kkHref }}"
                   class="flex items-center justify-center gap-2 w-full px-4 py-2.5 border border-neutral-200 text-neutral-700 text-[13px] font-medium rounded-lg hover:bg-neutral-50 hover:border-neutral-300 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    Request Return
                </a>

                {{-- The server already knows this order is waiting, so the
                     sentence is rendered filled in and Alpine only keeps it
                     moving. Nothing flickers if JavaScript is slow. --}}
                <p x-show="! isOpen" class="text-[12px] text-neutral-500 text-center px-2">
                    Returns open in <span x-text="label" class="font-semibold text-neutral-700 tabular-nums">{{ $kkOpensAt->diffForHumans(null, true) }}</span>.
                </p>
            @else
                <div class="bg-white border border-neutral-100 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-[15px] font-semibold text-neutral-900">Not quite right?</h2>
                        <p x-show="isOpen" x-cloak class="text-[13px] text-neutral-600 mt-0.5">
                            You can request a return or exchange until {{ $kkClosesAt->format('d M Y') }}.
                        </p>
                        <p x-show="! isOpen" class="text-[13px] text-neutral-600 mt-0.5">
                            Returns open in <span x-text="label" class="font-semibold text-neutral-800 tabular-nums">{{ $kkOpensAt->diffForHumans(null, true) }}</span>.
                        </p>
                    </div>
                    <a x-show="isOpen" x-cloak href="{{ $kkHref }}"
                       class="shrink-0 inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors"
                       style="background:#2D1810;">
                        Request a Return
                    </a>
                </div>
            @endif
        </div>
    @endif
@endif
