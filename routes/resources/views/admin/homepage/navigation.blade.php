<x-layouts.admin>
    <x-slot name="title">Navigation Menus</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Navigation Menus</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <!-- Add Menu Item -->
        <div class="card">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Add Menu Item</h2>
            </div>
            <div style="padding: 1rem;">
                <form action="{{ route('admin.homepage.navigation.store') }}" method="POST">
                    @csrf
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Location <span style="color: #d72c0d;">*</span></label>
                            <select name="location" class="form-select" required>
                                <option value="header">Header Navigation</option>
                                <option value="footer_col1">Footer - Quick Links</option>
                                <option value="footer_col2">Footer - Customer Service</option>
                                <option value="footer_col3">Footer - Policies</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Label <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="label" required class="form-input" placeholder="Menu item text">
                        </div>
                        <div>
                            <label class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">URL <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="url" required class="form-input" placeholder="/about or https://...">
                        </div>
                        <button type="submit" class="btn btn-primary" style="font-size: 13px; width: 100%;">Add Menu Item</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Menus -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <!-- Header Menu -->
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Header Navigation</h2>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                    @forelse($headerMenus as $item)
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.75rem; background: #f6f6f7; border-radius: 0.375rem;">
                            <div>
                                <span style="font-size: 13px; font-weight: 500; color: #303030;">{{ $item->label }}</span>
                                <span style="font-size: 12px; color: #616161; margin-left: 0.5rem;">{{ $item->url }}</span>
                            </div>
                            <form action="{{ route('admin.homepage.navigation.destroy', $item) }}" method="POST" onsubmit="return confirm('Remove?')">
                                @csrf
                                @method('DELETE')
                                <button style="font-size: 12px; color: #d72c0d; background: none; border: none; cursor: pointer; padding: 0.25rem 0.5rem;">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p style="font-size: 13px; color: #616161; margin: 0;">No header menu items</p>
                    @endforelse
                </div>
            </div>

            @foreach(['footer_col1' => 'Quick Links', 'footer_col2' => 'Customer Service', 'footer_col3' => 'Policies'] as $loc => $label)
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Footer: {{ $label }}</h2>
                    </div>
                    <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        @php $items = ${str_replace('footer_', 'footerCol', ucfirst(str_replace('footer_col', 'footerCol', $loc)))} ?? collect(); @endphp
                        @forelse($$loc as $item)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.75rem; background: #f6f6f7; border-radius: 0.375rem;">
                                <div>
                                    <span style="font-size: 13px; font-weight: 500; color: #303030;">{{ $item->label }}</span>
                                    <span style="font-size: 12px; color: #616161; margin-left: 0.5rem;">{{ $item->url }}</span>
                                </div>
                                <form action="{{ route('admin.homepage.navigation.destroy', $item) }}" method="POST" onsubmit="return confirm('Remove?')">
                                    @csrf
                                    @method('DELETE')
                                    <button style="font-size: 12px; color: #d72c0d; background: none; border: none; cursor: pointer; padding: 0.25rem 0.5rem;">Remove</button>
                                </form>
                            </div>
                        @empty
                            <p style="font-size: 13px; color: #616161; margin: 0;">No items</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
