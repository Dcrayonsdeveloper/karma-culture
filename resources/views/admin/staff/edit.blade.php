<x-layouts.admin>
    <x-slot name="title">Edit Staff</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.staff.index') }}" class="btn-icon" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $staff->user->full_name ?? 'Staff' }}</h1>
        @if($staff->is_active)
            <span class="badge badge-success">Active</span>
        @else
            <span class="badge badge-warning">Inactive</span>
        @endif
    </div>

    <form action="{{ route('admin.staff.update', $staff) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Personal Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">First Name <span style="color: #d72c0d;">*</span></label>
                                {{-- data-kk-chars is spelled out here rather than inferred: the
                                     inference in app.js reads autocomplete, and an admin editing
                                     a STAFF account must not be offered their own name by autofill,
                                     so these two boxes deliberately carry no autocomplete token. --}}
                                <input type="text" name="first_name" value="{{ old('first_name', $staff->user->first_name) }}" required
                                       minlength="2" maxlength="50"
                                       data-kk-chars="personName"
                                       pattern="{{ \App\Rules\ValidationRules::namePattern() }}"
                                       title="The first name may only contain letters, spaces, hyphens, apostrophes and periods."
                                       class="form-input" style="width: 100%;">
                                <x-field-error field="first_name" />
                            </div>
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Last Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="last_name" value="{{ old('last_name', $staff->user->last_name) }}" required
                                       minlength="2" maxlength="50"
                                       data-kk-chars="personName"
                                       pattern="{{ \App\Rules\ValidationRules::namePattern() }}"
                                       title="The last name may only contain letters, spaces, hyphens, apostrophes and periods."
                                       class="form-input" style="width: 100%;">
                                <x-field-error field="last_name" />
                            </div>
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Email <span style="color: #d72c0d;">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $staff->user->email) }}" required
                                   class="form-input" style="width: 100%;">
                            <x-field-error field="email" />
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">New Password</label>
                                {{-- No `required` - the box is optional and says so - but minlength
                                     still applies the moment anything is typed into it, and
                                     autocomplete="new-password" both keeps the admin's own saved
                                     password out of it and enrols it in the live policy check. --}}
                                <input type="password" name="password"
                                       autocomplete="new-password" minlength="10" maxlength="255"
                                       class="form-input" style="width: 100%;" placeholder="Leave blank to keep current">
                                <x-field-error field="password" />
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    At least 10 characters, including an uppercase and a lowercase
                                    letter, a number and a special character.
                                </p>
                            </div>
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Confirm Password</label>
                                <input type="password" name="password_confirmation"
                                       autocomplete="new-password" maxlength="255"
                                       class="form-input" style="width: 100%;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Role & Status</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Role <span style="color: #d72c0d;">*</span></label>
                            <select name="role" class="form-select" style="width: 100%;" required>
                                <option value="manager" @selected(old('role', $staff->role) === 'manager')>Manager</option>
                                <option value="cashier" @selected(old('role', $staff->role) === 'cashier')>Cashier</option>
                                <option value="support" @selected(old('role', $staff->role) === 'support')>Support</option>
                                <option value="warehouse" @selected(old('role', $staff->role) === 'warehouse')>Warehouse</option>
                            </select>
                            <x-field-error field="role" />
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                   style="width: 1rem; height: 1rem; accent-color: #303030;"
                                   @checked(old('is_active', $staff->is_active))>
                            <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Permissions</h2>
                    <div class="kk-check-list" style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <p style="font-size: 12px; color: #616161; margin-bottom: 0.5rem;">Override default role permissions. Leave all unchecked to use role defaults.</p>
                        @php
                            $sections = [
                                'dashboard' => 'Dashboard',
                                'orders' => 'Orders & Returns',
                                'abandoned_carts' => 'Abandoned Carts',
                                'catalog' => 'Catalog & Inventory',
                                'customers' => 'Customers',
                                'sellers' => 'Sellers',
                                'staff' => 'Staff Management',
                                'marketing' => 'Marketing',
                                'storefront' => 'Storefront',
                                'content' => 'Content & Reviews',
                                'reports' => 'Reports',
                                'settings' => 'Settings',
                            ];
                            $currentPerms = old('permissions', $staff->permissions ?? []);
                        @endphp
                        @foreach($sections as $key => $label)
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                       style="width: 1rem; height: 1rem; accent-color: #303030;"
                                       @checked(is_array($currentPerms) && in_array($key, $currentPerms))>
                                <span style="font-size: 13px; color: #303030;">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Info</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Created</span>
                            <span style="font-weight: 500; color: #303030;">{{ $staff->created_at->format('M d, Y') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Updated</span>
                            <span style="font-weight: 500; color: #303030;">{{ $staff->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <button type="submit" form="kk-staff-delete" class="btn" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer; margin-left: -0.75rem;">Delete staff</button>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>

    {{-- Deliberately outside the edit form. Forms cannot nest: when this sat inside,
         the browser hoisted its _method=DELETE hidden input into the edit form, which
         already carried _method=PUT. PHP keeps the last value for a repeated key, so
         DELETE won and clicking Save destroyed the staff account. The button above
         reaches this form by id instead, which keeps the save bar's layout intact. --}}
    <form id="kk-staff-delete" action="{{ route('admin.staff.destroy', $staff) }}" method="POST"
          onsubmit="return confirm('Delete this staff member?')">
        @csrf @method('DELETE')
    </form>
    @push('styles')
    <style>
        /* Touch: each permission row reaches a 36px target. */
        @media (pointer: coarse) {
            .kk-check-list > label { min-height: 2.25rem; }
        }
    </style>
    @endpush
</x-layouts.admin>
