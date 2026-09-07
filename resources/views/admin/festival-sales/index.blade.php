<x-layouts.admin>
    <x-slot name="title">Festival Sales</x-slot>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; gap: 1rem; flex-wrap: wrap;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Festival Sales</h1>
            <p style="font-size: 13px; color: #616161; margin: 0.25rem 0 0 0;">
                One percentage off a hand-picked set of products, across the whole shop
            </p>
        </div>
        <a href="{{ route('admin.festival-sales.create') }}" class="btn btn-primary" style="font-size: 13px; text-decoration: none;">
            <svg style="width: 16px; height: 16px; margin-right: 0.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Create festival sale
        </a>
    </div>

    <div class="card" style="overflow: hidden;">
        @if($festivalSales->isEmpty())
            <div style="padding: 3rem 1.25rem; text-align: center;">
                <p style="font-size: 14px; font-weight: 500; color: #303030; margin: 0 0 0.25rem;">No festival sales yet</p>
                <p style="font-size: 13px; color: #616161; margin: 0 0 1rem;">
                    Create one to take a percentage off a set of products for Diwali, Holi or a launch offer.
                </p>
                <a href="{{ route('admin.festival-sales.create') }}" class="btn btn-primary" style="font-size: 13px; text-decoration: none;">Create festival sale</a>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #fafafa; border-bottom: 1px solid #e3e3e3;">
                            <th style="text-align: left; padding: 0.625rem 1rem; font-weight: 500; color: #616161;">Sale</th>
                            <th style="text-align: left; padding: 0.625rem 1rem; font-weight: 500; color: #616161;">Discount</th>
                            <th style="text-align: left; padding: 0.625rem 1rem; font-weight: 500; color: #616161;">Products</th>
                            <th style="text-align: left; padding: 0.625rem 1rem; font-weight: 500; color: #616161;">Status</th>
                            <th style="text-align: right; padding: 0.625rem 1rem; font-weight: 500; color: #616161;">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($festivalSales as $sale)
                            <tr style="border-bottom: 1px solid #f1f1f1;">
                                <td style="padding: 0.75rem 1rem;">
                                    <a href="{{ route('admin.festival-sales.edit', $sale) }}"
                                       style="font-weight: 500; color: #005bd3; text-decoration: none;">{{ $sale->name }}</a>
                                    @if($sale->banner_path)
                                        <span style="display: block; font-size: 12px; color: #8c9196;">Banner uploaded</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 1rem; font-weight: 600; color: #303030;">
                                    {{ rtrim(rtrim(number_format((float) $sale->discount_percent, 2), '0'), '.') }}% off
                                </td>
                                <td style="padding: 0.75rem 1rem; color: #616161;">{{ $sale->products_count }}</td>
                                <td style="padding: 0.75rem 1rem;">
                                    @if($sale->is_active)
                                        <span style="font-size: 12px; font-weight: 500; color: #1a7a2e; background: #e7f5ea; padding: 2px 8px; border-radius: 999px;">Live</span>
                                    @else
                                        <span style="font-size: 12px; font-weight: 500; color: #616161; background: #f1f1f1; padding: 2px 8px; border-radius: 999px;">Off</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: right; white-space: nowrap;">
                                    <form action="{{ route('admin.festival-sales.toggle', $sale) }}" method="POST" style="display: inline;"
                                          onsubmit="return confirm('{{ $sale->is_active
                                                ? 'End this sale? Every product in it goes back to its normal price.'
                                                : 'Start this sale? Every selected product will be repriced across the shop.' }}');">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 4px 10px;">
                                            {{ $sale->is_active ? 'End' : 'Start' }}
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.festival-sales.edit', $sale) }}" class="btn btn-secondary"
                                       style="font-size: 12px; padding: 4px 10px; text-decoration: none;">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($festivalSales->hasPages())
                <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                    {{ $festivalSales->links() }}
                </div>
            @endif
        @endif
    </div>
</x-layouts.admin>
