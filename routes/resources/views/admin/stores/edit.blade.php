<x-layouts.admin>
    <x-slot name="title">Edit Store</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.stores.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $store->name }}</h1>
        @if($store->is_active)
            <span class="badge badge-success">Active</span>
        @else
            <span class="badge badge-warning">Inactive</span>
        @endif
    </div>

    <form action="{{ route('admin.stores.update', $store) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Store Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Name <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $store->name) }}" required
                                       class="form-input" style="width: 100%;">
                                @error('name')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Code <span style="color: #d72c0d;">*</span></label>
                                <input type="text" name="code" value="{{ old('code', $store->code) }}" required
                                       class="form-input" style="width: 100%;">
                                @error('code')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Address</label>
                            <input type="text" name="address" value="{{ old('address', $store->address) }}" class="form-input" style="width: 100%;">
                            @error('address')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Contact Information</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', $store->phone) }}" class="form-input" style="width: 100%;">
                                @error('phone')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Email</label>
                                <input type="email" name="email" value="{{ old('email', $store->email) }}" class="form-input" style="width: 100%;">
                                @error('email')
                                    <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                @if($store->registers->count())
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">POS Registers ({{ $store->registers->count() }})</h2>
                        <div>
                            @foreach($store->registers as $register)
                                <div style="padding: 0.75rem 0; display: flex; align-items: center; justify-content: space-between;{{ !$loop->last ? ' border-bottom: 1px solid #e3e3e3;' : '' }}">
                                    <div>
                                        <p style="font-weight: 500; color: #303030; font-size: 13px; margin: 0;">{{ $register->name }}</p>
                                        <p style="font-size: 13px; color: #616161; margin: 0;">{{ $register->code ?? '' }}</p>
                                    </div>
                                    <span class="badge {{ $register->is_active ? 'badge-success' : 'badge-warning' }}">
                                        {{ $register->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Status</h2>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               style="width: 1rem; height: 1rem; accent-color: #303030;"
                               @checked(old('is_active', $store->is_active))>
                        <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Info</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Code</span>
                            <span style="font-weight: 500; font-family: monospace; color: #303030;">{{ $store->code }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Registers</span>
                            <span style="font-weight: 500; color: #303030;">{{ $store->registers->count() }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Created</span>
                            <span style="font-weight: 500; color: #303030;">{{ $store->created_at->format('M d, Y') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Updated</span>
                            <span style="font-weight: 500; color: #303030;">{{ $store->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <form action="{{ route('admin.stores.destroy', $store) }}" method="POST"
                      onsubmit="return confirm('Delete this store?')" style="display: inline;">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete store</button>
                </form>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.stores.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>
</x-layouts.admin>
