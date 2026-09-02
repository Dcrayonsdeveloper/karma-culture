{{--
    Voice search panel. Shown while the browser's own permission prompt is
    open, so the customer can see what is being asked for and why, and again
    whenever a voice session cannot start - a bare alert() gave no context and
    no way back.

    Included by both the desktop header bar and the full-screen mobile search,
    which run the same searchBar() component. It used to live inline in the
    header only, so on a phone every one of these states was invisible: the mic
    button simply did nothing and never said why.

    Every state a customer can act on ends in "Try again". A dead end here
    means reloading the page, and a shopper who has just been told to try again
    "in a moment" has no button to do it with.
--}}
<template x-if="micPanel">
    <div class="fixed inset-0 z-[80] flex items-center justify-center p-4"
         @click.self="closeMicPanel()" x-cloak>
        <div class="absolute inset-0" style="background: rgba(45,24,16,.55);"></div>
        <div class="relative w-full max-w-sm rounded-2xl p-6 text-center shadow-2xl"
             style="background:#fff;">
            <button type="button" @click="closeMicPanel()" aria-label="Close"
                    class="absolute top-3 right-3 w-8 h-8 rounded-full flex items-center justify-center text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="mx-auto mb-4 w-16 h-16 rounded-full flex items-center justify-center"
                 :style="micPanel === 'listening' ? 'background:#fdecea;' : 'background:#f4efe6;'">
                <svg class="w-7 h-7" :class="micPanel === 'listening' ? 'animate-pulse' : ''"
                     :style="micPanel === 'listening' ? 'color:#dc362e;' : 'color:#8C5C34;'"
                     fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                    <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                </svg>
            </div>

            <template x-if="micPanel === 'waiting'">
                <div>
                    <h3 class="text-base font-semibold" style="color:#2d1810;">Waiting for permission</h3>
                    <p class="text-sm mt-1" style="color:#616161;">Allow microphone access to search with your voice.</p>
                </div>
            </template>

            <template x-if="micPanel === 'listening'">
                <div>
                    <h3 class="text-base font-semibold" style="color:#2d1810;">Listening&hellip;</h3>
                    <p class="text-sm mt-1" style="color:#616161;">Say what you are looking for, like &ldquo;polo shirt in blue&rdquo;.</p>
                </div>
            </template>

            <template x-if="micPanel === 'blocked'">
                <div>
                    <h3 class="text-base font-semibold" style="color:#2d1810;">Microphone is blocked</h3>
                    <p class="text-sm mt-1" style="color:#616161;">
                        Your browser is blocking the microphone for this site, so it will not ask again.
                    </p>
                    <ol class="text-sm text-left mt-3 space-y-1 mx-auto" style="color:#444; max-width:17rem;">
                        <li>1. Click the icon left of the web address</li>
                        <li>2. Choose <strong>Site settings</strong></li>
                        <li>3. Set <strong>Microphone</strong> to <strong>Allow</strong></li>
                        <li>4. Reload this page</li>
                    </ol>
                </div>
            </template>

            <template x-if="micPanel === 'denied'">
                <div>
                    <h3 class="text-base font-semibold" style="color:#2d1810;">Permission not granted</h3>
                    <p class="text-sm mt-1" style="color:#616161;">Choose <strong>Allow</strong> when your browser asks, then try again.</p>
                    <button type="button" @click="toggleMic()" class="mt-4 px-5 py-2 rounded-full text-white text-xs font-semibold" style="background:#8C5C34;">Try again</button>
                </div>
            </template>

            {{-- audio-capture. The permission can be granted and the device still
                 unreadable: unplugged, or held open by a call or a recorder that
                 will not share it. --}}
            <template x-if="micPanel === 'nodevice'">
                <div>
                    <h3 class="text-base font-semibold" style="color:#2d1810;">Microphone unavailable</h3>
                    <p class="text-sm mt-1" style="color:#616161;">Check that a microphone is connected and that no other app is using it, then try again.</p>
                    <button type="button" @click="toggleMic()" class="mt-4 px-5 py-2 rounded-full text-white text-xs font-semibold" style="background:#8C5C34;">Try again</button>
                </div>
            </template>

            {{-- network. Chrome transcribes on Google's servers rather than on the
                 device, so voice search needs a working connection even when the
                 microphone itself is perfectly fine. This is one of the states
                 that used to read "Something went wrong". --}}
            <template x-if="micPanel === 'network'">
                <div>
                    <h3 class="text-base font-semibold" style="color:#2d1810;">Could not reach the voice service</h3>
                    <p class="text-sm mt-1" style="color:#616161;">Your browser sends speech away to be transcribed, so voice search needs a working connection. Check yours and try again.</p>
                    <button type="button" @click="toggleMic()" class="mt-4 px-5 py-2 rounded-full text-white text-xs font-semibold" style="background:#8C5C34;">Try again</button>
                </div>
            </template>

            <template x-if="micPanel === 'nospeech'">
                <div>
                    <h3 class="text-base font-semibold" style="color:#2d1810;">Didn&rsquo;t catch that</h3>
                    <p class="text-sm mt-1" style="color:#616161;">No speech was detected.</p>
                    <button type="button" @click="toggleMic()" class="mt-4 px-5 py-2 rounded-full text-white text-xs font-semibold" style="background:#8C5C34;">Try again</button>
                </div>
            </template>

            <template x-if="micPanel === 'unsupported'">
                <div>
                    <h3 class="text-base font-semibold" style="color:#2d1810;">Not supported here</h3>
                    <p class="text-sm mt-1" style="color:#616161;">Voice search works in Chrome and Edge. You can still type your search.</p>
                </div>
            </template>

            {{-- Whatever is left over. The code is on screen because this panel is
                 the only place a shopper's failure is ever visible to us - without
                 it every bug report is "it says something went wrong". --}}
            <template x-if="micPanel === 'error'">
                <div>
                    <h3 class="text-base font-semibold" style="color:#2d1810;">Voice search unavailable</h3>
                    <p class="text-sm mt-1" style="color:#616161;">Something went wrong. Please try again in a moment.</p>
                    <button type="button" @click="toggleMic()" class="mt-4 px-5 py-2 rounded-full text-white text-xs font-semibold" style="background:#8C5C34;">Try again</button>
                    <p class="text-[11px] mt-3" style="color:#9e9e9e;" x-show="micErrorCode">Error: <span x-text="micErrorCode"></span></p>
                </div>
            </template>
        </div>
    </div>
</template>
