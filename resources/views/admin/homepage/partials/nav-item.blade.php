{{--
    One navigation menu row: read view, plus the inline edit form.

    updateNavItem and its route existed from the start but no page ever posted to
    them, so fixing a typo in a menu label meant deleting the item and adding it
    back. Hidden rows were not listed at all, which made them unreachable rather
    than merely hidden.

    @param $item      NavigationMenu
    @param $locations array<string, string>  value => human label
--}}
<div style="padding: 0.5rem 0.75rem; background: #f6f6f7; border-radius: 0.375rem; {{ $item->is_active ? '' : 'opacity: 0.65;' }}" x-data="{ editing: false }">
    <div x-show="!editing" style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
        <div style="min-width: 0;">
            <span style="font-size: 13px; font-weight: 500; color: #303030;">{{ $item->label }}</span>
            @unless($item->is_active)
                <span style="display: inline-block; margin-left: 0.375rem; padding: 0.0625rem 0.375rem; border-radius: 1rem; font-size: 10px; font-weight: 500; background: #ebebeb; color: #616161;">Hidden</span>
            @endunless
            <span style="font-size: 12px; color: #616161; margin-left: 0.5rem; word-break: break-all;">{{ $item->url }}</span>
        </div>
        <div style="display: flex; align-items: center; gap: 0.25rem; flex-shrink: 0;">
            <button type="button" class="btn btn-secondary" style="font-size: 12px; padding: 0.25rem 0.5rem;" @click="editing = true">Edit</button>
            <form action="{{ route('admin.homepage.navigation.toggle', $item) }}" method="POST" style="display: inline;">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 0.25rem 0.5rem;">{{ $item->is_active ? 'Hide' : 'Show' }}</button>
            </form>
            <form action="{{ route('admin.homepage.navigation.destroy', $item) }}" method="POST" onsubmit="return confirm('Remove this menu item?')" style="display: inline;">
                @csrf
                @method('DELETE')
                <button style="font-size: 12px; color: #d72c0d; background: none; border: none; cursor: pointer; padding: 0.25rem 0.5rem;">Remove</button>
            </form>
        </div>
    </div>

    <form x-show="editing" x-cloak action="{{ route('admin.homepage.navigation.update', $item) }}" method="POST">
        @csrf
        @method('PUT')
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <div>
                <label for="nav-{{ $item->id }}-label" class="form-label" style="font-size: 12px; font-weight: 500; color: #303030;">Label</label>
                <input type="text" name="label" id="nav-{{ $item->id }}-label" value="{{ $item->label }}" required maxlength="255" class="form-input" style="font-size: 13px;">
            </div>
            <div>
                <label for="nav-{{ $item->id }}-url" class="form-label" style="font-size: 12px; font-weight: 500; color: #303030;">URL</label>
                <input type="text" name="url" id="nav-{{ $item->id }}-url" value="{{ $item->url }}" required maxlength="255"
                       pattern="(https?://|mailto:|tel:)\S+|/(?!/)\S*|#\S*"
                       title="Enter a path such as /about, or a full https:// address."
                       class="form-input" style="font-size: 13px;">
            </div>
            <div>
                <label for="nav-{{ $item->id }}-location" class="form-label" style="font-size: 12px; font-weight: 500; color: #303030;">Shown in</label>
                <select name="location" id="nav-{{ $item->id }}-location" class="form-select" style="font-size: 13px;" required>
                    @foreach($locations as $value => $label)
                        <option value="{{ $value }}" {{ $item->location === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" style="font-size: 12px;" @click="editing = false">Cancel</button>
                <button type="submit" class="btn btn-primary" style="font-size: 12px;">Save</button>
            </div>
        </div>
    </form>
</div>
