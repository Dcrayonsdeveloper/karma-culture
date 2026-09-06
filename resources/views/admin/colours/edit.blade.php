<x-layouts.admin>
    <x-slot name="title">Edit Colour</x-slot>

    {{--
        $usage is what the index screen computes for the whole table; the edit
        screen only wants its own row out of it. It is read defensively because
        the count is decoration on this page, not the point of it - an edit form
        that 500s because a product count could not be worked out would be a
        worse trade than showing 0.
    --}}
    @php($usedBy = ($usage ?? [])[$colour->key] ?? 0)
    {{-- swatch already falls back to the neutral grey when hex_code is null, so
         the picker opens on something sane for a colour that has only ever had
         an uploaded image. --}}
    @php($hex = old('hex_code', $colour->swatch))

    <div>
        <!-- Top bar -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <a href="{{ route('admin.colours.index') }}" class="btn-icon" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $colour->name }}</h1>
                <span class="badge {{ $colour->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $colour->is_active ? 'Active' : 'Hidden' }}</span>
            </div>
        </div>

        <x-admin.form-errors title="The colour was not saved" />

        <form action="{{ route('admin.colours.update', $colour) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <!-- Main content -->
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Colour details</h2>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <label for="name" class="form-label">Name <span style="color: #d72c0d;">*</span></label>
                                {{-- maxlength 60 is the width of cart_items.colour /
                                     order_items.colour, so anything longer could be saved on a
                                     product and then never added to a basket. --}}
                                <input type="text" name="name" id="name" value="{{ old('name', $colour->name) }}" required
                                       maxlength="60" class="form-input">
                                @error('name') <p class="form-error">{{ $message }}</p> @enderror
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    Renaming changes what the picker offers from now on. The {{ $usedBy }} {{ Str::plural('product', $usedBy) }} already saved with the old spelling keep it until each one is re-saved &mdash; and so do every order and cart line that carry it.
                                </p>
                            </div>

                            {{--
                                type="color" degrades to a plain text box in anything that does
                                not implement the picker, and then the admin is free to type
                                "sea green" into a field the storefront will paste straight into
                                a CSS background. pattern + maxlength keep the fallback honest:
                                whatever arrives is a #rrggbb or the form does not submit.
                            --}}
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
                                <textarea name="description" id="description" rows="3" maxlength="2000" class="form-textarea" placeholder="Optional. A note for whoever is filling in products - e.g. the mill's shade code.">{{ old('description', $colour->description) }}</textarea>
                                @error('description') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Media -->
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Media</h2>
                        <div style="display: flex; align-items: center; gap: 1.25rem;">
                            <div style="width: 4rem; height: 4rem; border-radius: 0.75rem; background: #f6f6f7; display: flex; align-items: center; justify-content: center; border: 1px solid #e3e3e3; flex-shrink: 0; overflow: hidden;">
                                @if($colour->image_src)
                                    <img src="{{ $colour->image_src }}" alt="{{ $colour->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <svg width="24" height="24" fill="none" stroke="#c9cccf" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                @endif
                            </div>
                            <div style="flex: 1;">
                                {{-- accept matches the server rule exactly: SVG is not among the
                                     types the upload accepts, so offering it only wasted a round trip. --}}
                                <input type="file" name="image" id="image" aria-label="Colour swatch image"
                                       accept="image/jpeg,image/png,image/webp,image/gif" style="font-size: 13px; color: #616161;">
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    @if($colour->image_src) Upload a new file to replace the current image. @else JPG, PNG, WebP or GIF. Max 2MB. @endif
                                </p>
                                @error('image') <p class="form-error">{{ $message }}</p> @enderror
                                @if($colour->image_src)
                                    {{-- Choosing no file is not the same as asking for the image to
                                         go, so removal needs its own explicit tick - otherwise the
                                         only way back to the flat swatch is to delete the row. --}}
                                    <label style="display: flex; align-items: center; gap: 0.4rem; font-size: 12px; color: #616161; margin-top: 0.5rem; cursor: pointer;">
                                        <input type="checkbox" name="remove_image" value="1" style="width: 0.875rem; height: 0.875rem; accent-color: #303030;">
                                        Remove image and go back to the flat swatch
                                    </label>
                                    @error('remove_image') <p class="form-error">{{ $message }}</p> @enderror
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 0.75rem;">Status</h2>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1"
                                       style="width: 1rem; height: 1rem; accent-color: #303030;"
                                       @checked(old('is_active', $colour->is_active))>
                                <div>
                                    <span style="font-size: 13px; font-weight: 500; color: #303030;">Active</span>
                                    <p style="font-size: 12px; color: #616161;">Offered in the product form&rsquo;s colour picker</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 0.75rem;">Info</h2>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between; gap: 0.5rem;">
                                <span style="color: #616161;">Used by</span>
                                <span style="font-weight: 500; color: {{ $usedBy > 0 ? '#303030' : '#8a8a8a' }};">{{ $usedBy }} {{ Str::plural('product', $usedBy) }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; gap: 0.5rem;">
                                <span style="color: #616161;">Created</span>
                                <span style="font-weight: 500; color: #303030;">{{ $colour->created_at?->format('M d, Y') }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; gap: 0.5rem;">
                                <span style="color: #616161;">Matching key</span>
                                <span style="font-weight: 500; color: #303030; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; word-break: break-all; text-align: right;">{{ $colour->key }}</span>
                            </div>
                        </div>
                        {{-- The key is shown, not hidden, because it is the answer to the
                             question this screen otherwise raises: an admin who types "Black"
                             and is told the colour already exists wants to see that the
                             existing row's key is "black" and therefore the same entry. --}}
                        <p style="font-size: 12px; color: #616161; margin-top: 0.75rem; line-height: 1.5;">
                            The matching key is the name with case and spacing flattened. Two entries cannot share one, which is why &ldquo;Black&rdquo; and &ldquo;black&rdquo; are the same colour rather than two.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                {{-- The delete form is hoisted out of the edit form below and the button
                     re-attached with form="record-delete-form". Nested, its _method=DELETE
                     is hoisted into the edit form alongside _method=PUT, PHP keeps the last
                     value, and pressing Save destroys the record. --}}
                <button type="submit" form="record-delete-form" class="btn" style="padding: 0; font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete colour</button>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.colours.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
        </form>

        <form id="record-delete-form" action="{{ route('admin.colours.destroy', $colour) }}" method="POST"
              onsubmit="return confirm('Delete the colour &quot;{{ addslashes($colour->name) }}&quot;? Products already saved with it keep the label - it just stops being offered in the picker.')">
            @csrf @method('DELETE')
        </form>
    </div>
</x-layouts.admin>
