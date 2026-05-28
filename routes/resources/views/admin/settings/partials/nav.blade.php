@php
    $tabs = [
        'general' => [
            'label' => 'General',
            'route' => 'admin.settings.general',
        ],
        'payment' => [
            'label' => 'Payment',
            'route' => 'admin.settings.payment',
        ],
        'shipping' => [
            'label' => 'Shipping',
            'route' => 'admin.settings.shipping',
        ],
        'tax' => [
            'label' => 'Tax',
            'route' => 'admin.settings.tax',
        ],
        'email' => [
            'label' => 'Email',
            'route' => 'admin.settings.email',
        ],
        'seo' => [
            'label' => 'SEO',
            'route' => 'admin.settings.seo',
        ],
        'product-card' => [
            'label' => 'Features',
            'route' => 'admin.settings.product-card',
        ],
        'integrations' => [
            'label' => 'Integrations',
            'route' => 'admin.settings.integrations',
        ],
    ];
@endphp
<div class="card" style="margin-bottom: 1.5rem; border-bottom: 1px solid #e3e3e3;">
    <div style="display: flex; overflow-x: auto; gap: 0;">
        @foreach($tabs as $key => $tab)
            <a href="{{ route($tab['route']) }}"
               style="display: inline-flex; align-items: center; padding: 0.625rem 0.75rem; font-size: 13px; font-weight: 500; text-decoration: none; white-space: nowrap; border-bottom: 2px solid {{ ($active ?? '') === $key ? '#303030' : 'transparent' }}; color: {{ ($active ?? '') === $key ? '#303030' : '#616161' }}; transition: color 0.15s, border-color 0.15s; margin-bottom: -1px;">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</div>
