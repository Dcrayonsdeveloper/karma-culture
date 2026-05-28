@component('mail::message')
# Low Stock Alert

The following **{{ $products->count() }}** product(s) are running low on stock and need your attention:

@component('mail::table')
| Product | SKU | Stock | Threshold |
|:--------|:----|------:|----------:|
@foreach($products as $product)
| {{ $product->name }} | {{ $product->sku ?? 'N/A' }} | **{{ $product->stock_quantity }}** | {{ $product->low_stock_threshold }} |
@endforeach
@endcomponent

@php
    $outOfStock = $products->where('stock_quantity', 0)->count();
@endphp

@if($outOfStock > 0)
> **{{ $outOfStock }} product(s) are completely out of stock!**
@endif

@component('mail::button', ['url' => url('/admin/inventory/low-stock')])
View Low Stock Products
@endcomponent

Please restock these items as soon as possible to avoid missed sales.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
