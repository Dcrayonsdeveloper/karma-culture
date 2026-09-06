{{--
    "Pick from library" dropdown for the Sizes / Colours / Textures sections.
    It lives inside the section's kk* Alpine component, which must expose:
      presets (array), pickerOpen (bool), pickedIds (map), applyPicker().
    $type is 'size' | 'colour' | 'texture' and only changes the label and
    whether a swatch is shown next to each row.
--}}
@php
    $type = $type ?? 'size';
    $manage = [
        'size' => ['route' => 'admin.size-presets.index', 'label' => 'Sizes'],
        'colour' => ['route' => 'admin.colour-presets.index', 'label' => 'Colours'],
        'texture' => ['route' => 'admin.texture-presets.index', 'label' => 'Textures'],
    ][$type];
@endphp
<div style="position:relative;" @keydown.escape.window="pickerOpen = false">
    <button type="button" @click="pickerOpen = !pickerOpen"
            class="btn btn-secondary" style="font-size:12px; padding:4px 10px; display:inline-flex; align-items:center; gap:4px;">
        <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        Pick from library
    </button>

    <div x-show="pickerOpen" x-cloak @click.outside="pickerOpen = false"
         style="position:absolute; right:0; top:calc(100% + 4px); z-index:40; width:270px; background:#fff; border:1px solid #d4d4d4; border-radius:.5rem; box-shadow:0 6px 20px rgba(0,0,0,.14);">
        <template x-if="presets.length === 0">
            <p style="font-size:12px; color:#616161; padding:.85rem;">
                Nothing in the library yet. Add {{ strtolower($manage['label']) }} under
                <a href="{{ route($manage['route']) }}" style="color:#005bd3;" target="_blank" rel="noopener">Products &rarr; {{ $manage['label'] }}</a>.
            </p>
        </template>
        <template x-if="presets.length > 0">
            <div>
                <div style="max-height:260px; overflow:auto; padding:.4rem;">
                    <template x-for="p in presets" :key="p.id">
                        <label style="display:flex; align-items:center; gap:.5rem; padding:.35rem .4rem; border-radius:.375rem; cursor:pointer; font-size:13px; color:#303030;"
                               onmouseover="this.style.background='#f6f6f7'" onmouseout="this.style.background='transparent'">
                            <input type="checkbox" x-model="pickedIds[p.id]" style="width:.95rem; height:.95rem; accent-color:#303030; flex:0 0 auto;">
                            @if($type === 'colour')
                                <span :style="'width:1rem;height:1rem;border-radius:.25rem;border:1px solid #d4d4d4;flex:0 0 auto;background:'+p.hex"></span>
                            @endif
                            <span x-text="p.name" style="flex:1 1 auto; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></span>
                            @if($type === 'size')
                                <span x-show="p.measurements" x-text="p.measurements" style="color:#9e9e9e; font-size:11px; white-space:nowrap;"></span>
                            @endif
                        </label>
                    </template>
                </div>
                <div style="display:flex; justify-content:flex-end; align-items:center; gap:.5rem; padding:.5rem; border-top:1px solid #f1f1f1;">
                    <button type="button" @click="pickerOpen = false" style="font-size:12px; color:#616161; background:none; border:0; cursor:pointer;">Cancel</button>
                    <button type="button" @click="applyPicker()" class="btn btn-primary" style="font-size:12px; padding:3px 10px;">Add selected</button>
                </div>
            </div>
        </template>
    </div>
</div>
