<x-layouts.admin>
    <x-slot name="title">Inventory Movements</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Stock Movements</h1>
        </div>
    </x-slot>

    <div style="margin-bottom: 0.25rem;">
        <a href="{{ route('admin.inventory.index') }}" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 13px; color: #005bd3; text-decoration: none;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none"><path d="M12 16l-6-6 6-6" stroke="#005bd3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Inventory
        </a>
    </div>

    <div style="margin-bottom: 0.5rem;">
        <p style="font-size: 13px; color: #616161; margin: 0;">Full history of stock changes</p>
    </div>

    <div class="card" style="margin-top: 1rem;">
        @if($movements->total() > 0)
            <div style="padding: 0.5rem 1rem; border-bottom: 1px solid #e3e3e3;">
                <p style="font-size: 13px; color: #616161; margin: 0;">
                    Showing <span style="font-weight: 500; color: #303030;">{{ $movements->firstItem() }}</span>-<span style="font-weight: 500; color: #303030;">{{ $movements->lastItem() }}</span> of <span style="font-weight: 500; color: #303030;">{{ $movements->total() }}</span> records
                </p>
            </div>
        @endif
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 1px solid #e3e3e3;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Date</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Product</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Type</th>
                        <th style="padding: 0.5rem 1rem; text-align: center; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Qty</th>
                        <th style="padding: 0.5rem 1rem; text-align: center; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Before / After</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">Reason</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 12px; font-weight: 500; color: #616161; text-transform: uppercase;">By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 0.625rem 1rem;">
                                <p style="color: #303030; margin: 0;">{{ $movement->created_at->format('M d, Y') }}</p>
                                <p style="font-size: 12px; color: #616161; margin: 0;">{{ $movement->created_at->format('H:i') }}</p>
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                <p style="font-weight: 500; color: #303030; margin: 0;">{{ $movement->product->name ?? 'N/A' }}</p>
                                @if($movement->product?->sku)
                                    <span style="font-size: 12px; font-family: monospace; background: #f6f6f7; color: #616161; padding: 0.125rem 0.375rem; border-radius: 0.25rem;">{{ $movement->product->sku }}</span>
                                @endif
                            </td>
                            <td style="padding: 0.625rem 1rem;">
                                @php
                                    $typeBadges = [
                                        'in'         => ['background' => '#cdfee1', 'color' => '#1a7a2e'],
                                        'out'        => ['background' => '#ffe0db', 'color' => '#b71c00'],
                                        'adjustment' => ['background' => '#e3e3e3', 'color' => '#303030'],
                                    ];
                                    $typeLabels = ['in' => 'Stock In', 'out' => 'Stock Out', 'adjustment' => 'Adjustment'];
                                    $badge = $typeBadges[$movement->type] ?? ['background' => '#e3e3e3', 'color' => '#303030'];
                                @endphp
                                <span style="display: inline-block; padding: 0.125rem 0.5rem; border-radius: 1rem; font-size: 12px; font-weight: 500; background: {{ $badge['background'] }}; color: {{ $badge['color'] }};">
                                    {{ $typeLabels[$movement->type] ?? ucfirst($movement->type) }}
                                </span>
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: center;">
                                @php
                                    $qtyColor = $movement->type === 'in' ? '#1a7a2e' : ($movement->type === 'out' ? '#d72c0d' : '#303030');
                                    $qtySign = $movement->type === 'in' ? '+' : ($movement->type === 'out' ? '-' : '');
                                @endphp
                                <span style="font-weight: 700; color: {{ $qtyColor }};">{{ $qtySign }}{{ $movement->quantity }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; text-align: center;">
                                <span style="color: #616161;">{{ $movement->quantity_before }}</span>
                                <span style="color: #616161; margin: 0 0.25rem;">&rarr;</span>
                                <span style="font-weight: 600; color: #303030;">{{ $movement->quantity_after }}</span>
                            </td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $movement->reason ?? '—' }}</td>
                            <td style="padding: 0.625rem 1rem; color: #616161;">{{ $movement->createdBy?->full_name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 3rem 1rem; text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                    <div style="width: 48px; height: 48px; background: #f6f6f7; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#616161" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    </div>
                                    <p style="font-size: 13px; font-weight: 500; color: #303030; margin: 0;">No movements recorded yet</p>
                                    <p style="font-size: 12px; color: #616161; margin: 0;">Stock adjustments will appear here</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div style="padding: 0.75rem 1rem; border-top: 1px solid #e3e3e3;">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
