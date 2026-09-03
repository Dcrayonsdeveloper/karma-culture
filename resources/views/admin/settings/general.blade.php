<x-layouts.admin>
    <x-slot name="title">General Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Settings</h1>
        </div>
    </x-slot>

    <!-- Settings Navigation -->
    @include('admin.settings.partials.nav', ['active' => 'general'])

    @include('admin.settings.partials.errors')

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

            {{-- The Regional Settings card was removed on request. Timezone,
                 date format, currency, symbol and position are still stored and
                 still in force - the storefront reads them through
                 currency_config() and format_date(), and the timezone is applied
                 at boot. They simply have no editor on this screen any more, so
                 the rules for them in updateGeneral() are nullable: a save that
                 does not carry them leaves the stored values alone. --}}
            </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Settings</button>
        </div>
    </form>

    <!-- Related settings. These pages were previously unreachable: nothing in
         the admin, including the sidebar, linked to them. -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
        <div class="card" style="padding: 0.875rem 1rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                <div>
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Currencies</h3>
                    <p style="font-size: 12px; color: #616161; margin: 0;">Add currencies and set exchange rates</p>
                </div>
                <a href="{{ route('admin.settings.currencies.index') }}" class="btn btn-secondary" style="font-size: 13px; white-space: nowrap;">Manage Currencies</a>
            </div>
        </div>
        <div class="card" style="padding: 0.875rem 1rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                <div>
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Roles &amp; Permissions</h3>
                    <p style="font-size: 12px; color: #616161; margin: 0;">Control what each staff role can access</p>
                </div>
                <a href="{{ route('admin.settings.roles.index') }}" class="btn btn-secondary" style="font-size: 13px; white-space: nowrap;">Manage Roles</a>
            </div>
        </div>
    </div>
</x-layouts.admin>
