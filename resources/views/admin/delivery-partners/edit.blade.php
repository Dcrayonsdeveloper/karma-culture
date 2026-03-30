<x-layouts.admin>
    <x-slot name="title">Edit Delivery Partner</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.delivery-partners.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $deliveryPartner->user->first_name }} {{ $deliveryPartner->user->last_name }}</h1>
        @if($deliveryPartner->is_active)
            <span class="badge badge-success">Active</span>
        @else
            <span class="badge badge-warning">Inactive</span>
        @endif
    </div>

    <form action="{{ route('admin.delivery-partners.update', $deliveryPartner) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                {{-- Personal Details --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Personal Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="first_name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">First Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $deliveryPartner->user->first_name) }}" required class="form-input" style="width: 100%;">
                                @error('first_name') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="last_name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Last Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $deliveryPartner->user->last_name) }}" required class="form-input" style="width: 100%;">
                                @error('last_name') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="email" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Email <span style="color: #d72c0d;">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email', $deliveryPartner->user->email) }}" required class="form-input" style="width: 100%;">
                                @error('email') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Phone <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $deliveryPartner->phone) }}" required class="form-input" style="width: 100%;">
                                @error('phone') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="password" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">New Password</label>
                                <input type="password" name="password" id="password" class="form-input" style="width: 100%;" placeholder="Leave blank to keep current">
                                @error('password') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Confirm Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-input" style="width: 100%;">
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
                                    <option value="bike" @selected(old('vehicle_type', $deliveryPartner->vehicle_type) === 'bike')>Bike</option>
                                    <option value="scooter" @selected(old('vehicle_type', $deliveryPartner->vehicle_type) === 'scooter')>Scooter</option>
                                    <option value="van" @selected(old('vehicle_type', $deliveryPartner->vehicle_type) === 'van')>Van</option>
                                    <option value="truck" @selected(old('vehicle_type', $deliveryPartner->vehicle_type) === 'truck')>Truck</option>
                                    <option value="other" @selected(old('vehicle_type', $deliveryPartner->vehicle_type) === 'other')>Other</option>
                                </select>
                                @error('vehicle_type') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="vehicle_number" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Vehicle Number</label>
                                <input type="text" name="vehicle_number" id="vehicle_number" value="{{ old('vehicle_number', $deliveryPartner->vehicle_number) }}" class="form-input" style="width: 100%;">
                                @error('vehicle_number') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="license_number" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">License Number</label>
                                <input type="text" name="license_number" id="license_number" value="{{ old('license_number', $deliveryPartner->license_number) }}" class="form-input" style="width: 100%;">
                                @error('license_number') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="company_name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Company Name</label>
                                <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $deliveryPartner->company_name) }}" class="form-input" style="width: 100%;">
                                @error('company_name') <p class="form-error" style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                {{-- Info --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Partner Info</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Partner ID</span>
                            <span style="font-family: monospace; font-weight: 500; color: #303030;">{{ $deliveryPartner->partner_id }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #616161;">Joined</span>
                            <span style="color: #303030;">{{ $deliveryPartner->created_at->format('M d, Y') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                            <span style="color: #616161;">Verification</span>
                            @php
                                $vBadge = match($deliveryPartner->verification_status) {
                                    'verified' => 'badge-success',
                                    'rejected' => 'badge-error',
                                    default => 'badge-warning',
                                };
                            @endphp
                            <span class="badge {{ $vBadge }}">{{ ucfirst($deliveryPartner->verification_status) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Verification --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Verification</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Verification Status</label>
                            <select name="verification_status" class="form-select" style="width: 100%;">
                                <option value="pending" @selected(old('verification_status', $deliveryPartner->verification_status) === 'pending')>Pending</option>
                                <option value="verified" @selected(old('verification_status', $deliveryPartner->verification_status) === 'verified')>Verified</option>
                                <option value="rejected" @selected(old('verification_status', $deliveryPartner->verification_status) === 'rejected')>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Verification Note</label>
                            <textarea name="verification_note" rows="2" class="form-textarea" style="width: 100%;" placeholder="Reason for approval/rejection...">{{ old('verification_note', $deliveryPartner->verification_note) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Documents (uploaded by partner) --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 0.25rem;">Documents</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0 0 1rem 0;">Uploaded by the delivery partner</p>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                            <span style="color: #616161;">ID Proof (Aadhaar/PAN)</span>
                            @if($deliveryPartner->id_proof)
                                <a href="{{ asset('storage/' . $deliveryPartner->id_proof) }}" target="_blank" style="color: #005bd3; font-weight: 500; font-size: 12px; text-decoration: none;">View Document</a>
                            @else
                                <span class="badge badge-error">Not Uploaded</span>
                            @endif
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                            <span style="color: #616161;">Driving License</span>
                            @if($deliveryPartner->license_document)
                                <a href="{{ asset('storage/' . $deliveryPartner->license_document) }}" target="_blank" style="color: #005bd3; font-weight: 500; font-size: 12px; text-decoration: none;">View Document</a>
                            @else
                                <span class="badge badge-error">Not Uploaded</span>
                            @endif
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                            <span style="color: #616161;">Address Proof</span>
                            @if($deliveryPartner->address_proof)
                                <a href="{{ asset('storage/' . $deliveryPartner->address_proof) }}" target="_blank" style="color: #005bd3; font-weight: 500; font-size: 12px; text-decoration: none;">View Document</a>
                            @else
                                <span style="font-size: 12px; color: #616161;">Not uploaded</span>
                            @endif
                        </div>
                        @if(!$deliveryPartner->hasDocuments())
                            <div style="margin-top: 0.5rem; padding: 0.5rem; background: #fdf0d5; border-radius: 0.5rem;">
                                <p style="font-size: 12px; color: #916a00; margin: 0;">Partner has not uploaded mandatory documents yet.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Status --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Status</h2>
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $deliveryPartner->is_active)) style="width: 1rem; height: 1rem; accent-color: #303030;">
                        <div>
                            <span style="font-size: 13px; font-weight: 500; color: #303030;">Active</span>
                            <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Partner can receive and deliver orders</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <form action="{{ route('admin.delivery-partners.destroy', $deliveryPartner) }}" method="POST"
                      onsubmit="return confirm('Delete this delivery partner?')" style="display: inline;">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete delivery partner</button>
                </form>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.delivery-partners.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>
</x-layouts.admin>
