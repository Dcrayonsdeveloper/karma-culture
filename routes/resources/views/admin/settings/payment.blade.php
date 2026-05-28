<x-layouts.admin>
    <x-slot name="title">Payment Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Settings</h1>
        </div>
    </x-slot>

    @include('admin.settings.partials.nav', ['active' => 'payment'])

    @if(session('success'))
        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.625rem 1rem; background: #cdfee1; border: 1px solid #b3d8c0; border-radius: 0.5rem; font-size: 13px; color: #1a7a2e; margin-bottom: 1rem;">
            <svg style="width: 18px; height: 18px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.settings.payment.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: flex; flex-direction: column; gap: 1rem;">

            {{-- PayU --}}
            <div class="card" x-data="{ enabled: {{ ($settings['payu_enabled'] ?? '0') === '1' ? 'true' : 'false' }} }">
                <div style="padding: 0.875rem 1rem; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; background: #00AE4D; flex-shrink: 0;">
                            <span style="color: white; font-weight: 700; font-size: 12px; letter-spacing: -0.025em;">PayU</span>
                        </div>
                        <div>
                            <h3 style="font-size: 13px; font-weight: 600; color: #303030; display: flex; align-items: center; gap: 0.5rem; margin: 0;">
                                PayU
                                <span style="font-size: 10px; font-weight: 500; color: #005bd3; background: #d4edfc; padding: 0.125rem 0.375rem; border-radius: 0.25rem;">Recommended</span>
                            </h3>
                            <p style="font-size: 12px; color: #616161; margin: 0;">Accept payments via PayU — Cards, UPI, Net Banking, Wallets & more</p>
                        </div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="payu_enabled" value="1" x-model="enabled">
                        <div class="toggle-track"></div>
                    </label>
                </div>
                <div style="padding: 0 1rem 1rem 1rem; border-top: 1px solid #f0f0f0;" x-show="enabled" x-collapse>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; padding-top: 1rem;">
                        <div>
                            <label class="form-label">Merchant Key</label>
                            <input type="text" name="payu_merchant_key" value="{{ old('payu_merchant_key', $settings['payu_merchant_key'] ?? '') }}" placeholder="Your PayU Merchant Key" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Merchant Salt (v2)</label>
                            <input type="password" name="payu_merchant_salt" value="{{ old('payu_merchant_salt', $settings['payu_merchant_salt'] ?? '') }}" placeholder="••••••••••••" class="form-input">
                        </div>
                    </div>
                    <div style="max-width: 16rem; margin-top: 1rem;">
                        <label class="form-label">Mode</label>
                        <select name="payu_mode" class="form-select">
                            <option value="test" @selected(($settings['payu_mode'] ?? 'test') === 'test')>Test / Sandbox</option>
                            <option value="live" @selected(($settings['payu_mode'] ?? '') === 'live')>Live / Production</option>
                        </select>
                    </div>
                    <p style="font-size: 12px; color: #616161; margin-top: 0.75rem;">Get your credentials from <span style="font-weight: 500; color: #303030;">PayU Dashboard &rarr; Settings &rarr; Merchant Key & Salt</span></p>
                </div>
            </div>

            {{-- COD --}}
            <div class="card" x-data="{ enabled: {{ ($settings['cod_enabled'] ?? '0') === '1' ? 'true' : 'false' }} }">
                <div style="padding: 0.875rem 1rem; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 2.5rem; height: 2.5rem; background: #cdfee1; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg style="width: 1.25rem; height: 1.25rem; color: #1a7a2e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Cash on Delivery (COD)</p>
                            <p style="font-size: 12px; color: #616161; margin: 0;">Customer pays cash when order arrives</p>
                        </div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="cod_enabled" value="1" x-model="enabled">
                        <div class="toggle-track"></div>
                    </label>
                </div>
                <div style="padding: 0 1rem 1rem 1rem; border-top: 1px solid #f0f0f0;" x-show="enabled" x-collapse>
                    <label class="form-label" style="margin-top: 1rem;">Instructions for Customer <span style="color: #616161; font-weight: 400;">(optional)</span></label>
                    <textarea name="cod_instructions" rows="2" class="form-textarea" placeholder="e.g. Please keep exact change ready at delivery.">{{ old('cod_instructions', $settings['cod_instructions'] ?? '') }}</textarea>
                </div>
            </div>

        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Payment Settings</button>
        </div>
    </form>
</x-layouts.admin>
