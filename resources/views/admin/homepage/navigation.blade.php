<x-layouts.admin>
    <x-slot name="title">Navigation Menus</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Navigation Menus</h1>
            <a href="{{ route('admin.homepage.index') }}" class="btn btn-secondary" style="font-size: 13px;">Back to Homepage</a>
        </div>
    </x-slot>

    <x-admin.form-errors title="The menu item was not saved" />

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.homepage.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Homepage
        </a>
    </div>

    @php
        // One list, used by the Add form and by every row's inline editor, so the
        // two can never drift apart. footer_col4 is gone: nothing renders it.
        $kkNavLocations = [
            'header' => 'Header Navigation',
            'footer_col1' => 'Footer - Quick Links',
            'footer_col2' => 'Footer - Customer Service',
            'footer_col3' => 'Footer - Policies',
        ];
    @endphp

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
                        {{-- for/id pairs matter beyond accessibility here: the inline validator
                             names the field from its own <label>, so an unlabelled input reports
                             "This field is required" instead of "Label is required". --}}
                        <div>
                            <label for="nav-location" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Location <span style="color: #d72c0d;">*</span></label>
                            <select name="location" id="nav-location" class="form-select" required>
                                @foreach($kkNavLocations as $kkValue => $kkLabel)
                                    <option value="{{ $kkValue }}" {{ old('location') === $kkValue ? 'selected' : '' }}>{{ $kkLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="nav-label" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">Label <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="label" id="nav-label" value="{{ old('label') }}" required maxlength="255" class="form-input" placeholder="Menu item text">
                        </div>
                        <div>
                            <label for="nav-url" class="form-label" style="font-size: 13px; font-weight: 500; color: #303030;">URL <span style="color: #d72c0d;">*</span></label>
                            {{-- A relative path, a full http(s) address, mailto:, tel: or a #anchor.
                                 A menu item is rendered as an href, so `javascript:` here would be
                                 stored XSS on every page the menu appears on. --}}
                            <input type="text" name="url" id="nav-url" value="{{ old('url') }}" required maxlength="255"
                                   pattern="(https?://|mailto:|tel:)\S+|/(?!/)\S*|#\S*"
                                   title="Enter a path such as /about, or a full https:// address."
                                   class="form-input" placeholder="/about or https://...">
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
                        @include('admin.homepage.partials.nav-item', ['item' => $item, 'locations' => $kkNavLocations])
                    @empty
                        <p style="font-size: 13px; color: #616161; margin: 0;">No header menu items</p>
                    @endforelse
                </div>
            </div>

            {{-- The controller passes $footerCol1..3; the loop used to rebuild those
                 names from the location string and read $$loc, which resolved to
                 $footer_col1 and threw. Pair each column with its items here. --}}
            @foreach([
                ['label' => 'Quick Links', 'items' => $footerCol1],
                ['label' => 'Customer Service', 'items' => $footerCol2],
                ['label' => 'Policies', 'items' => $footerCol3],
            ] as $column)
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Footer: {{ $column['label'] }}</h2>
                    </div>
                    <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        @forelse($column['items'] as $item)
                            @include('admin.homepage.partials.nav-item', ['item' => $item, 'locations' => $kkNavLocations])
                        @empty
                            <p style="font-size: 13px; color: #616161; margin: 0;">No items</p>
                        @endforelse
                    </div>
                </div>
            @endforeach

            {{-- Items saved against a location no page renders - footer_col4 used to
                 be selectable through the API. They were invisible here, so they
                 could not be moved or deleted. Edit one to file it somewhere real. --}}
            @if($orphanMenus->isNotEmpty())
                <div class="card">
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Not shown anywhere</h2>
                        <p style="font-size: 12px; color: #616161; margin: 0.25rem 0 0 0;">These are filed under a location the storefront does not render. Edit each one to move it into a real column, or remove it.</p>
                    </div>
                    <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        @foreach($orphanMenus as $item)
                            @include('admin.homepage.partials.nav-item', ['item' => $item, 'locations' => $kkNavLocations])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
