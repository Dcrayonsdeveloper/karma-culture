<x-layouts.admin>
    <x-slot name="title">Edit Attribute</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.attributes.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $attribute->name }}</h1>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Attribute Details -->
            <form action="{{ route('admin.attributes.update', $attribute) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Attribute Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Name <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $attribute->name) }}" required
                                   class="form-input" style="width: 100%;">
                            @error('name')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Type <span style="color: #d72c0d;">*</span></label>
                            <select name="type" class="form-select" style="width: 100%;" required>
                                <option value="select" @selected(old('type', $attribute->type) === 'select')>Select (Dropdown)</option>
                                <option value="color" @selected(old('type', $attribute->type) === 'color')>Color (Swatch)</option>
                                <option value="text" @selected(old('type', $attribute->type) === 'text')>Text (Free Input)</option>
                            </select>
                            @error('type')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="display: flex; align-items: center; gap: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="hidden" name="is_filterable" value="0">
                                <input type="checkbox" name="is_filterable" value="1" id="is_filterable"
                                       style="width: 1rem; height: 1rem; accent-color: #303030;"
                                       @checked(old('is_filterable', $attribute->is_filterable))>
                                <label for="is_filterable" style="font-size: 13px; font-weight: 500; color: #303030;">Filterable</label>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="hidden" name="is_visible" value="0">
                                <input type="checkbox" name="is_visible" value="1" id="is_visible"
                                       style="width: 1rem; height: 1rem; accent-color: #303030;"
                                       @checked(old('is_visible', $attribute->is_visible))>
                                <label for="is_visible" style="font-size: 13px; font-weight: 500; color: #303030;">Visible on product page</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save bar -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                    <form action="{{ route('admin.attributes.destroy', $attribute) }}" method="POST"
                          onsubmit="return confirm('Delete this attribute?')" style="display: inline;">
                        @csrf @method('DELETE')
                        <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete attribute</button>
                    </form>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                        <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                    </div>
                </div>
            </form>

            <!-- Attribute Values -->
            <div class="card" style="padding: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Values</h2>
                    <a href="{{ route('admin.attributes.values.create', $attribute) }}" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center;">
                        <svg style="width: 1rem; height: 1rem; margin-right: 0.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Value
                    </a>
                </div>
                @if($attribute->values->count())
                    <div style="margin: -1.25rem; margin-top: 0;">
                        @foreach($attribute->values as $value)
                            <div style="padding: 0.75rem 1.25rem; display: flex; align-items: center; justify-content: space-between;{{ !$loop->last ? ' border-bottom: 1px solid #e3e3e3;' : '' }}">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    @if($attribute->type === 'color' && $value->color_code)
                                        <div style="width: 1.5rem; height: 1.5rem; border-radius: 50%; border: 1px solid #e3e3e3; background-color: {{ $value->color_code }};"></div>
                                    @endif
                                    <span style="font-weight: 500; color: #303030; font-size: 13px;">{{ $value->value }}</span>
                                    @if($value->color_code)
                                        <span style="font-size: 13px; color: #616161;">{{ $value->color_code }}</span>
                                    @endif
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <a href="{{ route('admin.values.edit', $value) }}" style="color: #005bd3; font-size: 13px; font-weight: 500; text-decoration: none;">Edit</a>
                                    <form action="{{ route('admin.values.destroy', $value) }}" method="POST" onsubmit="return confirm('Delete this value?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color: #d72c0d; font-size: 13px; font-weight: 500; background: none; border: none; cursor: pointer;">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="padding: 2rem; text-align: center; color: #616161; font-size: 13px;">
                        No values added yet.
                        <a href="{{ route('admin.attributes.values.create', $attribute) }}" style="color: #005bd3; font-weight: 500; margin-left: 0.25rem; text-decoration: none;">Add one now</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Info -->
        <div>
            <div class="card" style="padding: 1.25rem;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Info</h2>
                <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #616161;">Type</span>
                        <span style="font-weight: 500; text-transform: capitalize; color: #303030;">{{ $attribute->type }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #616161;">Values</span>
                        <span style="font-weight: 500; color: #303030;">{{ $attribute->values->count() }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #616161;">Created</span>
                        <span style="font-weight: 500; color: #303030;">{{ $attribute->created_at->format('M d, Y') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #616161;">Updated</span>
                        <span style="font-weight: 500; color: #303030;">{{ $attribute->updated_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
