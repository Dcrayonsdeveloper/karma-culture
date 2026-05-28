<x-layouts.admin>
    <x-slot name="title">Edit Flash Sale</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.flash-sales.index') }}" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">{{ $flashSale->name }}</h1>
        @if($flashSale->is_active)
            <span class="badge badge-success">Active</span>
        @else
            <span class="badge badge-warning">Inactive</span>
        @endif
    </div>

    <form action="{{ route('admin.flash-sales.update', $flashSale) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Flash Sale Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Name <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $flashSale->name) }}" required
                                   class="form-input" style="width: 100%;">
                            @error('name')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Description</label>
                            <textarea name="description" rows="3" class="form-textarea" style="width: 100%;">{{ old('description', $flashSale->description) }}</textarea>
                            @error('description')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Products in this Flash Sale -->
                @if($flashSale->products->count())
                    <div class="card" style="padding: 1.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Products ({{ $flashSale->products->count() }})</h2>
                        <div>
                            @foreach($flashSale->products as $product)
                                <div style="padding: 0.75rem 1rem; display: flex; align-items: center; justify-content: space-between;{{ !$loop->last ? ' border-bottom: 1px solid #e3e3e3;' : '' }}">
                                    <div>
                                        <p style="font-weight: 500; color: #303030; font-size: 13px; margin: 0;">{{ $product->name }}</p>
                                        <p style="font-size: 13px; color: #616161; margin: 0.125rem 0 0 0;">{{ $product->sku ?? 'N/A' }}</p>
                                    </div>
                                    <div style="font-size: 13px; text-align: right;">
                                        <span style="font-weight: 500; color: #303030;">@price($product->pivot->sale_price)</span>
                                        @if($product->pivot->stock_limit)
                                            <p style="color: #616161; margin: 0.125rem 0 0 0;">{{ $product->pivot->sold_count ?? 0 }}/{{ $product->pivot->stock_limit }} sold</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Schedule</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Starts At <span style="color: #d72c0d;">*</span></label>
                            <input type="datetime-local" name="starts_at"
                                   value="{{ old('starts_at', $flashSale->starts_at->format('Y-m-d\TH:i')) }}" required class="form-input" style="width: 100%;">
                            @error('starts_at')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Ends At <span style="color: #d72c0d;">*</span></label>
                            <input type="datetime-local" name="ends_at"
                                   value="{{ old('ends_at', $flashSale->ends_at->format('Y-m-d\TH:i')) }}" required class="form-input" style="width: 100%;">
                            @error('ends_at')
                                <p style="font-size: 12px; color: #d72c0d; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Status</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                   style="width: 1rem; height: 1rem; accent-color: #303030;"
                                   @checked(old('is_active', $flashSale->is_active))>
                            <label for="is_active" style="font-size: 13px; font-weight: 500; color: #303030;">Active</label>
                        </div>
                        <div style="padding-top: 0.5rem; border-top: 1px solid #e3e3e3; font-size: 13px;">
                            @if($flashSale->isActive())
                                <span class="badge badge-success">Currently Live</span>
                            @elseif($flashSale->isUpcoming())
                                <span class="badge badge-info">Upcoming</span>
                            @elseif($flashSale->hasEnded())
                                <span class="badge badge-error">Ended</span>
                            @else
                                <span class="badge badge-warning">Inactive</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Info</h2>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Products</span>
                            <span style="font-weight: 500; color: #303030;">{{ $flashSale->products->count() }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #616161;">Created</span>
                            <span style="font-weight: 500; color: #303030;">{{ $flashSale->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Save bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <form action="{{ route('admin.flash-sales.destroy', $flashSale) }}" method="POST"
                      onsubmit="return confirm('Delete this flash sale?')" style="display: inline;">
                    @csrf @method('DELETE')
                    <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete flash sale</button>
                </form>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>
</x-layouts.admin>
