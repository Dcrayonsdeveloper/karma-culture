<x-layouts.admin>
    <x-slot name="title">Edit Store</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.stores.index') }}" class="btn-icon" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $store->name }}</h1>
        @if($store->is_active)
            <span class="badge badge-success">Active</span>
        @else
            <span class="badge badge-warning">Inactive</span>
        @endif
    </div>

    <form action="{{ route('admin.stores.update', $store) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Store Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Name <span style="color: #d72c0d;">*</span></label>
                                {{-- Same charset as the create form; see the note there. --}}
                                <input type="text" name="name" value="{{ old('name', $store->name) }}" required
                                       minlength="2" maxlength="255"
                                       pattern="{{ \App\Rules\ValidationRules::namePattern(lettersOnly: true) }}"
                                       data-kk-chars="letters"
                                       title="Letters and spaces only - no digits or punctuation."
                                       class="form-input" style="width: 100%;">
                                <x-field-error field="name" />
                            </div>
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Code <span style="color: #d72c0d;">*</span></label>
                                {{-- maxlength matches the varchar(20) column, not the old max:50 rule,
                                     which MySQL truncated on the way in. --}}
                                <input type="text" name="code" value="{{ old('code', $store->code) }}" required
                                       maxlength="20" pattern="[A-Za-z0-9][A-Za-z0-9 _\-/]*"
                                       title="Start with a letter or number, then letters, numbers, spaces, hyphens, underscores and slashes."
                                       class="form-input" style="width: 100%;">
                                <x-field-error field="code" />
                            </div>
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Address</label>
                            <input type="text" name="address" value="{{ old('address', $store->address) }}"
                                   minlength="3" maxlength="255" autocomplete="street-address"
                                   class="form-input" style="width: 100%;">
                            <x-field-error field="address" />
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Contact Information</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Phone</label>
                                {{-- type="tel" is what makes app.js refuse letters as they are typed
                                     (charPolicy() infers CHAR_POLICIES.phone from it); the pattern
                                     mirrors App\Rules\IndianMobile, so an optional +91 or 0 prefix
                                     and the spacing people write numbers with are all accepted. --}}
                                <input type="tel" name="phone" value="{{ old('phone', $store->phone) }}"
                                       inputmode="numeric" autocomplete="tel" maxlength="20"
                                       pattern="(\+?91[\s\-]?)?0?[6-9][0-9\s\-]{9,}"
                                       title="Enter a 10-digit Indian mobile number starting with 6, 7, 8 or 9."
                                       class="form-input" style="width: 100%;">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">10-digit mobile number. Saved as bare digits.</p>
                                <x-field-error field="phone" />
                            </div>
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Email</label>
                                {{-- pattern is the client-side half of email:strict: the browser's own
                                     type="email" check accepts "store@gmail" with no TLD. --}}
                                <input type="email" name="email" value="{{ old('email', $store->email) }}"
                                       maxlength="200" autocomplete="email" pattern=".+@.+\..+"
                                       title="Enter a full email address, like store@example.com"
                                       class="form-input" style="width: 100%;">
                                <x-field-error field="email" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Status</h2>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               style="width: 1rem; height: 1rem; accent-color: #303030;"
                               @checked(old('is_active', $store->is_active))>
                        <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Info</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Code</span>
                            <span style="font-weight: 500; font-family: monospace; color: #303030;">{{ $store->code }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Created</span>
                            <span style="font-weight: 500; color: #303030;">{{ $store->created_at->format('M d, Y') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Updated</span>
                            <span style="font-weight: 500; color: #303030;">{{ $store->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                {{-- Submits the delete form declared after the edit form below.
                     The two must not nest: the browser hoisted the inner form's
                     _method=DELETE into the edit form, which already sent
                     _method=PUT, and PHP keeps the last value for a repeated key
                     — so clicking Save destroyed the record. --}}
                <button type="submit" form="kk-delete-store" class="btn"
                        style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer; margin-left: -0.75rem;">Delete store</button>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.stores.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>

    <form id="kk-delete-store" action="{{ route('admin.stores.destroy', $store) }}" method="POST"
          onsubmit="return confirm('Delete this store?')">
        @csrf @method('DELETE')
    </form>
</x-layouts.admin>
