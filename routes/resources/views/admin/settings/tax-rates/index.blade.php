<x-layouts.admin>
    <x-slot name="title">Tax Rates</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Tax Rates</h1>
            <a href="{{ route('admin.settings.tax-rates.create') }}" class="btn btn-primary" style="font-size: 13px;">Add Tax Rate</a>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.settings.tax') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Tax Settings
        </a>
    </div>

    <div style="margin-bottom: 0.5rem;">
        <p style="font-size: 13px; color: #616161; margin: 0;">Configure tax rates by region</p>
    </div>

    <div class="card" style="margin-top: 1rem;">
        @if($taxRates->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3;">
                {{ $taxRates->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Name</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">State</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">CGST %</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">SGST %</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">IGST %</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($taxRates as $taxRate)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem; font-weight: 500; color: #303030;">{{ $taxRate->name }}</td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $taxRate->state ?? '-' }}</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; color: #303030;">{{ number_format($taxRate->cgst_rate, 2) }}%</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; color: #303030;">{{ number_format($taxRate->sgst_rate, 2) }}%</td>
                            <td style="padding: 0.625rem 1rem; text-align: right; color: #303030;">{{ number_format($taxRate->igst_rate, 2) }}%</td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($taxRate->is_active)
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #cdfee1; color: #1a7a2e;">Active</span>
                                @else
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #ffe0db; color: #b71c00;">Inactive</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                                    <a href="{{ route('admin.settings.tax-rates.edit', $taxRate) }}" style="color: #005bd3; font-size: 13px; font-weight: 500; text-decoration: none;">Edit</a>
                                    <form action="{{ route('admin.settings.tax-rates.destroy', $taxRate) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this tax rate?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="color: #d72c0d; font-size: 13px; font-weight: 500; background: none; border: none; cursor: pointer;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 3rem 1rem; text-align: center; color: #616161; font-size: 13px;">
                                No tax rates configured yet.
                                <a href="{{ route('admin.settings.tax-rates.create') }}" style="color: #005bd3; font-weight: 500; text-decoration: none; margin-left: 0.25rem;">Add one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($taxRates->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $taxRates->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
