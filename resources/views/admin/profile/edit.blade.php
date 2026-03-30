<x-layouts.admin>
    <x-slot name="title">Profile Settings</x-slot>

    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Profile Settings</h1>
    <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 1rem 0;">Manage your admin account</p>

    <form action="{{ route('admin.profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <!-- Left Column -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Personal Information</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">First Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required
                                       class="form-input" style="width: 100%;">
                                @error('first_name')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Last Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                                       class="form-input" style="width: 100%;">
                                @error('last_name')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Email <span style="color: #d72c0d;">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="form-input" style="width: 100%;">
                            @error('email')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Change Password</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <p style="font-size: 12px; color: #616161; margin: 0;">Leave blank to keep your current password.</p>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Current Password</label>
                            <input type="password" name="current_password" class="form-input" style="width: 100%;">
                            @error('current_password')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">New Password</label>
                                <input type="password" name="password" class="form-input" style="width: 100%;">
                                @error('password')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Confirm New Password</label>
                                <input type="password" name="password_confirmation" class="form-input" style="width: 100%;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Account Info</h2>
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 48px; height: 48px; background: #e0f0ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span style="font-size: 16px; font-weight: 600; color: #005bd3;">
                                    {{ strtoupper(substr($user->first_name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <p style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">{{ $user->first_name }} {{ $user->last_name }}</p>
                                <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">{{ $user->email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Role</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.625rem; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #616161;">Role</span>
                            <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #e0f0ff; color: #005bd3;">{{ ucwords(str_replace('_', ' ', $admin->role)) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #616161;">Status</span>
                            @if($admin->is_active)
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                            @else
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">Inactive</span>
                            @endif
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #616161;">Member Since</span>
                            <span style="font-weight: 500; color: #303030;">{{ $admin->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save bar -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
            <a href="{{ route('admin.profile.edit') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
        </div>
    </form>
</x-layouts.admin>
