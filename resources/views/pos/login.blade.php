<x-pos.layout>
<div class="pos-container" x-data="posLogin()" @keydown.window="handleKeydown($event)">
    <div class="flex items-center justify-center h-full" style="background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);">
        <div class="pos-card p-8 w-full max-w-md pos-fade-in" style="box-shadow: 0 8px 32px rgba(0,0,0,0.08);">

            {{-- Logo --}}
            <div class="text-center mb-6">
                @php $siteLogo = \App\Models\Setting::get('site_logo', ''); @endphp
                @if($siteLogo)
                    <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ config('app.name') }}" class="h-10 mx-auto mb-2">
                @else
                    <div class="text-2xl font-bold" style="color: var(--pos-primary);">{{ config('app.name') }}</div>
                @endif
                <div class="text-sm font-medium" style="color: var(--pos-text-muted);">Point of Sale</div>
            </div>

            {{-- Device Registration (if not registered) --}}
            <template x-if="!deviceRegistered">
                <div>
                    <h2 class="text-lg font-semibold text-center mb-4">Register Terminal</h2>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1.5" style="color: var(--pos-text-muted);">Terminal ID</label>
                        <input
                            type="text"
                            x-model="deviceId"
                            placeholder="e.g. POS-MAIN"
                            class="w-full px-4 py-3 rounded-lg border text-base focus:outline-none focus:ring-2"
                            style="border-color: var(--pos-border); focus:ring-color: var(--pos-primary);"
                            @keydown.enter="registerDevice()"
                        >
                    </div>
                    <p x-show="deviceError" x-text="deviceError" class="text-sm mb-3" style="color: var(--pos-danger);"></p>
                    <button @click="registerDevice()" :disabled="!deviceId.trim() || deviceLoading"
                            class="pos-btn pos-btn-primary w-full text-base py-3" style="font-size: 15px;">
                        <span x-show="!deviceLoading">Register Device</span>
                        <span x-show="deviceLoading">Registering...</span>
                    </button>
                    <p class="text-center text-xs mt-4" style="color: var(--pos-text-muted);">
                        Terminal ID is provided by your store administrator
                    </p>
                </div>
            </template>

            {{-- PIN Login --}}
            <template x-if="deviceRegistered">
                <div>
                    {{-- Terminal info --}}
                    <div class="text-center mb-5 pb-4" style="border-bottom: 1px solid var(--pos-border);">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium"
                             style="background: #F0FDFA; color: var(--pos-primary);">
                            <span class="w-2 h-2 rounded-full" style="background: var(--pos-success);"></span>
                            <span x-text="terminalName"></span> &middot; <span x-text="storeName"></span>
                        </div>
                    </div>

                    <h2 class="text-base font-semibold text-center mb-4">Enter Your PIN</h2>

                    {{-- PIN dots --}}
                    <div class="flex justify-center gap-3 mb-5"
                         :class="{ 'pos-shake': shakePin }"
                         @animationend="shakePin = false">
                        <template x-for="i in pinLength" :key="i">
                            <div class="w-4 h-4 rounded-full transition-all duration-150"
                                 :style="pin.length >= i
                                    ? 'background: var(--pos-primary); transform: scale(1.15);'
                                    : 'background: #E2E8F0;'">
                            </div>
                        </template>
                    </div>

                    {{-- Error message --}}
                    <p x-show="loginError" x-text="loginError" x-transition
                       class="text-sm text-center mb-3" style="color: var(--pos-danger);"></p>

                    {{-- Lockout message --}}
                    <div x-show="lockoutRemaining > 0" class="text-center mb-3 p-3 rounded-lg" style="background: #FEF2F2;">
                        <p class="text-sm font-medium" style="color: var(--pos-danger);">
                            Too many attempts. Try again in <span x-text="lockoutRemaining" class="pos-mono font-bold"></span>s
                        </p>
                    </div>

                    {{-- Numpad --}}
                    <div class="flex flex-col items-center gap-2" :class="{ 'opacity-50 pointer-events-none': lockoutRemaining > 0 }">
                        <div class="flex gap-2">
                            <button @click="addDigit('1')" class="pos-numpad-btn">1</button>
                            <button @click="addDigit('2')" class="pos-numpad-btn">2</button>
                            <button @click="addDigit('3')" class="pos-numpad-btn">3</button>
                        </div>
                        <div class="flex gap-2">
                            <button @click="addDigit('4')" class="pos-numpad-btn">4</button>
                            <button @click="addDigit('5')" class="pos-numpad-btn">5</button>
                            <button @click="addDigit('6')" class="pos-numpad-btn">6</button>
                        </div>
                        <div class="flex gap-2">
                            <button @click="addDigit('7')" class="pos-numpad-btn">7</button>
                            <button @click="addDigit('8')" class="pos-numpad-btn">8</button>
                            <button @click="addDigit('9')" class="pos-numpad-btn">9</button>
                        </div>
                        <div class="flex gap-2">
                            <button @click="removeDigit()" class="pos-numpad-btn text-lg" style="color: var(--pos-danger);" aria-label="Delete digit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414-6.414A2 2 0 0110.828 5H21a1 1 0 011 1v12a1 1 0 01-1 1H10.828a2 2 0 01-1.414-.586L3 12z"/></svg>
                            </button>
                            <button @click="addDigit('0')" class="pos-numpad-btn">0</button>
                            <button @click="submitPin()" class="pos-numpad-btn" style="background: var(--pos-primary); color: white; border-color: var(--pos-primary);" aria-label="Submit PIN">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Change terminal link --}}
                    <button @click="resetDevice()" class="w-full text-center text-xs mt-5 py-2" style="color: var(--pos-text-muted);">
                        Change Terminal
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function posLogin() {
    return {
        // Device registration
        deviceRegistered: {{ $register ? 'true' : 'false' }},
        deviceId: '{{ $deviceId ?? '' }}',
        deviceLoading: false,
        deviceError: '',
        terminalName: '{{ $register?->name ?? '' }}',
        storeName: '{{ $register?->store?->name ?? '' }}',

        // PIN login
        pin: '',
        pinLength: 4,
        shakePin: false,
        loginError: '',
        loginLoading: false,
        lockoutRemaining: 0,
        lockoutTimer: null,

        async registerDevice() {
            if (!this.deviceId.trim()) return;
            this.deviceLoading = true;
            this.deviceError = '';

            try {
                const response = await axios.post('{{ route("pos.register-device") }}', {
                    device_id: this.deviceId.trim()
                });
                if (response.data.success) {
                    this.deviceRegistered = true;
                    this.terminalName = response.data.terminal;
                    this.storeName = response.data.store;
                }
            } catch (error) {
                this.deviceError = error.response?.data?.message || 'Registration failed.';
            } finally {
                this.deviceLoading = false;
            }
        },

        resetDevice() {
            this.deviceRegistered = false;
            this.pin = '';
            this.loginError = '';
        },

        addDigit(d) {
            if (this.pin.length < this.pinLength && this.lockoutRemaining <= 0) {
                this.pin += d;
                this.loginError = '';

                // Auto-submit when full
                if (this.pin.length === this.pinLength) {
                    this.$nextTick(() => this.submitPin());
                }
            }
        },

        removeDigit() {
            this.pin = this.pin.slice(0, -1);
            this.loginError = '';
        },

        async submitPin() {
            if (this.pin.length < this.pinLength || this.loginLoading || this.lockoutRemaining > 0) return;
            this.loginLoading = true;
            this.loginError = '';

            try {
                const response = await axios.post('{{ route("pos.login") }}', {
                    pin: this.pin
                });

                if (response.data.success) {
                    window.location.href = response.data.redirect;
                }
            } catch (error) {
                const data = error.response?.data;
                this.loginError = data?.message || 'Login failed.';
                this.shakePin = true;
                this.pin = '';

                if (data?.locked) {
                    this.startLockout(data.remaining || 30);
                }
            } finally {
                this.loginLoading = false;
            }
        },

        startLockout(seconds) {
            this.lockoutRemaining = seconds;
            if (this.lockoutTimer) clearInterval(this.lockoutTimer);
            this.lockoutTimer = setInterval(() => {
                this.lockoutRemaining--;
                if (this.lockoutRemaining <= 0) {
                    clearInterval(this.lockoutTimer);
                    this.loginError = '';
                }
            }, 1000);
        },

        handleKeydown(e) {
            if (!this.deviceRegistered) return;
            if (this.lockoutRemaining > 0) return;

            if (e.key >= '0' && e.key <= '9') {
                e.preventDefault();
                this.addDigit(e.key);
            } else if (e.key === 'Backspace') {
                e.preventDefault();
                this.removeDigit();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                this.submitPin();
            }
        },
    };
}
</script>
@endpush
</x-pos.layout>
