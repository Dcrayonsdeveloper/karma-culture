<x-layouts.admin>
    <x-slot name="title">Add Flash Sale</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.flash-sales.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add flash sale</h1>
    </div>

    <form action="{{ route('admin.flash-sales.store') }}" method="POST">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div>
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Flash Sale Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Name <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="form-input" style="width: 100%;" placeholder="e.g. Weekend Mega Sale">
                            @error('name')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Description</label>
                            <textarea name="description" rows="3" class="form-textarea" style="width: 100%;" placeholder="Optional description...">{{ old('description') }}</textarea>
                            @error('description')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Schedule</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Starts At <span style="color: #d72c0d;">*</span></label>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required class="form-input" style="width: 100%;">
                            @error('starts_at')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Ends At <span style="color: #d72c0d;">*</span></label>
                            <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" required class="form-input" style="width: 100%;">
                            @error('ends_at')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

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
                <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save flash sale</button>
            </div>
    </form>
</x-layouts.admin>
