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

    @include('admin.settings.partials.errors', ['handled' => ['exit_popup_claim_days', 'exit_popup_code', 'exit_popup_image', 'exit_popup_minutes', 'exit_popup_subtitle', 'exit_popup_title', 'offer_popup_image', 'offer_popup_subtitle', 'offer_popup_title']])

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
                        <x-field-error field="offer_popup_title" />
                    </div>

                    <div>
                        <label for="offer-popup-subtitle" class="form-label" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Sub-heading</label>
                        <textarea name="offer_popup_subtitle" id="offer-popup-subtitle" rows="3" maxlength="400" class="form-textarea">{{ old('offer_popup_subtitle', $settings['offer_popup_subtitle'] ?? '') }}</textarea>
                        {{-- Setting::get() treats a blank stored value as unset, so an empty
                             box genuinely does restore the default rather than showing nothing. --}}
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Leave blank to go back to the default wording.</p>
                        <x-field-error field="offer_popup_subtitle" />
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
                        <x-field-error field="offer_popup_image" />
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
                        <x-field-error field="exit_popup_title" />
                    </div>

                    <div>
                        <label for="exit-popup-subtitle" class="form-label" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Sub-heading</label>
                        <textarea name="exit_popup_subtitle" id="exit-popup-subtitle" rows="3" maxlength="400" class="form-textarea">{{ old('exit_popup_subtitle', $settings['exit_popup_subtitle'] ?? '') }}</textarea>
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Leave blank to go back to the default wording.</p>
                        <x-field-error field="exit_popup_subtitle" />
                    </div>

                    {{-- This used to be an <input list> over a <datalist>. The browser
                         drew a dropdown arrow on it, so it read as a picker, but that
                         arrow opens the browser's own suggestion list - which filters
                         itself down to nothing the moment anything is typed, and holds
                         nothing at all on a store with no coupons yet. The field looked
                         like a dropdown and behaved like a text box.

                         The list is ours now: the arrow always opens every code that
                         exists, typing filters it, and free text is still allowed
                         because the coupon may not have been created yet. With no
                         coupons to offer there is no arrow, so the box looks like what
                         it is. --}}
                    <div x-data="kkCodePicker(@js($couponCodes), @js(old('exit_popup_code', $settings['exit_popup_code'] ?? '')))"
                         @keydown.escape.stop="close()" @click.outside="close()"
                         style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr auto; gap: 0.75rem;">
                            <div>
                                <label for="exit-popup-code" class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Discount code</label>
                                <div style="position: relative;">
                                    {{-- The pattern tolerates the whitespace TrimStrings removes
                                         before the server's own regex ever sees the value: a code
                                         pasted with a stray space either side saves fine, so
                                         blocking it here would only be the browser refusing what
                                         the server accepts. --}}
                                    <input type="text" name="exit_popup_code" id="exit-popup-code" maxlength="32" required
                                           pattern="[\s\u200b\ufeff]*[A-Za-z0-9_\-]+[\s\u200b\ufeff]*"
                                           title="Letters, numbers, hyphens and underscores only."
                                           autocomplete="off" role="combobox" aria-autocomplete="list"
                                           aria-controls="exit-popup-code-list" :aria-expanded="open ? 'true' : 'false'"
                                           x-ref="input"
                                           @input="type($event.target.value)"
                                           @keydown.arrow-down.prevent="open ? move(1) : openAll()"
                                           @keydown.arrow-up.prevent="move(-1)"
                                           @keydown.enter="choose($event)"
                                           value="{{ old('exit_popup_code', $settings['exit_popup_code'] ?? '') }}"
                                           class="form-input"
                                           style="text-transform: uppercase;@if(! empty($couponCodes)) padding-right: 2.75rem;@endif">
                                    @if(! empty($couponCodes))
                                        {{-- tabindex -1: the input already opens the list with the
                                             down arrow, so Tab should reach Countdown rather than
                                             stopping on a second control for the same field. --}}
                                        <button type="button" tabindex="-1" @click="toggle()"
                                                :aria-label="open ? 'Hide the discount codes' : 'Show the discount codes that exist'"
                                                style="position: absolute; top: 0; right: 0; height: 100%; width: 2.75rem; display: flex; align-items: center; justify-content: center; padding: 0; background: none; border: 0; color: #616161; cursor: pointer;">
                                            <svg style="width: 14px; height: 14px; transition: transform .15s;" :style="open ? 'transform: rotate(180deg);' : ''"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <ul id="exit-popup-code-list" role="listbox" aria-label="Discount codes" x-show="open" x-cloak
                                            style="position: absolute; z-index: 20; top: calc(100% + 2px); left: 0; right: 0; margin: 0; padding: 0.25rem; list-style: none; max-height: 210px; overflow-y: auto; background: #fff; border: 1px solid #c9cccf; border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.12);">
                                            <template x-for="(option, i) in visible" :key="option">
                                                <li role="presentation">
                                                    <button type="button" role="option" :aria-selected="i === active ? 'true' : 'false'"
                                                            @click="pick(option)" @mousemove="active = i"
                                                            :style="i === active ? 'background: #f1f1f1;' : ''"
                                                            style="display: block; width: 100%; text-align: left; padding: 0.375rem 0.5rem; font-size: 13px; color: #303030; background: none; border: 0; border-radius: 0.375rem; cursor: pointer;"
                                                            x-text="option"></button>
                                                </li>
                                            </template>
                                            <li role="presentation" x-show="! visible.length" style="padding: 0.375rem 0.5rem; font-size: 12px; color: #616161;">No code matches what you have typed.</li>
                                        </ul>
                                    @endif
                                </div>
                                <x-field-error field="exit_popup_code" />
                            </div>
                            {{-- Two numbers that both look like "how long the offer lasts"
                                 sit next to each other here, so each label says which
                                 question it answers. The countdown only bounds the POPUP;
                                 the claim is what the customer actually walks away with. --}}
                            <div>
                                <label for="exit-popup-minutes" class="form-label form-label-required" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Countdown in the popup</label>
                                <input type="number" name="exit_popup_minutes" id="exit-popup-minutes" min="1" max="180" step="1" required inputmode="numeric"
                                       title="Whole minutes, 1 to 180." style="width: 7rem;"
                                       value="{{ old('exit_popup_minutes', $settings['exit_popup_minutes'] ?? '10') }}" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">minutes, then the form closes</p>
                                <x-field-error field="exit_popup_minutes" />
                            </div>
                            <div>
                                <label for="exit-popup-claim-days" class="form-label" style="font-size: 12px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">A claimed offer lasts</label>
                                <input type="number" name="exit_popup_claim_days" id="exit-popup-claim-days" min="1" max="365" step="1" inputmode="numeric"
                                       title="Whole days, 1 to 365." style="width: 7rem;"
                                       value="{{ old('exit_popup_claim_days', $settings['exit_popup_claim_days'] ?? '7') }}" class="form-input">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">days after they claim</p>
                                <x-field-error field="exit_popup_claim_days" />
                            </div>
                        </div>

                        {{-- Always rendered and shown by Alpine rather than by the server, so
                             that picking a real code out of the list clears the warning there
                             and then instead of leaving it contradicting the field until the
                             page is saved. --}}
                        <div role="status" x-show="! known" @if($codeIsKnown) x-cloak style="display: none;" @endif>
                            <div style="display: flex; align-items: flex-start; gap: 0.5rem; padding: 0.625rem 0.75rem; background: #fff5ea; border: 1px solid #ecc999; border-radius: 0.5rem;">
                                <svg style="width: 16px; height: 16px; color: #b98900; flex-shrink: 0; margin-top: 1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                <p style="font-size: 12px; color: #8a6100; margin: 0;">
                                    No coupon matches this code. Customers can still claim the offer and their
                                    claims are being recorded - they will start applying automatically, including
                                    for everyone who already claimed, the moment the coupon exists.
                                    <a href="{{ route('admin.coupons.create') }}">Create it under Coupons</a>, or pick an existing code.
                                </p>
                            </div>
                        </div>
                    </div>

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
                        <x-field-error field="exit_popup_image" />
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

    <script>
        // The Discount code combobox. Codes are compared upper-cased throughout:
        // the box shows whatever case was typed and the server upper-cases what it
        // stores, so "karmaa10" has to count as a match for KARMAA10 or the warning
        // below the field would contradict a code that is about to work.
        function kkCodePicker(codes, initial) {
            return {
                codes: (codes || []).map((code) => String(code)),
                code: String(initial ?? ''),
                open: false,
                // -1 is "nothing highlighted", which is what lets Enter go on
                // submitting the form until an arrow key picks a row.
                active: -1,
                // Set by the arrow button, cleared by typing. The whole point of
                // the button is to show every code, including when what is in the
                // box matches none of them - which is exactly when the browser's
                // own datalist used to show an empty list and look broken.
                showAll: false,
                // Set for the length of the input event pick() raises below.
                // The @input binding on the box cannot tell a dispatch from a
                // keystroke, and treating our own notification as typing would
                // reopen the list the admin has just chosen a code out of.
                syncing: false,

                get visible() {
                    const typed = this.code.trim().toUpperCase();

                    if (this.showAll || typed === '') return this.codes;

                    return this.codes.filter((code) => code.toUpperCase().includes(typed));
                },
                get known() {
                    const typed = this.code.trim().toUpperCase();

                    return typed !== '' && this.codes.some((code) => code.toUpperCase() === typed);
                },
                type(value) {
                    this.code = value;
                    if (this.syncing) return;

                    this.showAll = false;
                    this.active = -1;
                    this.open = this.visible.length > 0;
                },
                openAll() {
                    if (! this.codes.length) return;

                    this.showAll = true;
                    this.active = -1;
                    this.open = true;
                },
                toggle() {
                    if (this.open) {
                        this.close();

                        return;
                    }

                    this.openAll();
                    this.$refs.input.focus();
                },
                move(step) {
                    if (! this.open) return;

                    const count = this.visible.length;
                    if (! count) return;

                    this.active = this.active < 0
                        ? (step > 0 ? 0 : count - 1)
                        : (this.active + step + count) % count;
                },
                // Enter takes the highlighted row, and only then: with the list
                // closed, or open with nothing highlighted, it still saves the page
                // the way Enter does in every other box on this form.
                choose(event) {
                    if (! this.open || this.active < 0) return;

                    event.preventDefault();
                    this.pick(this.visible[this.active]);
                },
                pick(value) {
                    this.code = value;
                    // The input is not x-model bound - it carries the server's value
                    // and its own validation - so the DOM has to be written directly.
                    this.$refs.input.value = value;

                    // Writing .value from script raises nothing, and the site-wide
                    // validator retires a field's message on the EDIT - the input
                    // or change event - because that is the moment the value it
                    // was judging stopped being the value on screen. Choosing a
                    // code out of this list was therefore the one correction it
                    // never heard about: whatever sent the admin to the list in
                    // the first place, the server's "The exit popup code field is
                    // required." or the pattern complaint about what they typed,
                    // stayed sitting under a box that now held a real coupon code,
                    // red outline and aria-invalid with it, until the page was
                    // saved again. Saying out loud what was just written is what
                    // makes picking count as correcting.
                    this.syncing = true;
                    ['input', 'change'].forEach((name) => {
                        this.$refs.input.dispatchEvent(new Event(name, { bubbles: true }));
                    });
                    this.syncing = false;

                    this.close();
                    this.$refs.input.focus();
                },
                close() {
                    this.open = false;
                    this.showAll = false;
                    this.active = -1;
                },
            };
        }
    </script>
</x-layouts.admin>
