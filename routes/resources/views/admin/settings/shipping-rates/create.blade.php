<x-layouts.admin>
    <x-slot name="title">Add Shipping Rate</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.settings.shipping-zones.edit', $shippingZone) }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ $shippingZone->name }}
        </a>
    </div>

    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0.5rem 0 1.25rem;">Add Shipping Rate</h1>

    <div style="max-width: 640px;">
        <form action="{{ route('admin.settings.shipping-zones.rates.store', $shippingZone) }}" method="POST">
            @csrf

            <div style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Rate Name <span style="color: #d72c0d;">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="e.g. Standard Delivery"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    @error('name') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Rate Type <span style="color: #d72c0d;">*</span></label>
                    <select name="type" required
                            style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; background: #fff;">
                        <option value="flat" {{ old('type') === 'flat' ? 'selected' : '' }}>Flat Rate</option>
                        <option value="weight" {{ old('type') === 'weight' ? 'selected' : '' }}>Weight Based</option>
                        <option value="price" {{ old('type') === 'price' ? 'selected' : '' }}>Price Based</option>
                        <option value="free" {{ old('type') === 'free' ? 'selected' : '' }}>Free Shipping</option>
                    </select>
                    @error('type') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Rate Amount (₹) <span style="color: #d72c0d;">*</span></label>
                    <input type="number" name="rate" value="{{ old('rate', 0) }}" required min="0" step="0.01"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    @error('rate') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Min Order Amount (₹)</label>
                        <input type="number" name="min_order" value="{{ old('min_order') }}" min="0" step="0.01" placeholder="Optional"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Min Weight (kg)</label>
                        <input type="number" name="min_weight" value="{{ old('min_weight') }}" min="0" step="0.01" placeholder="Optional"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Est. Delivery Min Days</label>
                        <input type="number" name="estimated_days_min" value="{{ old('estimated_days_min') }}" min="1" placeholder="e.g. 3"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Est. Delivery Max Days</label>
                        <input type="number" name="estimated_days_max" value="{{ old('estimated_days_max') }}" min="1" placeholder="e.g. 7"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    </div>
                </div>

                <div>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" style="width: 1rem; height: 1rem; accent-color: #303030;" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span style="font-size: 13px; color: #303030;">Active</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                <button type="submit"
                        style="padding: 0.5rem 1.25rem; background: #303030; color: #fff; border: none; border-radius: 0.5rem; font-size: 13px; font-weight: 500; cursor: pointer;">
                    Add Rate
                </button>
                <a href="{{ route('admin.settings.shipping-zones.edit', $shippingZone) }}"
                   style="padding: 0.5rem 1.25rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; color: #303030; text-decoration: none;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-layouts.admin>
