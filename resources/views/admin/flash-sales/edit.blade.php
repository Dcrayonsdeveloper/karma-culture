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
                @php
                    $kkRows = $flashSale->products->map(fn ($p) => [
                        'product_id' => $p->id,
                        'sale_price' => (string) $p->pivot->sale_price,
                        'stock_limit' => $p->pivot->stock_limit,
                        'sold_count' => (int) ($p->pivot->sold_count ?? 0),
                    ])->values();
                @endphp
                <div class="card" style="padding: 1.25rem;" x-data="kkFlashProducts()">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem;">
                        <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Products in this sale</h2>
                        <button type="button" @click="add()" class="btn btn-secondary" style="font-size: 12px; padding: 4px 10px;">+ Add product</button>
                    </div>
                    <p style="font-size: 12px; color: #616161; margin: 0 0 1rem 0;">
                        Pick a product and the price it sells at during the sale. Leave the limit blank for unlimited.
                    </p>

                    <p style="font-size: 13px; color: #616161;" x-show="rows.length === 0" x-cloak>
                        No products yet &mdash; the sale will show a countdown but discount nothing.
                    </p>

                    <div x-show="rows.length > 0" x-cloak style="display: flex; flex-direction: column; gap: 8px;">
                        <template x-for="(r, i) in rows" :key="r.uid">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <select x-bind:name="'products[' + i + '][product_id]'" x-model="r.product_id"
                                        class="form-select" style="flex: 1 1 200px; font-size: 13px;">
                                    <option value="">Choose a product…</option>
                                    @foreach($allProducts as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} — @price($p->price)</option>
                                    @endforeach
                                </select>
                                <input type="number" step="0.01" min="0" x-bind:name="'products[' + i + '][sale_price]'" x-model="r.sale_price"
                                       placeholder="Sale price" class="form-input" style="width: 120px; font-size: 13px;">
                                <input type="number" min="0" x-bind:name="'products[' + i + '][stock_limit]'" x-model="r.stock_limit"
                                       placeholder="Limit" class="form-input" style="width: 90px; font-size: 13px;">
                                <span style="font-size: 12px; color: #616161; width: 62px;" x-show="r.sold_count > 0" x-cloak>
                                    <span x-text="r.sold_count"></span> sold
                                </span>
                                <button type="button" @click="rows.splice(i, 1)" title="Remove"
                                        style="color:#d72c0d;background:none;border:0;cursor:pointer;font-size:16px;line-height:1;">&times;</button>
                            </div>
                        </template>
                    </div>
                </div>
                <script>
                    function kkFlashProducts() {
                        return {
                            seq: 0,
                            rows: [],
                            init() {
                                this.rows = (@json($kkRows)).map(r => ({ ...r, uid: ++this.seq }));
                            },
                            add() {
                                this.rows.push({ uid: ++this.seq, product_id: '', sale_price: '', stock_limit: '', sold_count: 0 });
                            },
                        };
                    }
                </script>
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
                {{-- The delete button submits a form declared outside this one.
                     Nesting the two meant the browser closed the edit form at the
                     inner <form> tag, so Save belonged to the delete form and
                     saving destroyed the sale. --}}
                <button type="submit" form="kk-delete-flash-sale"
                        style="font-size: 13px; font-weight: 500; color: #d72c0d; background: none; border: none; cursor: pointer;">Delete flash sale</button>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save</button>
                </div>
            </div>
    </form>

    <form id="kk-delete-flash-sale" action="{{ route('admin.flash-sales.destroy', $flashSale) }}" method="POST"
          onsubmit="return confirm('Delete this flash sale?')">
        @csrf @method('DELETE')
    </form>
</x-layouts.admin>
