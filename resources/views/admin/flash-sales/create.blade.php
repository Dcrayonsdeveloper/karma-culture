<x-layouts.admin>
    <x-slot name="title">Add Flash Sale</x-slot>

    <!-- Top bar -->
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.flash-sales.index') }}" class="btn-icon" style="flex-shrink: 0; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add flash sale</h1>
    </div>

    <form action="{{ route('admin.flash-sales.store') }}" method="POST">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="card" style="padding: 1.25rem;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin-bottom: 1rem;">Flash Sale Details</h2>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            {{-- for/id pairs matter beyond accessibility here: the inline validator
                                 names the field from its own <label>, so an unlabelled input reports
                                 "This field is required" instead of "Name is required". --}}
                            <label for="fs-name" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Name <span style="color: #d72c0d;">*</span></label>
                            <input type="text" name="name" id="fs-name" value="{{ old('name') }}" required
                                   minlength="2" maxlength="255"
                                   class="form-input" style="width: 100%;" placeholder="e.g. Weekend Mega Sale">
                            <x-field-error field="name" />
                        </div>

                        <div>
                            <label for="fs-description" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Description</label>
                            <textarea name="description" id="fs-description" rows="3" maxlength="1000" class="form-textarea" style="width: 100%;" placeholder="Optional description...">{{ old('description') }}</textarea>
                            <x-field-error field="description" />
                        </div>
                    </div>
                </div>

                <!-- Products in this Flash Sale -->
                @php
                    $kkRows = collect();
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
                        No products yet - the sale will show a countdown but discount nothing.
                    </p>

                    <div x-show="rows.length > 0" x-cloak style="display: flex; flex-direction: column; gap: 8px;">
                        <template x-for="(r, i) in rows" :key="r.uid">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <select x-bind:name="'products[' + i + '][product_id]'" x-model="r.product_id"
                                        aria-label="Product" class="form-select" style="flex: 1 1 200px; font-size: 13px;">
                                    <option value="">Choose a product…</option>
                                    @foreach($allProducts as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} - @price($p->price)</option>
                                    @endforeach
                                </select>
                                <input type="number" step="0.01" min="0" max="9999999.99" inputmode="decimal"
                                       x-bind:name="'products[' + i + '][sale_price]'" x-model="r.sale_price"
                                       aria-label="Sale price" title="Enter an amount, up to two decimal places."
                                       placeholder="Sale price" class="form-input" style="width: 120px; font-size: 13px;">
                                <input type="number" min="0" max="1000000" step="1" inputmode="numeric"
                                       x-bind:name="'products[' + i + '][stock_limit]'" x-model="r.stock_limit"
                                       aria-label="Stock limit" title="Enter a whole number, or leave blank for unlimited."
                                       placeholder="Limit" class="form-input" style="width: 90px; font-size: 13px;">
                                <span style="font-size: 12px; color: #616161; width: 62px;" x-show="r.sold_count > 0" x-cloak>
                                    <span x-text="r.sold_count"></span> sold
                                </span>
                                <button type="button" @click="rows.splice(i, 1)" title="Remove"
                                        class="btn-icon" style="color:#d72c0d;cursor:pointer;font-size:16px;line-height:1;">&times;</button>
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
                    @php
                        // The earliest moment a new schedule may be set to. The
                        // picker greys out everything before it; the rule behind
                        // V::scheduleStart() is what enforces it once posted.
                        $scheduleFloor = now()->format('Y-m-d\TH:i');
                    @endphp
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <label for="fs-starts-at" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Starts At <span style="color: #d72c0d;">*</span></label>
                            <input type="datetime-local" name="starts_at" id="fs-starts-at" value="{{ old('starts_at') }}" required
                                   min="{{ $scheduleFloor }}" data-schedule-start class="form-input" style="width: 100%;">
                            <x-field-error field="starts_at" />
                        </div>
                        <div>
                            <label for="fs-ends-at" class="form-label" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.25rem;">Ends At <span style="color: #d72c0d;">*</span></label>
                            <input type="datetime-local" name="ends_at" id="fs-ends-at" value="{{ old('ends_at') }}" required
                                   min="{{ $scheduleFloor }}" data-schedule-end="fs-starts-at" class="form-input" style="width: 100%;">
                            <x-field-error field="ends_at" />
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
