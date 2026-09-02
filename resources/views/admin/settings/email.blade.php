<x-layouts.admin>
    <x-slot name="title">Email Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Settings</h1>
        </div>
    </x-slot>

    @include('admin.settings.partials.nav', ['active' => 'email'])

    @include('admin.settings.partials.errors')

    <form action="{{ route('admin.settings.email.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <!-- SMTP Configuration -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Mail Configuration</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label">Mail Driver <span style="color: #d72c0d;">*</span></label>
                        <select name="mail_driver" required class="form-select">
                            {{-- Only transports this install actually has. Mailgun, SES and
                                 Postmark need SDKs that are not in composer.json; offering them
                                 let an admin pick a driver that silently stops all mail. --}}
                            <option value="smtp" @selected(old('mail_driver', $settings['mail_driver'] ?? 'smtp') === 'smtp')>SMTP</option>
                            <option value="sendmail" @selected(old('mail_driver', $settings['mail_driver'] ?? '') === 'sendmail')>Sendmail</option>
                            <option value="log" @selected(old('mail_driver', $settings['mail_driver'] ?? '') === 'log')>Log file (no mail sent)</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="mail_host" value="{{ old('mail_host', $settings['mail_host'] ?? '') }}" placeholder="smtp.example.com" class="form-input">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label">Port</label>
                            <input type="number" name="mail_port" value="{{ old('mail_port', $settings['mail_port'] ?? '587') }}" min="1" max="65535" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Encryption</label>
                            <select name="mail_encryption" class="form-select">
                                <option value="">None</option>
                                <option value="tls" @selected(old('mail_encryption', $settings['mail_encryption'] ?? 'tls') === 'tls')>TLS</option>
                                <option value="ssl" @selected(old('mail_encryption', $settings['mail_encryption'] ?? '') === 'ssl')>SSL</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Username</label>
                        <input type="text" name="mail_username" value="{{ old('mail_username', $settings['mail_username'] ?? '') }}" autocomplete="off" class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Password</label>
                        {{-- new-password stops the browser autofilling the admin's own login here --}}
                        <input type="password" name="mail_password" value="" autocomplete="new-password"
                               placeholder="{{ ($settings['mail_password'] ?? '') !== '' ? 'Saved - leave blank to keep' : '' }}" class="form-input">
                    </div>
                </div>
            </div>

            <!-- From Address -->
            <div class="card" style="align-self: start;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Sender Details</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label">From Email <span style="color: #d72c0d;">*</span></label>
                        <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address'] ?? '') }}" required placeholder="noreply@example.com" class="form-input">
                    </div>

                    <div>
                        <label class="form-label">From Name <span style="color: #d72c0d;">*</span></label>
                        <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name'] ?? '') }}" required placeholder="Your Store Name" class="form-input">
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            {{-- Lives in a sibling form declared below; nesting it inside the
                 settings form would be invalid HTML and would submit the wrong
                 action. --}}
            <button type="submit" form="send-test-email" class="btn btn-secondary" style="font-size: 13px;">
                <svg style="width: 1rem; height: 1rem; margin-right: 0.375rem; display: inline-block; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Send Test Email
            </button>
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Settings</button>
        </div>
    </form>

    <form id="send-test-email" method="POST" action="{{ route('admin.settings.email.test') }}">
        @csrf
    </form>

    {{-- auth('admin'), not auth(): the admin area runs on its own guard and the
         default one is null here. --}}
    <p style="margin-top: 0.5rem; font-size: 12px; color: #616161; text-align: right;">
        The test uses the settings currently saved, so save your changes first.
        @if($testRecipient = auth('admin')->user()?->email)
            It is sent to {{ $testRecipient }}.
        @endif
    </p>
</x-layouts.admin>
