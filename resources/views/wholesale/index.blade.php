<x-layouts.app>
    <x-slot name="title">Wholesale - {{ config('app.name') }}</x-slot>

    <!-- Hero Banner -->
    <div style="background: linear-gradient(135deg, #6F9CA2 0%, #4A7A80 100%); color: #fff; padding: 4rem 1rem; text-align: center;">
        <div class="container mx-auto px-4" style="max-width: 640px;">
            <h1 style="font-size: 2rem; font-weight: 700; margin: 0 0 0.75rem;">Wholesale Program</h1>
            <p style="font-size: 1rem; opacity: 0.9; margin: 0 0 1.5rem; line-height: 1.6;">
                Partner with {{ config('app.name') }} for bulk pricing on quality kids clothing.
                Minimum order quantities apply.
            </p>
            <a href="#wholesale-form"
               style="display: inline-block; background: #fff; color: #4A7A80; font-weight: 600; font-size: 14px; padding: 0.75rem 2rem; border-radius: 9999px; text-decoration: none;">
                Enquire Now
            </a>
        </div>
    </div>

    <div class="container mx-auto px-4 py-12" style="max-width: 900px;">

        <!-- Benefits -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            @php
                $benefits = [
                    ['icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'Competitive Pricing', 'desc' => 'Up to 40% off retail prices for bulk orders'],
                    ['icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'title' => 'Fast Fulfillment', 'desc' => 'Dedicated order processing for wholesale partners'],
                    ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'title' => 'Quality Assured', 'desc' => '100% Made in India, quality tested products'],
                    ['icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', 'title' => 'Dedicated Support', 'desc' => 'Account manager assigned to your business'],
                ];
            @endphp
            @foreach($benefits as $benefit)
                <div style="background: #fff; border: 1px solid #e5e5e5; border-radius: 0.75rem; padding: 1.5rem; text-align: center;">
                    <div style="width: 48px; height: 48px; background: #f0f7f8; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem;">
                        <svg style="width: 24px; height: 24px; color: #6F9CA2;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $benefit['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 style="font-size: 14px; font-weight: 600; color: #0F1111; margin: 0 0 0.375rem;">{{ $benefit['title'] }}</h3>
                    <p style="font-size: 13px; color: #565959; margin: 0; line-height: 1.5;">{{ $benefit['desc'] }}</p>
                </div>
            @endforeach
        </div>

        <!-- Enquiry Form -->
        <div id="wholesale-form" style="background: #fff; border: 1px solid #e5e5e5; border-radius: 0.75rem; padding: 2rem; max-width: 600px; margin: 0 auto;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: #0F1111; margin: 0 0 0.375rem;">Wholesale Enquiry</h2>
            <p style="font-size: 13px; color: #565959; margin: 0 0 1.5rem;">Fill in your details and we'll get back to you within 24 hours.</p>

            @if(session('success'))
                <div style="background: #e4f5e9; border: 1px solid #c6e8d0; border-radius: 0.5rem; padding: 0.875rem; margin-bottom: 1rem;">
                    <p style="font-size: 13px; color: #1a7431; margin: 0;">{{ session('success') }}</p>
                </div>
            @endif

            <form action="{{ route('contact.send') }}" method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
                @csrf
                <input type="hidden" name="subject" value="Wholesale Enquiry">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #0F1111; margin-bottom: 0.375rem;">Business Name *</label>
                        <input type="text" name="business_name" required placeholder="Your business name"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d5d9d9; border-radius: 0.5rem; font-size: 13px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #0F1111; margin-bottom: 0.375rem;">Contact Name *</label>
                        <input type="text" name="name" required placeholder="Your name"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d5d9d9; border-radius: 0.5rem; font-size: 13px; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #0F1111; margin-bottom: 0.375rem;">Email *</label>
                        <input type="email" name="email" required placeholder="your@email.com"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d5d9d9; border-radius: 0.5rem; font-size: 13px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 500; color: #0F1111; margin-bottom: 0.375rem;">Phone</label>
                        <input type="tel" name="phone" placeholder="+91 XXXXX XXXXX"
                               style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d5d9d9; border-radius: 0.5rem; font-size: 13px; box-sizing: border-box;">
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #0F1111; margin-bottom: 0.375rem;">Message *</label>
                    <textarea name="message" required rows="4" placeholder="Tell us about your business, estimated order quantities, and categories of interest..."
                              style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d5d9d9; border-radius: 0.5rem; font-size: 13px; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <button type="submit"
                        style="padding: 0.75rem; background: #6F9CA2; color: #fff; border: none; border-radius: 0.5rem; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.15s;"
                        onmouseenter="this.style.background='#4A7A80'" onmouseleave="this.style.background='#6F9CA2'">
                    Submit Enquiry
                </button>
            </form>
        </div>

    </div>
</x-layouts.app>
