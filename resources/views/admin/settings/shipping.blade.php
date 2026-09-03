<x-layouts.admin>
    <x-slot name="title">Shipping Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Settings</h1>
        </div>
    </x-slot>

    @include('admin.settings.partials.nav', ['active' => 'shipping'])

    @include('admin.settings.partials.errors')

    <form action="{{ route('admin.settings.shipping.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <!-- Left Column -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <!-- Shiprocket Integration -->
                <div class="card" x-data="{ enabled: {{ ($settings['shiprocket_enabled'] ?? false) ? 'true' : 'false' }} }">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg style="width: 1.25rem; height: 1.25rem; color: #7C3AED;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125v-3.659a3 3 0 00-.879-2.121l-2.121-2.122a3 3 0 00-2.121-.879H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.988-1.111a48.662 48.662 0 00-3.478-.404c-.668-.049-1.284.366-1.496.994l-.297.882"/></svg>
                            <div>
                                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Shiprocket</h2>
                                <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Auto-ship orders via Shiprocket</p>
                            </div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="shiprocket_enabled" value="1" x-model="enabled">
                            <div class="toggle-track"></div>
                        </label>
                    </div>
                    <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;" x-show="enabled" x-collapse
                         x-data="{ authMode: '{{ old('shiprocket_auth_mode', $settings['shiprocket_auth_mode'] ?? (!empty($settings['shiprocket_api_token'] ?? '') ? 'token' : 'credentials')) }}' }">
                        <div>
                            <label class="form-label">Authentication Method</label>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; margin-top: 0.25rem;">
                                <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 13px; cursor: pointer;">
                                    <input type="radio" name="shiprocket_auth_mode" value="token" x-model="authMode"> API Token
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 13px; cursor: pointer;">
                                    <input type="radio" name="shiprocket_auth_mode" value="credentials" x-model="authMode"> Email &amp; Password
                                </label>
                            </div>
                        </div>

                        <div x-show="authMode === 'token'" x-collapse>
                            <label class="form-label">API Token <span style="color: #d72c0d;">*</span></label>
                            <input type="password" name="shiprocket_api_token" value="{{ old('shiprocket_api_token', $settings['shiprocket_api_token'] ?? '') }}" class="form-input" placeholder="Paste your Shiprocket API token">
                            <p style="font-size: 11px; color: #616161; margin-top: 2px;">Generate from Shiprocket → Settings → API → Create an API User</p>
                        </div>

                        <div x-show="authMode === 'credentials'" x-collapse style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div>
                                <label class="form-label">Email <span style="color: #d72c0d;">*</span></label>
                                <input type="email" name="shiprocket_email" value="{{ old('shiprocket_email', $settings['shiprocket_email'] ?? '') }}" class="form-input" placeholder="your@email.com">
                                <p style="font-size: 11px; color: #616161; margin-top: 2px;">Your Shiprocket account login email</p>
                            </div>
                            <div>
                                <label class="form-label">Password <span style="color: #d72c0d;">*</span></label>
                                <input type="password" name="shiprocket_password" value="{{ old('shiprocket_password', $settings['shiprocket_password'] ?? '') }}" class="form-input" placeholder="Enter password">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Pickup Location</label>
                            <input type="text" name="shiprocket_pickup_location" value="{{ old('shiprocket_pickup_location', $settings['shiprocket_pickup_location'] ?? 'Primary') }}" class="form-input" placeholder="Primary">
                            <p style="font-size: 11px; color: #616161; margin-top: 2px;">Must match a pickup location name in your Shiprocket dashboard</p>
                        </div>
                        <div>
                            <label class="form-label">Channel ID</label>
                            <input type="text" name="shiprocket_channel_id" value="{{ old('shiprocket_channel_id', $settings['shiprocket_channel_id'] ?? '') }}" class="form-input" placeholder="e.g. 12345">
                            <p style="font-size: 11px; color: #616161; margin-top: 2px;">Found in Shiprocket Settings → Channels. Leave blank to use default.</p>
                        </div>
                        <div style="padding: 0.5rem 0.75rem; background: #f0f4ff; border-radius: 0.5rem; border: 1px solid #d4e0ff;">
                            <p style="font-size: 12px; color: #303030; margin: 0;">
                                <strong>How it works:</strong> When you move an order to "Processing", it's automatically pushed to Shiprocket with AWB assignment and pickup request. Tracking updates sync automatically via webhook.
                            </p>
                            <p style="font-size: 11px; color: #616161; margin: 0.25rem 0 0 0;">
                                Webhook URL: <code style="font-size: 11px; background: #e3e3e3; padding: 1px 4px; border-radius: 3px;">{{ url('/webhooks/shiprocket') }}</code>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Free Shipping -->
                <div class="card" x-data="{ enabled: {{ ($settings['free_shipping_enabled'] ?? false) ? 'true' : 'false' }} }">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Free Shipping</h2>
                            <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Offer free shipping above a threshold</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="free_shipping_enabled" value="1" x-model="enabled">
                            <div class="toggle-track"></div>
                        </label>
                    </div>
                    <div style="padding: 1rem;" x-show="enabled" x-collapse>
                        <label class="form-label">Minimum Order Amount</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #616161; font-size: 13px;">&#8377;</span>
                            <input type="number" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $settings['free_shipping_threshold'] ?? '') }}" step="0.01" min="0" class="form-input" style="padding-left: 1.75rem;">
                        </div>
                    </div>
                </div>

                <!-- Returns -->
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Returns</h2>
                        <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">When a customer may request a return</p>
                    </div>
                    <div style="padding: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label">Return window (days)</label>
                            <input type="number" name="return_window_days" min="0" max="365"
                                   value="{{ old('return_window_days', $settings['return_window_days'] ?? 7) }}" class="form-input">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Days after delivery a return can still be raised.</p>
                        </div>
                        <div>
                            <label class="form-label">Wait before returning (hours)</label>
                            <input type="number" name="return_min_hours" min="0" max="168"
                                   value="{{ old('return_min_hours', $settings['return_min_hours'] ?? 0) }}" class="form-input">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Set 0 to let customers return as soon as the order is delivered.</p>
                        </div>
                    </div>
                </div>

                <!-- Flat Rate -->
                <div class="card" x-data="{ enabled: {{ ($settings['flat_rate_enabled'] ?? true) ? 'true' : 'false' }} }">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Flat Rate Shipping</h2>
                            <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Charge a fixed shipping fee</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="flat_rate_enabled" value="1" x-model="enabled">
                            <div class="toggle-track"></div>
                        </label>
                    </div>
                    <div style="padding: 1rem;" x-show="enabled" x-collapse>
                        <label class="form-label">Shipping Fee</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #616161; font-size: 13px;">&#8377;</span>
                            <input type="number" name="flat_rate_amount" value="{{ old('flat_rate_amount', $settings['flat_rate_amount'] ?? '99') }}" step="0.01" min="0" class="form-input" style="padding-left: 1.75rem;">
                        </div>
                    </div>
                </div>

                <!-- Local Pickup -->
                <div class="card" x-data="{ enabled: {{ ($settings['local_pickup_enabled'] ?? false) ? 'true' : 'false' }} }">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Local Pickup</h2>
                            <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Allow in-store pickup</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="local_pickup_enabled" value="1" x-model="enabled">
                            <div class="toggle-track"></div>
                        </label>
                    </div>
                    <div style="padding: 1rem;" x-show="enabled" x-collapse>
                        <label class="form-label">Pickup Address</label>
                        <textarea name="local_pickup_address" rows="3" class="form-textarea" placeholder="Enter your store address...">{{ old('local_pickup_address', $settings['local_pickup_address'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Shipping Origin -->
            <div class="card" style="align-self: start;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Shipping Origin</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Where packages are shipped from</p>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label">Country <span style="color: #d72c0d;">*</span></label>
                        <select name="shipping_origin_country" required class="form-select">
                            <option value="IN" @selected(($settings['shipping_origin_country'] ?? 'IN') === 'IN')>India</option>
                            <option value="US" @selected(($settings['shipping_origin_country'] ?? '') === 'US')>United States</option>
                            <option value="CA" @selected(($settings['shipping_origin_country'] ?? '') === 'CA')>Canada</option>
                            <option value="GB" @selected(($settings['shipping_origin_country'] ?? '') === 'GB')>United Kingdom</option>
                            <option value="AU" @selected(($settings['shipping_origin_country'] ?? '') === 'AU')>Australia</option>
                            <option value="DE" @selected(($settings['shipping_origin_country'] ?? '') === 'DE')>Germany</option>
                            <option value="FR" @selected(($settings['shipping_origin_country'] ?? '') === 'FR')>France</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">State/Province</label>
                        <input type="text" name="shipping_origin_state" value="{{ old('shipping_origin_state', $settings['shipping_origin_state'] ?? '') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">ZIP/Postal Code</label>
                        <input type="text" name="shipping_origin_zip" value="{{ old('shipping_origin_zip', $settings['shipping_origin_zip'] ?? '') }}" class="form-input">
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Settings</button>
        </div>
    </form>

    <!-- Shipping Zones was previously unreachable from the admin UI. -->
    <div style="margin-top: 1.5rem;">
        <div class="card" style="padding: 0.875rem 1rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                <div>
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Shipping Zones</h3>
                    <p style="font-size: 12px; color: #616161; margin: 0;">Set per-region shipping rates</p>
                </div>
                <a href="{{ route('admin.settings.shipping-zones.index') }}" class="btn btn-secondary" style="font-size: 13px; white-space: nowrap;">Manage Shipping Zones</a>
            </div>
        </div>
    </div>
</x-layouts.admin>
