<x-layouts.admin>
    <x-slot name="title">Edit {{ $festivalSale->name }}</x-slot>

    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
        <a href="{{ route('admin.festival-sales.index') }}" class="btn-icon" style="flex-shrink: 0; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030; margin: 0;">{{ $festivalSale->name }}</h1>

        @if($festivalSale->is_active)
            <span style="font-size: 12px; font-weight: 500; color: #1a7a2e; background: #e7f5ea; padding: 2px 8px; border-radius: 999px;">Live</span>
        @else
            <span style="font-size: 12px; font-weight: 500; color: #616161; background: #f1f1f1; padding: 2px 8px; border-radius: 999px;">Off</span>
        @endif

        <div style="margin-left: auto; display: flex; gap: 0.5rem; align-items: center;">
            @if($festivalSale->is_active)
                <a href="{{ route('festival-sale.show', $festivalSale) }}" target="_blank" rel="noopener"
                   class="btn btn-secondary" style="font-size: 13px; text-decoration: none;">View sale page</a>
            @endif

            {{-- Its own form: the toggle must not carry the edit form's fields. --}}
            <form action="{{ route('admin.festival-sales.toggle', $festivalSale) }}" method="POST"
                  onsubmit="return confirm('{{ $festivalSale->is_active
                        ? 'End this sale? Every product in it goes back to its normal price.'
                        : 'Start this sale? Every selected product will be repriced across the shop.' }}');">
                @csrf
                @method('PUT')
                <button type="submit" class="btn {{ $festivalSale->is_active ? 'btn-secondary' : 'btn-primary' }}" style="font-size: 13px;">
                    {{ $festivalSale->is_active ? 'End sale' : 'Start sale' }}
                </button>
            </form>
        </div>
    </div>

    <x-admin.form-errors />

    <form action="{{ route('admin.festival-sales.update', $festivalSale) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('admin.festival-sales.partials.form')

        <div style="display: flex; justify-content: space-between; gap: 0.5rem; margin-top: 1.25rem; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary" style="color: #d72c0d;"
                    onclick="document.getElementById('fest-delete').submit();">
                Delete sale
            </button>
            <div style="display: flex; gap: 0.5rem;">
                <a href="{{ route('admin.festival-sales.index') }}" class="btn btn-secondary" style="text-decoration: none;">Cancel</a>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </form>

    {{-- Outside the edit form: nesting one form in another is invalid and the
         browser drops the inner one, which would make Delete do nothing. --}}
    <form id="fest-delete" action="{{ route('admin.festival-sales.destroy', $festivalSale) }}" method="POST"
          onsubmit="return confirm('Delete this sale? Every product in it is put back to its normal price first.');">
        @csrf
        @method('DELETE')
    </form>
</x-layouts.admin>
