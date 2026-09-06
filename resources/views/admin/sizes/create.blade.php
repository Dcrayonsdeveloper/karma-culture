<x-layouts.admin>
    <x-slot name="title">Add Size</x-slot>

    <div>
        <!-- Top bar -->
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
            <a href="{{ route('admin.sizes.index') }}" class="btn-icon" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add size</h1>
        </div>

        <x-admin.form-errors title="The size was not saved" />

        <form action="{{ route('admin.sizes.store') }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <!-- Main content -->
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Size details</h2>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div>
                                <label for="name" class="form-label">Name <span style="color: #d72c0d;">*</span></label>
                                {{--
                                    No minlength: "S" and "M" are one character each, and the
                                    minlength="2" copied from the brand form would make the two
                                    most common sizes in the catalogue impossible to enter.

                                    maxlength is 50 because that is the width of cart_items.size
                                    and order_items.size. A longer label would save happily on the
                                    product and then be truncated - or rejected - the first time a
                                    shopper tried to put it in a basket.
                                --}}
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       maxlength="50" class="form-input" placeholder="e.g. XL">
                                @error('name') <p class="form-error">{{ $message }}</p> @enderror
                                <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                                    The spelling you type here is the spelling shoppers see, on the product page and on the shop&rsquo;s size rail. Case and stray spaces are ignored when entries are matched, so &ldquo;XL&rdquo; and &ldquo;xl&rdquo; will not become two separate sizes.
                                </p>
                            </div>
                            <div>
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" rows="3" maxlength="2000" class="form-textarea" placeholder="Optional. A note for whoever is filling in products - e.g. fits 38-40 inch chest.">{{ old('description') }}</textarea>
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
                                    <p style="font-size: 12px; color: #616161;">Offered in the product form&rsquo;s size picker</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.sizes.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save size</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
