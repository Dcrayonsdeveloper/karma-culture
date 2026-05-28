@if($paginator->total() > 0)
    <div class="flex items-center justify-between" x-data="{ perPage: {{ $paginator->perPage() }} }">
        {{-- Mobile --}}
        <div class="flex flex-1 items-center justify-between sm:hidden">
            <div class="flex items-center gap-2">
                <label class="text-xs text-neutral-600">Rows:</label>
                <select x-model="perPage"
                        @change="
                            let u = new URL(window.location.href);
                            u.searchParams.set('per_page', perPage);
                            u.searchParams.delete('page');
                            window.location.href = u.href;
                        "
                        class="form-select text-xs py-1 pl-2 pr-7 rounded-md border-neutral-200">
                    @foreach([5, 10, 25, 50, 100] as $size)
                        <option value="{{ $size }}">{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <span class="text-xs text-neutral-600">
                {{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
            </span>
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <p class="text-sm text-neutral-600">
                Showing
                @if ($paginator->firstItem())
                    <span class="font-semibold text-neutral-700">{{ $paginator->firstItem() }}</span>
                    to
                    <span class="font-semibold text-neutral-700">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                of
                <span class="font-semibold text-neutral-700">{{ $paginator->total() }}</span>
                results
            </p>
            <div class="flex items-center gap-2">
                <label class="text-sm text-neutral-600 whitespace-nowrap">Rows per page:</label>
                <select x-model="perPage"
                        @change="
                            let u = new URL(window.location.href);
                            u.searchParams.set('per_page', perPage);
                            u.searchParams.delete('page');
                            window.location.href = u.href;
                        "
                        class="form-select text-sm py-1 pl-2.5 pr-8 rounded-md border-neutral-200 focus:border-primary-300 focus:ring-primary-200">
                    @foreach([5, 10, 25, 50, 100] as $size)
                        <option value="{{ $size }}">{{ $size }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
@endif
