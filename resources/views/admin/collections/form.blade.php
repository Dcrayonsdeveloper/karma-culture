{{-- Shared by create and edit so the two cannot drift apart. --}}
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <div class="card" style="padding: 1.25rem;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Collection Details</h2>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label for="name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">
                        Name <span style="color: #d72c0d;">*</span>
                    </label>
                    <input type="text" name="name" id="name" required minlength="2" maxlength="100"
                           value="{{ old('name', $collection->name ?? '') }}"
                           class="form-input" style="width: 100%;" placeholder="e.g. Summer Picks">
                    @error('name') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="slug" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">URL</label>
                    <div style="display: flex; align-items: center; gap: 0.25rem;">
                        <span style="font-size: 13px; color: #8a8a8a;">/collection/</span>
                        <input type="text" name="slug" id="slug" maxlength="120"
                               pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                               title="Lower-case letters, numbers and single hyphens."
                               value="{{ old('slug', $collection->slug ?? '') }}"
                               class="form-input" style="flex: 1;" placeholder="auto-generated-from-name">
                    </div>
                    @error('slug') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Leave empty to build it from the name.</p>
                </div>

                <div>
                    <label for="description" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Description</label>
                    <input type="text" name="description" id="description" maxlength="255"
                           value="{{ old('description', $collection->description ?? '') }}"
                           class="form-input" style="width: 100%;" placeholder="Shown under the heading on the collection page">
                    @error('description') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Products are ticked on the product form, not here: that is where
             someone is already deciding where a product belongs, and it keeps
             one list rather than two that can disagree. --}}
        <div class="card" style="padding: 1.25rem;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 0.5rem;">Products</h2>
            @if(isset($collection))
                <p style="font-size: 13px; color: #616161;">
                    This collection has {{ $collection->products()->count() }}
                    {{ Str::plural('product', $collection->products()->count()) }}.
                    Add or remove them from a product's own page, under <strong>Organization &rarr; Collections</strong>.
                </p>
            @else
                <p style="font-size: 13px; color: #616161;">
                    Save the collection first, then tick it on any product under
                    <strong>Organization &rarr; Collections</strong>.
                </p>
            @endif
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <div class="card" style="padding: 1.25rem;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Visibility</h2>

            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           style="width: 1rem; height: 1rem; accent-color: #303030;"
                           @checked(old('is_active', $collection->is_active ?? true))>
                    <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="hidden" name="show_in_header" value="0">
                    <input type="checkbox" name="show_in_header" value="1" id="show_in_header"
                           style="width: 1rem; height: 1rem; accent-color: #303030;"
                           @checked(old('show_in_header', $collection->show_in_header ?? false))>
                    <label for="show_in_header" style="font-size: 13px; font-weight: 500; color: #303030;">Show in header menu</label>
                </div>

                <p style="font-size: 12px; color: #616161;">
                    Hidden collections keep their page but drop out of the menu, and typing
                    the URL answers 404 - so a link you switch off is off everywhere.
                </p>

                <div>
                    <label for="position" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Menu order</label>
                    <input type="number" name="position" id="position" min="0" max="999"
                           value="{{ old('position', $collection->position ?? 0) }}"
                           class="form-input" style="width: 100%;">
                    @error('position') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">Lower numbers come first.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
    <a href="{{ route('admin.collections.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save collection</button>
</div>
