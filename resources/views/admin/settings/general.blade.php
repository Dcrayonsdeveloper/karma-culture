<x-layouts.admin>
    <x-slot name="title">General Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Settings</h1>
        </div>
    </x-slot>

    <!-- Settings Navigation -->
    @include('admin.settings.partials.nav', ['active' => 'general'])

    <form action="{{ route('admin.settings.general.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <!-- Store Information -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Store Information</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Site Name</label>
                        <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name'] ?? '') }}" required class="form-input">
                        @error('site_name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Tagline</label>
                        <input type="text" name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline'] ?? '') }}" class="form-input">
                        @error('site_tagline') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Email Address</label>
                        <input type="email" name="site_email" value="{{ old('site_email', $settings['site_email'] ?? '') }}" required class="form-input">
                        @error('site_email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Phone Number</label>
                        <input type="tel" name="site_phone" value="{{ old('site_phone', $settings['site_phone'] ?? '') }}" class="form-input">
                        @error('site_phone') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Address</label>
                        <textarea name="site_address" rows="3" class="form-textarea">{{ old('site_address', $settings['site_address'] ?? '') }}</textarea>
                        @error('site_address') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Regional Settings -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Regional Settings</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Timezone</label>
                        <select name="timezone" required class="form-select">
                            @foreach(timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" @selected(($settings['timezone'] ?? 'UTC') === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Date Format</label>
                        <select name="date_format" required class="form-select">
                            <option value="M d, Y" @selected(($settings['date_format'] ?? 'M d, Y') === 'M d, Y')>{{ now()->format('M d, Y') }}</option>
                            <option value="d/m/Y" @selected(($settings['date_format'] ?? '') === 'd/m/Y')>{{ now()->format('d/m/Y') }}</option>
                            <option value="m/d/Y" @selected(($settings['date_format'] ?? '') === 'm/d/Y')>{{ now()->format('m/d/Y') }}</option>
                            <option value="Y-m-d" @selected(($settings['date_format'] ?? '') === 'Y-m-d')>{{ now()->format('Y-m-d') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Currency</label>
                        <select name="currency" required class="form-select">
                            <option value="USD" @selected(($settings['currency'] ?? 'USD') === 'USD')>USD - US Dollar</option>
                            <option value="EUR" @selected(($settings['currency'] ?? '') === 'EUR')>EUR - Euro</option>
                            <option value="GBP" @selected(($settings['currency'] ?? '') === 'GBP')>GBP - British Pound</option>
                            <option value="INR" @selected(($settings['currency'] ?? '') === 'INR')>INR - Indian Rupee</option>
                            <option value="CAD" @selected(($settings['currency'] ?? '') === 'CAD')>CAD - Canadian Dollar</option>
                            <option value="AUD" @selected(($settings['currency'] ?? '') === 'AUD')>AUD - Australian Dollar</option>
                            <option value="JPY" @selected(($settings['currency'] ?? '') === 'JPY')>JPY - Japanese Yen</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Currency Symbol</label>
                        <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '$') }}" required maxlength="5" class="form-input" placeholder="e.g. $, €, £, ₹, ¥">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">The symbol displayed with prices (e.g. $, €, £, ₹, ¥)</p>
                    </div>
                    <div>
                        <label class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Currency Position</label>
                        <select name="currency_position" required class="form-select">
                            <option value="before" @selected(($settings['currency_position'] ?? 'before') === 'before')>Before amount ($99.99)</option>
                            <option value="after" @selected(($settings['currency_position'] ?? '') === 'after')>After amount (99.99$)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Settings</button>
        </div>
    </form>
</x-layouts.admin>
