<x-layouts.seller>
    <x-slot name="title">Question Details</x-slot>

    <div class="flex items-center gap-2 text-sm text-neutral-600 mb-6">
        <a href="{{ route('seller.questions.index') }}" class="hover:text-primary-600">Q&A</a>
        <span>/</span>
        <span>Question Details</span>
    </div>

    <div class="max-w-3xl">
        <!-- Question -->
        <div class="card p-6 mb-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-sm text-neutral-600 mb-1">
                        Product: <span class="font-medium text-neutral-700">{{ $question->product->name ?? 'N/A' }}</span>
                    </p>
                    <p class="text-sm text-neutral-600">
                        Asked by {{ $question->user->first_name ?? '' }} {{ $question->user->last_name ?? '' }}
                        on {{ $question->created_at->format('F d, Y') }}
                    </p>
                </div>
                @if($question->is_answered)
                    <span class="badge badge-success">Answered</span>
                @else
                    <span class="badge badge-warning">Pending</span>
                @endif
            </div>

            <p class="text-neutral-900 text-lg">{{ $question->question }}</p>
        </div>

        <!-- Existing Answers -->
        @if($question->answers->count())
            <div class="space-y-4 mb-6">
                <h3 class="font-semibold text-neutral-900">Answers ({{ $question->answers->count() }})</h3>
                @foreach($question->answers as $answer)
                    <div class="card p-4 bg-neutral-50">
                        <p class="text-neutral-700">{{ $answer->answer }}</p>
                        <p class="text-xs text-neutral-600 mt-2">
                            {{ $answer->created_at->format('M d, Y H:i') }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Answer Form -->
        <div class="card p-6">
            <h3 class="font-semibold text-neutral-900 mb-4">{{ $question->is_answered ? 'Add Another Answer' : 'Answer This Question' }}</h3>
            <form action="{{ route('seller.questions.answer', $question) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <textarea name="answer" rows="4" required
                              class="form-input w-full @error('answer') border-error-300 @enderror"
                              placeholder="Write your answer...">{{ old('answer') }}</textarea>
                    @error('answer')
                        <p class="mt-1 text-sm text-error-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary">Submit Answer</button>
            </form>
        </div>
    </div>
</x-layouts.seller>
