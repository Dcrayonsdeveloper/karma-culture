<x-layouts.admin>
    <x-slot name="title">Tax Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Settings</h1>
        </div>
    </x-slot>

    @include('admin.settings.partials.nav', ['active' => 'tax'])

    <form action="{{ route('admin.settings.tax.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <!-- Tax Options -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Tax Options</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">Enable Taxes</p>
                            <p style="font-size: 12px; color: #616161; margin: 0;">Calculate and apply taxes to orders</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="tax_enabled" value="1" @checked($settings['tax_enabled'] ?? true)>
                            <div class="toggle-track"></div>
                        </label>
                    </div>

                    <div>
                        <label class="form-label">Prices Entered With Tax</label>
                        <select name="tax_calculation" class="form-select">
                            <option value="exclusive" @selected(($settings['tax_calculation'] ?? 'exclusive') === 'exclusive')>No, I will enter prices exclusive of tax</option>
                            <option value="inclusive" @selected(($settings['tax_calculation'] ?? '') === 'inclusive')>Yes, I will enter prices inclusive of tax</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Calculate Tax Based On</label>
                        <select name="tax_based_on" class="form-select">
                            <option value="shipping" @selected(($settings['tax_based_on'] ?? 'shipping') === 'shipping')>Customer shipping address</option>
                            <option value="billing" @selected(($settings['tax_based_on'] ?? '') === 'billing')>Customer billing address</option>
                            <option value="store" @selected(($settings['tax_based_on'] ?? '') === 'store')>Shop base address</option>
                        </select>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">Round Tax at Subtotal</p>
                            <p style="font-size: 12px; color: #616161; margin: 0;">Round tax at subtotal level instead of per line</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="tax_round_at_subtotal" value="1" @checked($settings['tax_round_at_subtotal'] ?? false)>
                            <div class="toggle-track"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Display Options -->
            <div class="card" style="align-self: start;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Display Options</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label">Display Prices in Cart</label>
                        <select name="tax_display_cart" class="form-select">
                            <option value="excluding" @selected(($settings['tax_display_cart'] ?? 'excluding') === 'excluding')>Excluding tax</option>
                            <option value="including" @selected(($settings['tax_display_cart'] ?? '') === 'including')>Including tax</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Display Prices During Checkout</label>
                        <select name="tax_display_checkout" class="form-select">
                            <option value="excluding" @selected(($settings['tax_display_checkout'] ?? 'excluding') === 'excluding')>Excluding tax</option>
                            <option value="including" @selected(($settings['tax_display_checkout'] ?? '') === 'including')>Including tax</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Settings</button>
        </div>
    </form>

    <!-- Tax Rates Link -->
    <div class="card" style="margin-top: 1.5rem; padding: 0.875rem 1rem;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Tax Rates</h3>
                <p style="font-size: 12px; color: #616161; margin: 0;">Configure tax rates by region</p>
            </div>
            <a href="{{ route('admin.settings.tax-rates.index') }}" class="btn btn-secondary" style="font-size: 13px;">
                Manage Tax Rates
            </a>
        </div>
    </div>
</x-layouts.admin>
