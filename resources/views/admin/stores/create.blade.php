<x-layouts.admin>
    <x-slot name="title">Add Store</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.stores.index') }}" class="btn-icon" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add store</h1>
    </div>

    <form action="{{ route('admin.stores.store') }}" method="POST">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Store Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Name <span style="color: #d72c0d;">*</span></label>
                                {{-- Letters and spaces only, mirroring V::name(lettersOnly: true).
                                     The pattern is echoed from namePattern() rather than typed out:
                                     it carries the invisible-character clauses TrimStrings strips
                                     server-side, so a name pasted out of Word is not refused over a
                                     character nobody can see. data-kk-chars stops the keystroke. --}}
                                <input type="text" name="name" value="{{ old('name') }}" required
                                       minlength="2" maxlength="255"
                                       pattern="{{ \App\Rules\ValidationRules::namePattern(lettersOnly: true) }}"
                                       data-kk-chars="letters"
                                       title="Letters and spaces only - no digits or punctuation."
                                       class="form-input" style="width: 100%;" placeholder="e.g. Main Street Store">
                                @error('name')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Code <span style="color: #d72c0d;">*</span></label>
                                {{-- maxlength matches the varchar(20) column, not the old max:50 rule,
                                     which MySQL truncated on the way in. --}}
                                <input type="text" name="code" value="{{ old('code') }}" required
                                       maxlength="20" pattern="[A-Za-z0-9][A-Za-z0-9 _\-/]*"
                                       title="Start with a letter or number, then letters, numbers, spaces, hyphens, underscores and slashes."
                                       class="form-input" style="width: 100%;" placeholder="e.g. STORE-001">
                                @error('code')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Address</label>
                            <input type="text" name="address" value="{{ old('address') }}"
                                   minlength="3" maxlength="255" autocomplete="street-address"
                                   class="form-input" style="width: 100%;" placeholder="Street address">
                            @error('address')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
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
                                     and the spacing people write numbers with are all accepted.
                                     Anything narrower would reject numbers the server takes. --}}
                                <input type="tel" name="phone" value="{{ old('phone') }}"
                                       inputmode="numeric" autocomplete="tel" maxlength="20"
                                       pattern="(\+?91[\s\-]?)?0?[6-9][0-9\s\-]{9,}"
                                       title="Enter a 10-digit Indian mobile number starting with 6, 7, 8 or 9."
                                       class="form-input" style="width: 100%;" placeholder="98765 43210">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">10-digit mobile number. Saved as bare digits.</p>
                                @error('phone')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Email</label>
                                {{-- pattern is the client-side half of email:strict: the browser's own
                                     type="email" check accepts "store@gmail" with no TLD. --}}
                                <input type="email" name="email" value="{{ old('email') }}"
                                       maxlength="200" autocomplete="email" pattern=".+@.+\..+"
                                       title="Enter a full email address, like store@example.com"
                                       class="form-input" style="width: 100%;" placeholder="store@example.com">
                                @error('email')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
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
                               @checked(old('is_active', true))>
                        <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                    </div>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.stores.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save store</button>
            </div>
    </form>
</x-layouts.admin>
