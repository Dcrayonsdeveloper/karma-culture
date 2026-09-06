<x-layouts.admin>
    <x-slot name="title">Add Colour</x-slot>

    <div>
        <!-- Top bar -->
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
            <a href="{{ route('admin.colours.index') }}" class="btn-icon" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add colour</h1>
        </div>

        <x-admin.form-errors title="The colour was not saved" />

        <form action="{{ route('admin.colours.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <!-- Main content -->
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Colour details</h2>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <label for="name" class="form-label">Name <span style="color: #d72c0d;">*</span></label>
                                {{--
                                    maxlength is 60 because that is the width of
                                    cart_items.colour and order_items.colour. A longer label
                                    would save happily on the product and then be truncated -
                                    or rejected - the first time a shopper tried to put it in
                                    a basket, which is a bug nobody would trace back to here.
                                --}}
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       maxlength="60" class="form-input" placeholder="e.g. Sea Green">
                                @error('name') <p class="form-error">{{ $message }}</p> @enderror
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    The spelling you type here is the spelling shoppers see, on the product page and on the shop&rsquo;s shade rail. Case and stray spaces are ignored when entries are matched, so &ldquo;Black&rdquo; and &ldquo;black&rdquo; will not become two separate colours.
                                </p>
                            </div>

                            {{--
                                type="color" degrades to a plain text box in anything that does
                                not implement the picker, and then the admin is free to type
                                "sea green" into a field the storefront will paste straight into
                                a CSS background. pattern + maxlength keep the fallback honest:
                                whatever arrives is a #rrggbb or the form does not submit.
                            --}}
                            @php($hex = old('hex_code', '#111111'))
                            <div x-data="{ hex: @js($hex) }">
                                <label for="hex_code" class="form-label">Swatch</label>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="color" name="hex_code" id="hex_code" x-model="hex"
                                           value="{{ $hex }}"
                                           maxlength="7" pattern="#[0-9A-Fa-f]{6}"
                                           title="A six-digit hex colour, e.g. #2E8B57"
                                           style="width: 2.5rem; height: 2.5rem; border-radius: 0.375rem; border: 1px solid #c9cccf; cursor: pointer; padding: 0.125rem; flex-shrink: 0;">
                                    {{-- Mirror only: readonly, so there is one field to fix when the
                                         value is wrong and no second copy of hex_code is posted. It
                                         carries its own value= as well as x-model so the hex is
                                         readable in the instant before Alpine boots. --}}
                                    <input type="text" x-model="hex" value="{{ $hex }}" aria-label="Swatch hex code" class="form-input" style="flex: 1; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;" readonly>
                                </div>
                                @error('hex_code') <p class="form-error">{{ $message }}</p> @enderror
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    The dot shoppers tap on the shade rail. Pick the colour of the fabric, not of the photograph &mdash; and for a print or a melange, upload an image below instead: it takes the swatch&rsquo;s place wherever both exist.
                                </p>
                            </div>

                            <div>
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" rows="3" maxlength="2000" class="form-textarea" placeholder="Optional. A note for whoever is filling in products - e.g. the mill's shade code.">{{ old('description') }}</textarea>
                                @error('description') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Media -->
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Media</h2>
                        <div style="display: flex; align-items: center; gap: 1.25rem;">
                            <div style="width: 4rem; height: 4rem; border-radius: 0.75rem; background: #f6f6f7; display: flex; align-items: center; justify-content: center; border: 1px solid #e3e3e3; flex-shrink: 0;">
                                <svg width="24" height="24" fill="none" stroke="#c9cccf" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div style="flex: 1;">
                                {{-- accept matches the server rule exactly: SVG is not among the
                                     types the upload accepts, so offering it only wasted a round trip. --}}
                                <input type="file" name="image" id="image" aria-label="Colour swatch image"
                                       accept="image/jpeg,image/png,image/webp,image/gif" style="font-size: 13px; color: #616161;">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">JPG, PNG, WebP or GIF. Max 2MB.</p>
                                @error('image') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 0.75rem;">Status</h2>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            {{-- The hidden 0 in front of the checkbox is what makes an unticked
                                 box post at all: without it the key is simply absent and the
                                 old value survives the save. --}}
                            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1"
                                       style="width: 1rem; height: 1rem; accent-color: #303030;"
                                       @checked(old('is_active', true))>
                                <div>
                                    <span style="font-size: 13px; font-weight: 500; color: #303030;">Active</span>
                                    <p style="font-size: 12px; color: #616161;">Offered in the product form&rsquo;s colour picker</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.colours.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save colour</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
