<x-layouts.admin>
    <x-slot name="title">Edit Shipping Zone</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.settings.shipping-zones.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Shipping Zones
        </a>
    </div>

    <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0 0 1rem 0;">Edit: {{ $shippingZone->name }}</h1>

    <div style="max-width: 800px;">

        @include('admin.settings.partials.errors')
        <form action="{{ route('admin.settings.shipping-zones.update', $shippingZone) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card" style="padding: 1.25rem;">
                <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Zone details</h2>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div>
                        <label class="form-label" style="font-size: 13px; color: #303030;">Zone Name <span style="color: #d72c0d;">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $shippingZone->name) }}" class="form-input" required>
                        @error('name') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 13px; color: #303030;">Regions</label>
                        <textarea name="regions" rows="4" class="form-textarea"
                                  placeholder="One per line, e.g.&#10;Maharashtra&#10;Gujarat&#10;Karnataka">{{ old('regions', implode("\n", $shippingZone->regions ?? [])) }}</textarea>
                        <p style="font-size: 12px; color: #616161; margin-top: 0.25rem;">
                            States or postal codes this zone covers. Leave blank to cover everywhere.
                        </p>
                        @error('regions') <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" style="width: 1rem; height: 1rem; accent-color: #303030;" {{ old('is_active', $shippingZone->is_active) ? 'checked' : '' }}>
                            <span style="font-size: 13px; color: #303030;">Active</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                {{-- Submits the delete form declared after the edit form below.
                     The two must not nest: the browser hoisted the inner form's
                     _method=DELETE into the edit form, which already sent
                     _method=PUT, and PHP keeps the last value for a repeated key
                     — so clicking Save destroyed the record. --}}
                <button type="submit" form="kk-delete-shipping-zone"
                        class="pointer-coarse:inline-flex pointer-coarse:items-center pointer-coarse:min-h-9 pointer-coarse:px-2 pointer-coarse:-ml-2"
                        style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete shipping zone</button>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.settings.shipping-zones.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
        </form>

    <form id="kk-delete-shipping-zone" action="{{ route('admin.settings.shipping-zones.destroy', $shippingZone) }}" method="POST"
          onsubmit="return confirm('Delete this shipping zone?')">
        @csrf @method('DELETE')
    </form>

        <div class="card" style="margin-top: 1rem;">
            <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                <div>
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Rates</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0;">What this zone charges for delivery</p>
                </div>
                <a href="{{ route('admin.settings.shipping-zones.rates.create', $shippingZone) }}" class="btn btn-secondary" style="font-size: 13px; white-space: nowrap;">Add rate</a>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th style="text-align: right;">Rate</th>
                            <th style="text-align: right;">Est. days</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shippingZone->rates as $rate)
                            <tr>
                                <td style="font-weight: 500;">{{ $rate->name }}</td>
                                <td style="color: #616161;">{{ ucfirst($rate->type) }}</td>
                                <td style="text-align: right;">&#8377;{{ number_format($rate->rate, 2) }}</td>
                                <td style="text-align: right; color: #616161;">{{ $rate->estimated_days_min }}&ndash;{{ $rate->estimated_days_max }}</td>
                                <td>
                                    @if($rate->is_active)
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                                    @else
                                        <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #f0f0f0; color: #616161;">Inactive</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                                        <a href="{{ route('admin.settings.rates.edit', $rate) }}" class="pointer-coarse:inline-flex pointer-coarse:items-center pointer-coarse:min-h-9 pointer-coarse:-my-2 pointer-coarse:px-2" style="font-size: 13px; font-weight: 500;">Edit</a>
                                        <form action="{{ route('admin.settings.rates.destroy', $rate) }}" method="POST" onsubmit="return confirm('Delete this rate?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="pointer-coarse:inline-flex pointer-coarse:items-center pointer-coarse:min-h-9 pointer-coarse:-my-2 pointer-coarse:px-2" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 2rem 1rem; text-align: center; color: #616161;">
                                    No rates yet.
                                    <a href="{{ route('admin.settings.shipping-zones.rates.create', $shippingZone) }}" style="font-weight: 500;">Add one now</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.admin>
