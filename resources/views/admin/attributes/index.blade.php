<x-layouts.admin>
    <x-slot name="title">Attributes</x-slot>

    <x-slot name="header">
        <div class="page-header">
            <h1>Attributes</h1>
            <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary" style="font-size: 13px;">Add attribute</a>
        </div>
    </x-slot>

    {{-- Stats row --}}
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1px; background: #e3e3e3; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1rem;">
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Total</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #303030;">{{ $stats['total'] }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Select</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #005bd3;">{{ $stats['select'] }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Color</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #b98900;">{{ $stats['color'] }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Text</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #1a7a2e;">{{ $stats['text'] }}</p>
        </div>
        <div style="background: white; padding: 0.875rem 1rem;">
            <p style="font-size: 13px; color: #616161; margin-bottom: 2px;">Filterable</p>
            <p style="font-size: 1.25rem; font-weight: 600; color: #7c3aed;">{{ $stats['filterable'] }}</p>
        </div>
    </div>

    {{-- Search card --}}
    <div class="card" style="margin-bottom: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem;">
            <form action="{{ route('admin.attributes.index') }}" method="GET" style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                <div style="position: relative; flex: 1; max-width: 24rem;">
                    <svg style="position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #999;" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search attributes or values..."
                           style="padding-left: 2rem; border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; width: 100%; padding-top: 0.375rem; padding-bottom: 0.375rem; padding-right: 0.625rem;">
                </div>
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                @if(request('filterable'))
                    <input type="hidden" name="filterable" value="{{ request('filterable') }}">
                @endif
                <select name="type" style="border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; padding: 0.375rem 0.5rem; color: #303030;">
                    <option value="">All Types</option>
                    <option value="select" @selected(request('type') === 'select')>Select</option>
                    <option value="color" @selected(request('type') === 'color')>Color</option>
                    <option value="text" @selected(request('type') === 'text')>Text</option>
                </select>
                <select name="filterable" style="border: 1px solid #c9cccf; border-radius: 0.5rem; font-size: 13px; padding: 0.375rem 0.5rem; color: #303030;">
                    <option value="">Filterable</option>
                    <option value="yes" @selected(request('filterable') === 'yes')>Yes</option>
                    <option value="no" @selected(request('filterable') === 'no')>No</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            </form>
            @if(request()->hasAny(['search', 'type', 'filterable']))
                <a href="{{ route('admin.attributes.index') }}" style="font-size: 13px; color: #005bd3; font-weight: 500; text-decoration: none; white-space: nowrap;">Clear all</a>
            @endif
        </div>
    </div>

    {{-- Attributes Grid --}}
    @if($attributes->count())
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
            @foreach($attributes as $attribute)
                <div class="card" style="overflow: hidden;">
                    {{-- Card Header --}}
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e3e3e3; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.625rem;">
                            @if($attribute->type === 'select')
                                <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: #e0f0ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" fill="none" stroke="#005bd3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                                    </svg>
                                </div>
                            @elseif($attribute->type === 'color')
                                <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: #fff3cd; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" fill="none" stroke="#b98900" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                    </svg>
                                </div>
                            @else
                                <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: #cdfee1; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <svg width="16" height="16" fill="none" stroke="#1a7a2e" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <p style="font-weight: 600; font-size: 13px; color: #303030; margin: 0;">{{ $attribute->name }}</p>
                                <div style="display: flex; align-items: center; gap: 0.375rem; margin-top: 2px;">
                                    @if($attribute->type === 'select')
                                        <span class="badge badge-info">{{ ucfirst($attribute->type) }}</span>
                                    @elseif($attribute->type === 'color')
                                        <span class="badge badge-warning">{{ ucfirst($attribute->type) }}</span>
                                    @else
                                        <span class="badge badge-success">{{ ucfirst($attribute->type) }}</span>
                                    @endif
                                    <span style="font-size: 12px; color: #616161;">{{ $attribute->values_count }} {{ Str::plural('value', $attribute->values_count) }}</span>
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 2px;">
                            <a href="{{ route('admin.attributes.edit', $attribute) }}" class="btn-icon" title="Edit">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <form action="{{ route('admin.attributes.destroy', $attribute) }}" method="POST" onsubmit="return confirm('Delete this attribute and all its values?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon" style="color: #d72c0d;" title="Delete">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Values Preview --}}
                    <div style="padding: 0.75rem 1rem;">
                        @if($attribute->values->count())
                            @if($attribute->type === 'color')
                                <div style="display: flex; flex-wrap: wrap; gap: 0.375rem;">
                                    @foreach($attribute->values as $value)
                                        <div style="display: flex; align-items: center; gap: 0.375rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; background: #f6f6f7; border: 1px solid #e3e3e3;">
                                            <div style="width: 1rem; height: 1rem; border-radius: 50%; border: 1px solid #e3e3e3; flex-shrink: 0; background-color: {{ $value->color_code ?? '#ccc' }};"></div>
                                            <span style="font-size: 12px; color: #616161;">{{ $value->value }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                    @foreach($attribute->values as $value)
                                        <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.5rem; border-radius: 0.375rem; background: #f6f6f7; border: 1px solid #e3e3e3; font-size: 12px; font-weight: 500; color: #616161;">{{ $value->value }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div style="text-align: center; padding: 1rem 0;">
                                <p style="font-size: 13px; color: #616161; font-style: italic; margin: 0 0 0.25rem 0;">No values added yet</p>
                                <a href="{{ route('admin.attributes.values.create', $attribute) }}" style="font-size: 12px; color: #005bd3; font-weight: 500; text-decoration: none;">Add values</a>
                            </div>
                        @endif
                    </div>

                    {{-- Card Footer --}}
                    <div style="padding: 0.625rem 1rem; border-top: 1px solid #e3e3e3; background: #f6f6f7; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.375rem;">
                                @if($attribute->is_filterable)
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 1.125rem; height: 1.125rem; border-radius: 50%; background: #cdfee1;">
                                        <svg width="10" height="10" fill="#1a7a2e" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 1.125rem; height: 1.125rem; border-radius: 50%; background: #f1f1f1;">
                                        <svg width="10" height="10" fill="none" stroke="#616161" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </span>
                                @endif
                                <span style="font-size: 12px; color: #616161;">Filterable</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.375rem;">
                                @if($attribute->is_visible)
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 1.125rem; height: 1.125rem; border-radius: 50%; background: #cdfee1;">
                                        <svg width="10" height="10" fill="#1a7a2e" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 1.125rem; height: 1.125rem; border-radius: 50%; background: #f1f1f1;">
                                        <svg width="10" height="10" fill="none" stroke="#616161" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </span>
                                @endif
                                <span style="font-size: 12px; color: #616161;">Visible</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.attributes.edit', $attribute) }}" style="font-size: 12px; color: #005bd3; font-weight: 500; text-decoration: none;">
                            Manage Values
                            <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: inline; vertical-align: middle; margin-left: 2px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card">
            <div style="padding: 4rem 1rem; text-align: center;">
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <div style="width: 3rem; height: 3rem; border-radius: 50%; background: #f1f1f1; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                        <svg width="24" height="24" fill="none" stroke="#616161" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <p style="font-size: 14px; font-weight: 600; color: #303030; margin: 0 0 0.25rem 0;">No attributes found</p>
                    <p style="font-size: 13px; color: #616161; margin: 0 0 0.75rem 0;">Create attributes to define product specifications like size, color, and material.</p>
                    <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary" style="font-size: 13px;">Add attribute</a>
                </div>
            </div>
        </div>
    @endif

    {{-- Pagination --}}
    @if($attributes->hasPages())
        <div style="margin-top: 1rem;">
            {{ $attributes->links() }}
        </div>
    @endif
</x-layouts.admin>
