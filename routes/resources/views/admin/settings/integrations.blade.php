<x-layouts.admin>
    <x-slot name="title">Integration Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Settings</h1>
        </div>
    </x-slot>

    @include('admin.settings.partials.nav', ['active' => 'integrations'])

    {{-- Reusable show/hide script --}}
    <script>
    function secretField(id) {
        return {
            show: false,
            toggle() {
                this.show = !this.show;
                const el = document.getElementById(id);
                el.type = this.show ? 'text' : 'password';
            }
        }
    }
    </script>

    <form action="{{ route('admin.settings.integrations.update') }}" method="POST" autocomplete="off">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">

            {{-- Razorpay Webhook --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Razorpay Webhook</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Used to verify incoming payment webhook signatures</p>
                </div>
                <div style="padding: 1rem;">
                    <div x-data="secretField('razorpay_webhook_secret')">
                        <label class="form-label">Webhook Secret</label>
                        <div style="position: relative;">
                            <input type="password" id="razorpay_webhook_secret" name="razorpay_webhook_secret"
                                   value="{{ old('razorpay_webhook_secret', $settings['razorpay_webhook_secret'] ?? '') }}"
                                   autocomplete="new-password"
                                   class="form-input" style="padding-right: 4rem;">
                            <button type="button" @click="toggle()"
                                    style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 12px; color: #616161; background: none; border: none; cursor: pointer;"
                                    x-text="show ? 'Hide' : 'Show'"></button>
                        </div>
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                            Found in Razorpay Dashboard &rarr; Webhooks &rarr; Secret
                        </p>
                    </div>
                </div>
            </div>

            {{-- WhatsApp Business API --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">WhatsApp Business (Meta)</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">For order notifications & customer chat via WhatsApp Cloud API</p>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label">Phone Number ID</label>
                        <input type="text" name="whatsapp_phone_number_id"
                               value="{{ old('whatsapp_phone_number_id', $settings['whatsapp_phone_number_id'] ?? '') }}"
                               placeholder="1234567890123456" inputmode="numeric" pattern="[0-9]*"
                               class="form-input">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Meta Developer Console &rarr; WhatsApp &rarr; Phone Number ID</p>
                    </div>

                    <div>
                        <label class="form-label">Webhook Verify Token</label>
                        <input type="text" name="whatsapp_verify_token"
                               value="{{ old('whatsapp_verify_token', $settings['whatsapp_verify_token'] ?? '') }}"
                               placeholder="Any random string you set" autocomplete="off"
                               class="form-input">
                    </div>

                    <div x-data="secretField('whatsapp_page_access_token')">
                        <label class="form-label">Page Access Token</label>
                        <div style="position: relative;">
                            <input type="password" id="whatsapp_page_access_token" name="whatsapp_page_access_token"
                                   value="{{ old('whatsapp_page_access_token', $settings['whatsapp_page_access_token'] ?? '') }}"
                                   autocomplete="new-password" class="form-input" style="padding-right: 4rem;">
                            <button type="button" @click="toggle()"
                                    style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 12px; color: #616161; background: none; border: none; cursor: pointer;"
                                    x-text="show ? 'Hide' : 'Show'"></button>
                        </div>
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Meta Developer Console &rarr; WhatsApp &rarr; Temporary / Permanent Access Token</p>
                    </div>

                    <div x-data="secretField('whatsapp_app_secret')">
                        <label class="form-label">App Secret</label>
                        <div style="position: relative;">
                            <input type="password" id="whatsapp_app_secret" name="whatsapp_app_secret"
                                   value="{{ old('whatsapp_app_secret', $settings['whatsapp_app_secret'] ?? '') }}"
                                   autocomplete="new-password" class="form-input" style="padding-right: 4rem;">
                            <button type="button" @click="toggle()"
                                    style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 12px; color: #616161; background: none; border: none; cursor: pointer;"
                                    x-text="show ? 'Hide' : 'Show'"></button>
                        </div>
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Meta Developer Console &rarr; App Settings &rarr; Basic &rarr; App Secret</p>
                    </div>
                </div>
            </div>

            {{-- SMS Gateway --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">SMS Gateway</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">For OTP, order updates, and promotional SMS</p>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label">Provider</label>
                        <select name="sms_provider" class="form-select">
                            @foreach(['none' => 'Disabled', 'msg91' => 'MSG91', 'twilio' => 'Twilio'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('sms_provider', $settings['sms_provider'] ?? 'none') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-data="secretField('sms_api_key')">
                        <label class="form-label">API Key / Auth Token</label>
                        <div style="position: relative;">
                            <input type="password" id="sms_api_key" name="sms_api_key"
                                   value="{{ old('sms_api_key', $settings['sms_api_key'] ?? '') }}"
                                   autocomplete="new-password" class="form-input" style="padding-right: 4rem;">
                            <button type="button" @click="toggle()"
                                    style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 12px; color: #616161; background: none; border: none; cursor: pointer;"
                                    x-text="show ? 'Hide' : 'Show'"></button>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Sender ID</label>
                        <input type="text" name="sms_sender_id"
                               value="{{ old('sms_sender_id', $settings['sms_sender_id'] ?? '') }}"
                               placeholder="FVRKDS" maxlength="20" class="form-input">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">6-character alphanumeric sender ID (DLT registered)</p>
                    </div>

                    <div>
                        <label class="form-label">DLT Template ID</label>
                        <input type="text" name="sms_dlt_template_id"
                               value="{{ old('sms_dlt_template_id', $settings['sms_dlt_template_id'] ?? '') }}"
                               placeholder="1234567890123456789" inputmode="numeric" pattern="[0-9]*"
                               class="form-input">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">TRAI DLT-registered template ID (required in India)</p>
                    </div>
                </div>
            </div>

            {{-- AI Chatbot (Anthropic) --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">AI Chatbot (Anthropic)</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Powers the customer support chatbot on the website</p>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div x-data="secretField('anthropic_api_key')">
                        <label class="form-label">Anthropic API Key</label>
                        <div style="position: relative;">
                            <input type="password" id="anthropic_api_key" name="anthropic_api_key"
                                   value="{{ old('anthropic_api_key', $settings['anthropic_api_key'] ?? '') }}"
                                   autocomplete="new-password"
                                   placeholder="sk-ant-..."
                                   class="form-input" style="padding-right: 4rem; font-family: monospace; font-size: 13px;">
                            <button type="button" @click="toggle()"
                                    style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 12px; color: #616161; background: none; border: none; cursor: pointer;"
                                    x-text="show ? 'Hide' : 'Show'"></button>
                        </div>
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                            <a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener noreferrer" style="color: #005bd3; text-decoration: underline;">console.anthropic.com/settings/keys</a>
                        </p>
                    </div>

                    <div>
                        <label class="form-label">Model</label>
                        <select name="anthropic_model" class="form-select">
                            @foreach([
                                'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (fastest, lowest cost)',
                                'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 (balanced)',
                                'claude-opus-4-6'           => 'Claude Opus 4.6 (most capable)',
                            ] as $val => $label)
                                <option value="{{ $val }}" @selected(old('anthropic_model', $settings['anthropic_model'] ?? 'claude-haiku-4-5-20251001') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

        </div>

        {{-- Security notice --}}
        <div style="margin-top: 1.5rem; padding: 1rem; background: #fff3cd; border: 1px solid #e8d5a0; border-radius: 0.75rem; display: flex; align-items: flex-start; gap: 0.75rem;">
            <svg style="width: 1.25rem; height: 1.25rem; color: #b98900; flex-shrink: 0; margin-top: 0.125rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <p style="font-size: 13px; font-weight: 500; color: #8a6d00; margin: 0;">Security notice</p>
                <p style="font-size: 12px; color: #8a6d00; margin: 0.25rem 0 0 0;">All API keys and secrets are stored encrypted in the database. Never share these credentials. Rotate keys immediately if you suspect a breach.</p>
            </div>
        </div>

        <div style="margin-top: 1rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Integration Settings</button>
        </div>
    </form>
</x-layouts.admin>
