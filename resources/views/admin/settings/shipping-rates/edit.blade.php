<x-layouts.admin>
    <x-slot name="title">Edit Shipping Rate</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.settings.shipping-zones.edit', $rate->zone) }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ $rate->zone->name }}
        </a>
    </div>

    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0.5rem 0 1.25rem;">Edit Shipping Rate</h1>

    <div style="max-width: 640px;">

        @include('admin.settings.partials.errors', ['handled' => ['name', 'rate', 'type']])
        <form action="{{ route('admin.settings.rates.update', $rate) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card" style="background: #fff; border: 1px solid #e3e3e3; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Rate Name <span style="color: #d72c0d;">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $rate->name) }}" required
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    <x-field-error field="name" />
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Rate Type <span style="color: #d72c0d;">*</span></label>
                    <select name="type" required
                            style="width: 100%; padding: 0.5rem 2rem 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; background-color: #fff;">
                        <option value="flat" {{ old('type', $rate->type) === 'flat' ? 'selected' : '' }}>Flat Rate</option>
                        <option value="weight" {{ old('type', $rate->type) === 'weight' ? 'selected' : '' }}>Weight Based</option>
                        <option value="price" {{ old('type', $rate->type) === 'price' ? 'selected' : '' }}>Price Based</option>
                        <option value="free" {{ old('type', $rate->type) === 'free' ? 'selected' : '' }}>Free Shipping</option>
                    </select>
                    <x-field-error field="type" />
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Rate Amount (₹) <span style="color: #d72c0d;">*</span></label>
                    <input type="number" name="rate" value="{{ old('rate', $rate->rate) }}" required min="0" step="0.01"
                           style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    <x-field-error field="rate" />
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Min Order Amount (₹)</label>
                        <input type="number" name="min_order" value="{{ old('min_order', $rate->min_order) }}" min="0" step="0.01" placeholder="Optional"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Min Weight (kg)</label>
                        <input type="number" name="min_weight" value="{{ old('min_weight', $rate->min_weight) }}" min="0" step="0.01" placeholder="Optional"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Max Weight (kg)</label>
                        <input type="number" name="max_weight" value="{{ old('max_weight', $rate->max_weight) }}" min="0" step="0.01" placeholder="Optional"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Est. Delivery Min Days</label>
                        <input type="number" name="estimated_days_min" value="{{ old('estimated_days_min', $rate->estimated_days_min) }}" min="1" placeholder="e.g. 3"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Est. Delivery Max Days</label>
                        <input type="number" name="estimated_days_max" value="{{ old('estimated_days_max', $rate->estimated_days_max) }}" min="1" placeholder="e.g. 7"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px;">
                    </div>
                </div>

                <div>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" style="width: 1rem; height: 1rem; accent-color: #303030;" {{ old('is_active', $rate->is_active) ? 'checked' : '' }}>
                        <span style="font-size: 13px; color: #303030;">Active</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem;">
                <button type="submit"
                        style="padding: 0.5rem 1.25rem; background: #303030; color: #fff; border: none; border-radius: 0.5rem; font-size: 13px; font-weight: 500; cursor: pointer;">
                    Save Changes
                </button>
                <a href="{{ route('admin.settings.shipping-zones.edit', $rate->zone) }}"
                   style="padding: 0.5rem 1.25rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; color: #303030; text-decoration: none;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</x-layouts.admin>
