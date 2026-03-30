<x-layouts.seller>
    <x-slot name="title">Promotions</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Promotions</h1>
            <p class="text-neutral-600">Create and manage product promotions</p>
        </div>
        <a href="{{ route('seller.promotions.create') }}" class="btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Promotion
        </a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Value</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Period</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($promotions as $promotion)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 font-medium text-neutral-900">{{ $promotion->name }}</td>
                            <td class="px-4 py-3 text-sm text-neutral-600">{{ ucfirst($promotion->type) }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($promotion->type === 'percentage')
                                    {{ $promotion->value }}%
                                @else
                                    @price($promotion->value)
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $promotion->starts_at?->format('M d, Y') }} - {{ $promotion->ends_at?->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                @if($promotion->ends_at && $promotion->ends_at->isPast())
                                    <span class="badge badge-neutral">Expired</span>
                                @elseif($promotion->starts_at && $promotion->starts_at->isFuture())
                                    <span class="badge badge-info">Scheduled</span>
                                @elseif($promotion->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-neutral">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('seller.promotions.edit', $promotion) }}"
                                       class="p-2 text-neutral-600 hover:text-primary-600" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('seller.promotions.destroy', $promotion) }}" method="POST"
                                          onsubmit="return confirm('Delete this promotion?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-neutral-600 hover:text-error-600" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                <h3 class="text-lg font-medium text-neutral-900 mb-1">No promotions yet</h3>
                                <p class="text-neutral-600 mb-4">Create your first promotion to boost sales.</p>
                                <a href="{{ route('seller.promotions.create') }}" class="btn-primary">New Promotion</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($promotions->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $promotions->links() }}
            </div>
        @endif
    </div>
</x-layouts.seller>
