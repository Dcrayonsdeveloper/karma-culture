<x-layouts.admin>
    <x-slot name="title">Currencies</x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.settings.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Settings
        </a>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h1 style="font-size: 1.25rem; font-weight: 600; color: #303030; margin: 0;">Currencies</h1>
        <a href="{{ route('admin.settings.currencies.create') }}" class="btn btn-primary" style="font-size: 13px;">Add Currency</a>
    </div>

    <div class="card">
        @if($currencies->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3;">
                {{ $currencies->links('vendor.pagination.info-bar') }}
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Code</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Name</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Symbol</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Exchange Rate</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: right; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($currencies as $currency)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem; color: #303030; font-weight: 500;">
                                {{ $currency->code }}
                                @if($currency->is_default)
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #e3f1ff; color: #005bd3; margin-left: 0.25rem;">Default</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $currency->name }}</td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $currency->symbol }}</td>
                            <td style="padding: 0.625rem 1rem; color: #303030; text-align: right;">{{ $currency->exchange_rate }}</td>
                            <td style="padding: 0.625rem 1rem;">
                                @if($currency->is_active)
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #e4f3e6; color: #1a7a2e;">Active</span>
                                @else
                                    <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: #f0f0f0; color: #616161;">Inactive</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
                                    <a href="{{ route('admin.settings.currencies.edit', $currency) }}" style="font-size: 13px; font-weight: 500; color: #005bd3; text-decoration: none;">Edit</a>
                                    @unless($currency->is_default)
                                        <form action="{{ route('admin.settings.currencies.destroy', $currency) }}" method="POST" onsubmit="return confirm('Delete this currency?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="font-size: 13px; font-weight: 500; color: #d72c0d; cursor: pointer; background: none; border: none;">Delete</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 3rem 1rem; text-align: center; color: #616161; font-size: 13px;">
                                No currencies configured.
                                <a href="{{ route('admin.settings.currencies.create') }}" style="color: #005bd3; font-weight: 500; margin-left: 0.25rem; text-decoration: none;">Add one now</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($currencies->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">{{ $currencies->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
