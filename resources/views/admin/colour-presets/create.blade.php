<x-layouts.admin>
    <x-slot name="title">Add Colour</x-slot>

    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
            <a href="{{ route('admin.colour-presets.index') }}" class="btn-icon" style="padding: 0.25rem; border-radius: 0.25rem; color: #616161; text-decoration: none;">
                <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 style="font-size: 1.125rem; font-weight: 600; color: #303030;">Add colour</h1>
        </div>

        <form action="{{ route('admin.colour-presets.store') }}" method="POST" style="max-width: 32rem;">
            @csrf
            @include('admin.colour-presets._form', ['preset' => null])

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e3e3e3;">
                <a href="{{ route('admin.colour-presets.index') }}" class="btn btn-secondary" style="font-size: 13px;">Discard</a>
                <button type="submit" class="btn btn-primary" style="font-size: 13px;">Save colour</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
