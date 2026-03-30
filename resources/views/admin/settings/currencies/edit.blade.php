<x-layouts.admin>
    <x-slot name="title">Edit Currency</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.settings.currencies.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Currencies
        </a>
    </div>

    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Edit Currency: {{ $currency->name }}</h1>

    <div style="max-width: 800px;">
        <form action="{{ route('admin.settings.currencies.update', $currency) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card" style="padding: 1.25rem;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Currency details</h2>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-size: 13px; color: #303030;">Currency Code <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="code" value="{{ old('code', $currency->code) }}" class="form-input" maxlength="3" required>
                            @error('code') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; color: #303030;">Name <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $currency->name) }}" class="form-input" required>
                            @error('name') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-size: 13px; color: #303030;">Symbol <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="symbol" value="{{ old('symbol', $currency->symbol) }}" class="form-input" required>
                            @error('symbol') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; color: #303030;">Exchange Rate <span style="color: #d72c0d;">*</span></label>
                            <input type="number" name="exchange_rate" value="{{ old('exchange_rate', $currency->exchange_rate) }}" class="form-input" step="0.000001" min="0" required>
                            @error('exchange_rate') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_default" value="1" style="width: 1rem; height: 1rem; accent-color: #303030;" {{ old('is_default', $currency->is_default) ? 'checked' : '' }}>
                            <span style="font-size: 13px; color: #303030;">Default currency</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1" style="width: 1rem; height: 1rem; accent-color: #303030;" {{ old('is_active', $currency->is_active) ? 'checked' : '' }}>
                            <span style="font-size: 13px; color: #303030;">Active</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <form action="{{ route('admin.settings.currencies.destroy', $currency) }}" method="POST"
                      onsubmit="return confirm('Delete this currency?')" style="display: inline;">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete currency</button>
                </form>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.settings.currencies.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>
