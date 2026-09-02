@php
    use App\Support\PopupSettings;

    $offerImage = PopupSettings::imageUrl((string) ($settings['offer_popup_image'] ?? ''));
    $exitImage  = PopupSettings::imageUrl((string) ($settings['exit_popup_image'] ?? ''));
@endphp

<x-layouts.admin>
    <x-slot name="title">Popup Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Settings</h1>
        </div>
    </x-slot>

    @include('admin.settings.partials.nav', ['active' => 'popups'])

    @include('admin.settings.partials.errors')

    {{-- enctype: both popups take an image, and without it the file inputs post
         their filename as text and no upload ever arrives. --}}
    <form action="{{ route('admin.settings.popups.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">

            {{-- ============================ Offer popup ============================ --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Offer Popup</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Shown on the <strong>homepage only</strong>, a few seconds after it loads, once per visitor. Collects name, email and mobile number.</p>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">

                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.875rem; border-radius: 0.5rem; border: 1px solid #e3e3e3;">
                        <div>
                            <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">Show this popup</p>
                            <p style="font-size: 12px; color: #616161; margin: 0;">Turn off to stop it appearing without losing your copy.</p>
                        </div>
                        <label class="toggle-switch" style="flex-shrink: 0; margin-left: 1rem;">
                            <input type="hidden" name="offer_popup_enabled" value="0">
                            <input type="checkbox" name="offer_popup_enabled" value="1" @checked(old('offer_popup_enabled', $settings['offer_popup_enabled'] ?? '1'))>
                            <div class="toggle-track"></div>
                        </label>
                    </div>

                    <div>
                        <label for="offer-popup-title" class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Heading</label>
                        <input type="text" name="offer_popup_title" id="offer-popup-title" maxlength="120" required
                               value="{{ old('offer_popup_title', $settings['offer_popup_title'] ?? '') }}" class="form-input">
                        @error('offer_popup_title') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="offer-popup-subtitle" class="form-label" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Sub-heading</label>
                        <textarea name="offer_popup_subtitle" id="offer-popup-subtitle" rows="3" maxlength="400" class="form-textarea">{{ old('offer_popup_subtitle', $settings['offer_popup_subtitle'] ?? '') }}</textarea>
                        {{-- Setting::get() treats a blank stored value as unset, so an empty
                             box genuinely does restore the default rather than showing nothing. --}}
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Leave blank to go back to the default wording.</p>
                        @error('offer_popup_subtitle') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="offer-popup-image" class="form-label" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Side image</label>
                        @if($offerImage)
                            <img src="{{ $offerImage }}" alt="Current offer popup image" style="display: block; width: 100%; max-width: 220px; border-radius: 0.5rem; border: 1px solid #e3e3e3; margin-bottom: 0.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.375rem; font-size: 12px; color: #616161; margin-bottom: 0.5rem;">
                                <input type="checkbox" name="offer_popup_image_remove" value="1">
                                Remove this image
                            </label>
                        @endif
                        <input type="file" name="offer_popup_image" id="offer-popup-image"
                               accept="image/jpeg,image/png,image/webp" style="font-size: 13px; color: #616161;">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Optional. JPG, PNG or WebP, max 2MB. Recommended 600x660px. Without one the brown gradient is used.</p>
                        @error('offer_popup_image') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ============================= Exit popup ============================= --}}
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Exit-Intent Popup</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Shown on <strong>every storefront page</strong>, once per visitor, when the pointer leaves the window or after a minute on the page. Hands out a discount code.</p>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">

                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.875rem; border-radius: 0.5rem; border: 1px solid #e3e3e3;">
                        <div>
                            <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">Show this popup</p>
                            <p style="font-size: 12px; color: #616161; margin: 0;">Turn off to stop it appearing without losing your copy.</p>
                        </div>
                        <label class="toggle-switch" style="flex-shrink: 0; margin-left: 1rem;">
                            <input type="hidden" name="exit_popup_enabled" value="0">
                            <input type="checkbox" name="exit_popup_enabled" value="1" @checked(old('exit_popup_enabled', $settings['exit_popup_enabled'] ?? '1'))>
                            <div class="toggle-track"></div>
                        </label>
                    </div>

                    <div>
                        <label for="exit-popup-title" class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Heading</label>
                        <input type="text" name="exit_popup_title" id="exit-popup-title" maxlength="120" required
                               value="{{ old('exit_popup_title', $settings['exit_popup_title'] ?? '') }}" class="form-input">
                        @error('exit_popup_title') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="exit-popup-subtitle" class="form-label" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Sub-heading</label>
                        <textarea name="exit_popup_subtitle" id="exit-popup-subtitle" rows="3" maxlength="400" class="form-textarea">{{ old('exit_popup_subtitle', $settings['exit_popup_subtitle'] ?? '') }}</textarea>
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Leave blank to go back to the default wording.</p>
                        @error('exit_popup_subtitle') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 0.75rem;">
                        <div>
                            <label for="exit-popup-code" class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Discount code</label>
                            {{-- list: the codes that actually exist, so the usual case is a
                                 pick rather than a retype. Free text is still allowed - the
                                 coupon may not have been created yet. --}}
                            <input type="text" name="exit_popup_code" id="exit-popup-code" maxlength="32" required
                                   pattern="[A-Za-z0-9_\-]+" title="Letters, numbers, hyphens and underscores only."
                                   list="exit-popup-code-options" style="text-transform: uppercase;"
                                   value="{{ old('exit_popup_code', $settings['exit_popup_code'] ?? '') }}" class="form-input">
                            <datalist id="exit-popup-code-options">
                                @foreach($couponCodes as $code)
                                    <option value="{{ $code }}"></option>
                                @endforeach
                            </datalist>
                            @error('exit_popup_code') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="exit-popup-minutes" class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Countdown</label>
                            <input type="number" name="exit_popup_minutes" id="exit-popup-minutes" min="1" max="180" step="1" required inputmode="numeric"
                                   title="Whole minutes, 1 to 180." style="width: 7rem;"
                                   value="{{ old('exit_popup_minutes', $settings['exit_popup_minutes'] ?? '10') }}" class="form-input">
                            <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">minutes</p>
                            @error('exit_popup_minutes') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @unless($codeIsKnown)
                        {{-- The countdown is display only: it does not expire the coupon, and
                             nothing else checks that this code exists. A customer given a code
                             with no coupon behind it finds out at checkout. --}}
                        <div role="status" style="display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.625rem 0.75rem; background: #fff5ea; border: 1px solid #ecc999; border-radius: 0.5rem;">
                            <svg style="width: 16px; height: 16px; color: #b98900; flex-shrink: 0; margin-top: 1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            <p style="font-size: 12px; color: #8a6100; margin: 0;">
                                No coupon matches this code, so it will be rejected at checkout.
                                <a href="{{ route('admin.coupons.create') }}">Create it under Coupons</a>, or pick an existing code.
                            </p>
                        </div>
                    @endunless

                    <div>
                        <label for="exit-popup-image" class="form-label" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Side image</label>
                        @if($exitImage)
                            <img src="{{ $exitImage }}" alt="Current exit popup image" style="display: block; width: 100%; max-width: 220px; border-radius: 0.5rem; border: 1px solid #e3e3e3; margin-bottom: 0.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.375rem; font-size: 12px; color: #616161; margin-bottom: 0.5rem;">
                                <input type="checkbox" name="exit_popup_image_remove" value="1">
                                Remove this image
                            </label>
                        @endif
                        <input type="file" name="exit_popup_image" id="exit-popup-image"
                               accept="image/jpeg,image/png,image/webp" style="font-size: 13px; color: #616161;">
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Optional. JPG, PNG or WebP, max 2MB. Recommended 600x660px. Without one the brown gradient is used.</p>
                        @error('exit_popup_image') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 1rem; padding: 0.75rem 1rem; background: #f6f6f7; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Changes</button>
        </div>
    </form>

    {{-- Where the rest of each popup lives: the names, emails and numbers both
         of them collect, and the coupon the exit popup gives away. --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
        <div class="card" style="padding: 0.875rem 1rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                <div>
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Captured subscribers</h3>
                    <p style="font-size: 12px; color: #616161; margin: 0;">Everyone who submitted either popup</p>
                </div>
                <a href="{{ route('admin.newsletter.index') }}" class="btn btn-secondary" style="font-size: 13px; white-space: nowrap;">Open Newsletter</a>
            </div>
        </div>
        <div class="card" style="padding: 0.875rem 1rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                <div>
                    <h3 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Discount codes</h3>
                    <p style="font-size: 12px; color: #616161; margin: 0;">The coupon behind the exit-intent offer</p>
                </div>
                <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary" style="font-size: 13px; white-space: nowrap;">Manage Coupons</a>
            </div>
        </div>
    </div>
</x-layouts.admin>
