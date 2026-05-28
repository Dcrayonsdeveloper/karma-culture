<x-layouts.admin>
    <x-slot name="title">Edit Customer</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.customers.show', $customer) }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ $customer->full_name }}
        </a>
    </div>

    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Edit Customer</h1>

    <div style="max-width: 640px;">
        <form action="{{ route('admin.customers.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card" style="margin-bottom: 1rem; padding: 1.25rem;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Customer Information</h2>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label for="first_name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">First Name *</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $customer->first_name) }}" required
                                   class="form-input" style="width: 100%;">
                            @error('first_name')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="last_name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Last Name *</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $customer->last_name) }}" required
                                   class="form-input" style="width: 100%;">
                            @error('last_name')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Email *</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $customer->email) }}" required
                               class="form-input" style="width: 100%;">
                        @error('email')
                            <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Phone</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}"
                               class="form-input" style="width: 100%;">
                        @error('phone')
                            <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 1rem; padding: 1.25rem;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Account Status</h2>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}
                               style="width: 16px; height: 16px; accent-color: #005bd3;">
                        <span style="font-size: 13px; color: #303030;">Account is active</span>
                    </label>
                    <p style="font-size: 12px; color: #616161;">Inactive accounts cannot login or place orders.</p>
                </div>
            </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <span></span>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>
