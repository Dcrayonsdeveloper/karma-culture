<x-layouts.admin>
    <x-slot name="title">Edit Size</x-slot>

    {{--
        $usage is what the index screen computes for the whole table; the edit
        screen only wants its own row out of it. It is read defensively because
        the count is decoration on this page, not the point of it - an edit form
        that 500s because a product count could not be worked out would be a
        worse trade than showing 0.
    --}}
    @php($usedBy = ($usage ?? [])[$size->key] ?? 0)

    <div>
        <!-- Top bar -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <a href="{{ route('admin.sizes.index') }}" class="btn-icon" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $size->name }}</h1>
                <span class="badge {{ $size->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $size->is_active ? 'Active' : 'Hidden' }}</span>
            </div>
        </div>

        <x-admin.form-errors title="The size was not saved" />

        <form action="{{ route('admin.sizes.update', $size) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <!-- Main content -->
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Size details</h2>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <label for="name" class="form-label">Name <span style="color: #d72c0d;">*</span></label>
                                {{-- No minlength - "S" and "M" are one character. maxlength 50 is
                                     the width of cart_items.size / order_items.size, so anything
                                     longer could be saved on a product and then never added to a
                                     basket. --}}
                                <input type="text" name="name" id="name" value="{{ old('name', $size->name) }}" required
                                       maxlength="50" class="form-input">
                                @error('name') <p class="form-error">{{ $message }}</p> @enderror
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    Renaming changes what the picker offers from now on. The {{ $usedBy }} {{ Str::plural('product', $usedBy) }} already saved with the old spelling keep it until each one is re-saved &mdash; and so do every order and cart line that carry it.
                                </p>
                            </div>
                            <div>
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" rows="3" maxlength="2000" class="form-textarea" placeholder="Optional. A note for whoever is filling in products - e.g. fits 38-40 inch chest.">{{ old('description', $size->description) }}</textarea>
                                @error('description') <p class="form-error">{{ $message }}</p> @enderror
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
                                       @checked(old('is_active', $size->is_active))>
                                <div>
                                    <span style="font-size: 13px; font-weight: 500; color: #303030;">Active</span>
                                    <p style="font-size: 12px; color: #616161;">Offered in the product form&rsquo;s size picker</p>
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
                                <span style="font-weight: 500; color: #303030;">{{ $size->created_at?->format('M d, Y') }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; gap: 0.5rem;">
                                <span style="color: #616161;">Matching key</span>
                                <span style="font-weight: 500; color: #303030; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; word-break: break-all; text-align: right;">{{ $size->key }}</span>
                            </div>
                        </div>
                        {{-- The key is shown, not hidden, because it is the answer to the
                             question this screen otherwise raises: an admin who types "XL"
                             and is told the size already exists wants to see that the
                             existing row's key is "xl" and therefore the same entry. --}}
                        <p style="font-size: 12px; color: #616161; margin-top: 0.75rem; line-height: 1.5;">
                            The matching key is the name with case and spacing flattened. Two entries cannot share one, which is why &ldquo;XL&rdquo; and &ldquo;xl&rdquo; are the same size rather than two.
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
                <button type="submit" form="record-delete-form" class="btn" style="padding: 0; font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete size</button>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.sizes.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
        </form>

        <form id="record-delete-form" action="{{ route('admin.sizes.destroy', $size) }}" method="POST"
              onsubmit="return confirm('Delete the size &quot;{{ addslashes($size->name) }}&quot;? Products already saved with it keep the label - it just stops being offered in the picker.')">
            @csrf @method('DELETE')
        </form>
    </div>
</x-layouts.admin>
