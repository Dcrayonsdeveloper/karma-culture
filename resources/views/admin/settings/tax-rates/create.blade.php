<x-layouts.admin>
    <x-slot name="title">Add Tax Rate</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.settings.tax-rates.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add tax rate</h1>
    </div>

    <form action="{{ route('admin.settings.tax-rates.store') }}" method="POST">
        @csrf

        <div class="card" style="max-width: 640px; margin-top: 1rem; padding: 1.25rem;">
            <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Tax Rate Details</h2>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label class="form-label">Name <span style="color: #d72c0d;">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="form-input" placeholder="e.g. GST 18%">
                    @error('name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">State</label>
                    <input type="text" name="state" value="{{ old('state') }}"
                           class="form-input" placeholder="e.g. Maharashtra">
                    @error('state')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                    <div>
                        <label class="form-label">CGST Rate (%) <span style="color: #d72c0d;">*</span></label>
                        <input type="number" name="cgst_rate" value="{{ old('cgst_rate', '0') }}" step="0.01" min="0" max="100" required
                               class="form-input">
                        @error('cgst_rate')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label">SGST Rate (%) <span style="color: #d72c0d;">*</span></label>
                        <input type="number" name="sgst_rate" value="{{ old('sgst_rate', '0') }}" step="0.01" min="0" max="100" required
                               class="form-input">
                        @error('sgst_rate')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label">IGST Rate (%) <span style="color: #d72c0d;">*</span></label>
                        <input type="number" name="igst_rate" value="{{ old('igst_rate', '0') }}" step="0.01" min="0" max="100" required
                               class="form-input">
                        @error('igst_rate')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           style="accent-color: #005bd3;"
                           @checked(old('is_active', true))>
                    <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.settings.tax-rates.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save tax rate</button>
            </div>
    </form>
</x-layouts.admin>
