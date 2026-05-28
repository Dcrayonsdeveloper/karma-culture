<x-layouts.admin>
    <x-slot name="title">Features Settings</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Settings</h1>
        </div>
    </x-slot>

    @include('admin.settings.partials.nav', ['active' => 'product-card'])

    <form action="{{ route('admin.settings.product-card.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div style="max-width: 42rem;">
            <div class="card">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Product Card Hover Actions</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Control which actions appear when customers hover over product cards on the storefront.</p>
                </div>
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <!-- Quick View -->
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.875rem; border-radius: 0.5rem; border: 1px solid #e3e3e3;" x-data="{ enabled: {{ $settings['product_card_quick_view'] ? 'true' : 'false' }} }">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 2.5rem; height: 2.5rem; background: #d4edfc; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg style="width: 1.25rem; height: 1.25rem; color: #0064a4;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">Quick View</p>
                                <p style="font-size: 12px; color: #616161; margin: 0;">Show an eye icon to preview product details in a popup without leaving the page.</p>
                            </div>
                        </div>
                        <label class="toggle-switch" style="flex-shrink: 0; margin-left: 1rem;">
                            <input type="hidden" name="product_card_quick_view" value="0">
                            <input type="checkbox" name="product_card_quick_view" value="1" x-model="enabled">
                            <div class="toggle-track"></div>
                        </label>
                    </div>

                    <!-- Add to Cart -->
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.875rem; border-radius: 0.5rem; border: 1px solid #e3e3e3;" x-data="{ enabled: {{ $settings['product_card_add_to_cart'] ? 'true' : 'false' }} }">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 2.5rem; height: 2.5rem; background: #cdfee1; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg style="width: 1.25rem; height: 1.25rem; color: #1a7a2e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <div>
                                <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">Add to Cart</p>
                                <p style="font-size: 12px; color: #616161; margin: 0;">Show the Add to Bag button on hover for quick cart additions.</p>
                            </div>
                        </div>
                        <label class="toggle-switch" style="flex-shrink: 0; margin-left: 1rem;">
                            <input type="hidden" name="product_card_add_to_cart" value="0">
                            <input type="checkbox" name="product_card_add_to_cart" value="1" x-model="enabled">
                            <div class="toggle-track"></div>
                        </label>
                    </div>

                    <!-- Wishlist -->
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.875rem; border-radius: 0.5rem; border: 1px solid #e3e3e3;" x-data="{ enabled: {{ $settings['product_card_wishlist'] ? 'true' : 'false' }} }">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 2.5rem; height: 2.5rem; background: #ffe0db; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg style="width: 1.25rem; height: 1.25rem; color: #d72c0d;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">Wishlist</p>
                                <p style="font-size: 12px; color: #616161; margin: 0;">Show a heart icon on hover so customers can save products to their wishlist.</p>
                            </div>
                        </div>
                        <label class="toggle-switch" style="flex-shrink: 0; margin-left: 1rem;">
                            <input type="hidden" name="product_card_wishlist" value="0">
                            <input type="checkbox" name="product_card_wishlist" value="1" x-model="enabled">
                            <div class="toggle-track"></div>
                        </label>
                    </div>
                </div>

                <div style="padding: 0.75rem 1rem; background: #f6f6f7; border-top: 1px solid #e3e3e3; display: flex; justify-content: flex-end; border-radius: 0 0 0.75rem 0.75rem;">
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Changes</button>
                </div>
            </div>

            <!-- Customer Features -->
            <div class="card" style="margin-top: 1rem;">
                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3;">
                    <h2 style="font-size: 13px; font-weight: 600; color: #303030; margin: 0;">Customer Features</h2>
                    <p style="font-size: 12px; color: #616161; margin: 0.125rem 0 0 0;">Enable or disable features available to customers.</p>
                </div>
                <div style="padding: 1rem;">
                    <!-- Support Tickets -->
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.875rem; border-radius: 0.5rem; border: 1px solid #e3e3e3;" x-data="{ enabled: {{ $settings['support_tickets_enabled'] ? 'true' : 'false' }} }">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 2.5rem; height: 2.5rem; background: #f0e6ff; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg style="width: 1.25rem; height: 1.25rem; color: #7c3aed;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                            </div>
                            <div>
                                <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">Support Tickets</p>
                                <p style="font-size: 12px; color: #616161; margin: 0;">Allow customers to raise support tickets from their account dashboard.</p>
                            </div>
                        </div>
                        <label class="toggle-switch" style="flex-shrink: 0; margin-left: 1rem;">
                            <input type="hidden" name="support_tickets_enabled" value="0">
                            <input type="checkbox" name="support_tickets_enabled" value="1" x-model="enabled">
                            <div class="toggle-track"></div>
                        </label>
                    </div>
                </div>

                <div style="padding: 0.75rem 1rem; background: #f6f6f7; border-top: 1px solid #e3e3e3; display: flex; justify-content: flex-end; border-radius: 0 0 0.75rem 0.75rem;">
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save Changes</button>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
