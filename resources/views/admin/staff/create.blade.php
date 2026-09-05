<x-layouts.admin>
    <x-slot name="title">Add Staff</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.staff.index') }}" class="btn-icon" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add staff member</h1>
    </div>

    {{-- Every label now names its control with for=, and every control carries the
         matching id. That is not only the a11y linkage: labelFor() in app.js reads
         field.labels to write the live message, and with nothing linking the two it
         fell through to its last resort and said "This field is required." for all
         six boxes at once - while the server, for the same empty box, said "The
         first name field is required." One rule, one field, two different sentences
         depending on which side caught it, and the client half named nothing at all.
         The linkage is what lets it say "First Name is required." instead. --}}
    <form action="{{ route('admin.staff.store') }}" method="POST">
        @csrf

        {{-- The one form-level line on this page, and it was missing entirely.

             The Permissions card posts `permissions[]` and StaffController holds every
             value to the eleven section names, but a list of checkboxes has no single
             box for a message to sit under, and nothing above the form spoke for the
             keys that have no box. A request rejected on `permissions.*` therefore
             printed nowhere at all: the save bounced, the page came back, and it looked
             as though "Create staff" had simply done nothing. The same is true of the
             `is_active` pair. This is what a form-level line is for - the keys named in
             `handled` each render their own <x-field-error> beside their control and are
             left to it, so no sentence appears twice, and everything with nowhere else
             to go is printed here instead of being swallowed. --}}
        <x-admin.form-errors title="This staff member could not be created."
                             :handled="['first_name', 'last_name', 'email', 'password', 'role']" />

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Personal Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="first_name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">First Name <span style="color: #d72c0d;">*</span></label>
                                {{-- data-kk-chars is spelled out here rather than inferred: the
                                     inference in app.js reads autocomplete, and an admin creating
                                     a STAFF account must not be offered their own name by autofill,
                                     so these two boxes deliberately carry no autocomplete token. --}}
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required
                                       minlength="2" maxlength="50"
                                       data-kk-chars="personName"
                                       pattern="{{ \App\Rules\ValidationRules::namePattern() }}"
                                       title="The first name may only contain letters, spaces, hyphens, apostrophes and periods."
                                       class="form-input" style="width: 100%;">
                                <x-field-error field="first_name" />
                            </div>
                            <div>
                                <label for="last_name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Last Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required
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
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                   class="form-input" style="width: 100%;">
                            <x-field-error field="email" />
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="password" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Password <span style="color: #d72c0d;">*</span></label>
                                {{-- autocomplete="new-password" does two jobs: it stops the browser
                                     offering the ADMIN's own saved password into a box that mints
                                     somebody else's login, and it is what enrols the box in the
                                     live policy check in app.js. --}}
                                <input type="password" name="password" id="password" required
                                       autocomplete="new-password" minlength="10" maxlength="255"
                                       class="form-input" style="width: 100%;">
                                <x-field-error field="password" />
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    At least 10 characters, including an uppercase and a lowercase
                                    letter, a number and a special character.
                                </p>
                            </div>
                            <div>
                                <label for="password_confirmation" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Confirm Password <span style="color: #d72c0d;">*</span></label>
                                <input type="password" name="password_confirmation" id="password_confirmation" required
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
                            <label for="role" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Role <span style="color: #d72c0d;">*</span></label>
                            <select name="role" id="role" class="form-select" style="width: 100%;" required>
                                <option value="manager" @selected(old('role') === 'manager')>Manager</option>
                                <option value="cashier" @selected(old('role') === 'cashier')>Cashier</option>
                                <option value="support" @selected(old('role') === 'support')>Support</option>
                                <option value="warehouse" @selected(old('role') === 'warehouse')>Warehouse</option>
                            </select>
                            <x-field-error field="role" />
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                   style="width: 1rem; height: 1rem; accent-color: #303030;"
                                   @checked(old('is_active', true))>
                            <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Permissions</h2>
                    <div class="kk-check-list" style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <p style="font-size: 12px; color: #616161; margin-bottom: 0.5rem;">Override default role permissions. Leave unchecked to use role defaults.</p>
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
                        @endphp
                        @foreach($sections as $key => $label)
                            <label style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="permissions[]" value="{{ $key }}"
                                       style="width: 1rem; height: 1rem; accent-color: #303030;"
                                       @checked(is_array(old('permissions')) && in_array($key, old('permissions')))>
                                <span style="font-size: 13px; color: #303030;">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Create staff</button>
            </div>
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
