<x-layouts.admin>
    <x-slot name="title">Add Delivery Partner</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.delivery-partners.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add delivery partner</h1>
    </div>

    <form action="{{ route('admin.delivery-partners.store') }}" method="POST">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                {{-- Personal Details --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Personal Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="first_name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">First Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required class="form-input" style="width: 100%;">
                                @error('first_name') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="last_name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Last Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required class="form-input" style="width: 100%;">
                                @error('last_name') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="email" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Email <span style="color: #d72c0d;">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required class="form-input" style="width: 100%;">
                                @error('email') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Phone <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required class="form-input" style="width: 100%;">
                                @error('phone') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="password" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Password <span style="color: #d72c0d;">*</span></label>
                                <input type="password" name="password" id="password" required class="form-input" style="width: 100%;">
                                @error('password') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Confirm Password <span style="color: #d72c0d;">*</span></label>
                                <input type="password" name="password_confirmation" id="password_confirmation" required class="form-input" style="width: 100%;">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Vehicle Details --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Vehicle Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="vehicle_type" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Vehicle Type <span style="color: #d72c0d;">*</span></label>
                                <select name="vehicle_type" id="vehicle_type" required class="form-select" style="width: 100%;">
                                    <option value="bike" @selected(old('vehicle_type') === 'bike')>Bike</option>
                                    <option value="scooter" @selected(old('vehicle_type') === 'scooter')>Scooter</option>
                                    <option value="van" @selected(old('vehicle_type') === 'van')>Van</option>
                                    <option value="truck" @selected(old('vehicle_type') === 'truck')>Truck</option>
                                    <option value="other" @selected(old('vehicle_type') === 'other')>Other</option>
                                </select>
                                @error('vehicle_type') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="vehicle_number" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Vehicle Number</label>
                                <input type="text" name="vehicle_number" id="vehicle_number" value="{{ old('vehicle_number') }}" class="form-input" style="width: 100%;" placeholder="e.g. MH 02 AB 1234">
                                @error('vehicle_number') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="license_number" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">License Number</label>
                                <input type="text" name="license_number" id="license_number" value="{{ old('license_number') }}" class="form-input" style="width: 100%;">
                                @error('license_number') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="company_name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Company Name</label>
                                <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" class="form-input" style="width: 100%;" placeholder="Optional">
                                @error('company_name') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                {{-- Status --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Status</h2>
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) style="width: 1rem; height: 1rem; accent-color: #303030;">
                        <div>
                            <span style="font-size: 13px; font-weight: 500; color: #303030;">Active</span>
                            <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Partner can receive and deliver orders</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.delivery-partners.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save delivery partner</button>
            </div>
    </form>
</x-layouts.admin>
