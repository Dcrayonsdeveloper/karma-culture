<x-layouts.seller>
    <x-slot name="title">Q&A</x-slot>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Questions & Answers</h1>
            <p class="text-neutral-600">Answer customer questions about your products</p>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Question</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Asked By</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-neutral-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-neutral-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($questions as $question)
                        <tr class="hover:bg-neutral-50">
                            <td class="px-4 py-3 text-sm text-neutral-900 max-w-xs truncate">
                                {{ Str::limit($question->question, 60) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $question->product->name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $question->user->first_name ?? '' }} {{ $question->user->last_name ?? '' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($question->is_answered)
                                    <span class="badge badge-success">Answered</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-600">
                                {{ $question->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('seller.questions.show', $question) }}"
                                   class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                    {{ $question->is_answered ? 'View' : 'Answer' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h3 class="text-lg font-medium text-neutral-900 mb-1">No questions yet</h3>
                                <p class="text-neutral-600">Customer questions about your products will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($questions->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $questions->links() }}
            </div>
        @endif
    </div>
</x-layouts.seller>
