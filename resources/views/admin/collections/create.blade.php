<x-layouts.admin>
    <x-slot name="title">Add Collection</x-slot>

    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
        <a href="{{ route('admin.collections.index') }}" class="btn-icon" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
            <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add collection</h1>
    </div>

    <form action="{{ route('admin.collections.store') }}" method="POST">
        @csrf
        @include('admin.collections.form', ['collection' => null])
    </form>
</x-layouts.admin>
