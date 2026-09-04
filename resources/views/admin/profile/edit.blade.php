<x-layouts.admin>
    <x-slot name="title">Profile Settings</x-slot>

    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Profile Settings</h1>
    <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 1rem 0;">Manage your admin account</p>

    {{-- Every label names its control with for=, and every control carries the
         matching id. Beyond the a11y linkage, that is what lets the live checks
         name the field at all: labelFor() in app.js reads field.labels, and with
         nothing joining the two it fell through to its last resort and said "This
         field is required." for every box on the page, while the server named each
         one - the same rule on the same field, worded two different ways depending
         on which side caught it. --}}
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
                                <label for="first_name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">First Name <span style="color: #d72c0d;">*</span></label>
                                {{-- data-kk-chars rather than an inferred autocomplete token: this
                                     is the admin panel, and these boxes are deliberately left out
                                     of the browser's autofill. maxlength is 50 because that is the
                                     width of users.first_name; the rule behind it said 255. --}}
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}" required
                                       minlength="2" maxlength="50"
                                       data-kk-chars="personName"
                                       pattern="{{ \App\Rules\ValidationRules::namePattern() }}"
                                       title="The first name may only contain letters, spaces, hyphens, apostrophes and periods."
                                       class="form-input" style="width: 100%;">
                                <x-field-error field="first_name" />
                            </div>
                            <div>
                                <label for="last_name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Last Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}" required
                                       minlength="2" maxlength="50"
                                       data-kk-chars="personName"
                                       pattern="{{ \App\Rules\ValidationRules::namePattern() }}"
                                       title="The last name may only contain letters, spaces, hyphens, apostrophes and periods."
                                       class="form-input" style="width: 100%;">
                                <x-field-error field="last_name" />
                            </div>
                        </div>

                        <div>
                            <label for="email" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Email <span style="color: #d72c0d;">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                   class="form-input" style="width: 100%;">
                            <x-field-error field="email" />
                        </div>
                    </div>
                </div>

                @if(false) {{-- ADMIN_PASSWORD_CARD_HIDDEN_2026-09-02: temporary hide of the Change Password card, restore 2026-09-03 by flipping false to true --}}
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Change Password</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <p style="font-size: 12px; color: #616161; margin: 0;">Leave blank to keep your current password.</p>

                        <div>
                            <label for="current_password" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Current Password</label>
                            <div class="relative" style="position: relative;">
                                <input type="password" name="current_password" id="current_password" autocomplete="current-password"
                                       class="form-input" style="width: 100%; padding-right: 2.75rem;">
                                <x-admin.password-toggle label="current password" />
                            </div>
                            <x-field-error field="current_password" />
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="password" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">New Password</label>
                                <div class="relative" style="position: relative;">
                                    {{-- Optional, so no `required`; minlength applies only once
                                         something has been typed. autocomplete="new-password" is
                                         what enrols the box in the live policy check in app.js. --}}
                                    <input type="password" name="password" id="password"
                                           autocomplete="new-password" minlength="10" maxlength="255"
                                           class="form-input" style="width: 100%; padding-right: 2.75rem;">
                                    <x-admin.password-toggle label="new password" />
                                </div>
                                <x-field-error field="password" />
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    At least 10 characters, including an uppercase and a lowercase
                                    letter, a number and a special character.
                                </p>
                            </div>
                            <div>
                                <label for="password_confirmation" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Confirm New Password</label>
                                <div class="relative" style="position: relative;">
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                           autocomplete="new-password" maxlength="255"
                                           class="form-input" style="width: 100%; padding-right: 2.75rem;">
                                    <x-admin.password-toggle label="password confirmation" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
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
                            <div style="min-width: 0;">
                                <p style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">{{ $user->first_name }} {{ $user->last_name }}</p>
                                <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0; overflow-wrap: anywhere;">{{ $user->email }}</p>
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
            <a href="{{ route('admin.profile') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
        </div>
    </form>
</x-layouts.admin>
