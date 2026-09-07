<x-layouts.admin>
    <x-slot name="title">Add Festival Sale</x-slot>

    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.festival-sales.index') }}" class="btn-icon" style="flex-shrink: 0; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add festival sale</h1>
    </div>

    <x-admin.form-errors />

    <form action="{{ route('admin.festival-sales.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        @include('admin.festival-sales.partials.form', ['festivalSale' => null])

        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem;">
            <a href="{{ route('admin.festival-sales.index') }}" class="btn btn-secondary" style="text-decoration: none;">Cancel</a>
            <button type="submit" class="btn btn-primary">Create sale</button>
        </div>
    </form>
</x-layouts.admin>
