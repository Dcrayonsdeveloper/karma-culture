<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TexturePreset;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TexturePresetController extends Controller
{
    public function index(): View
    {
        $presets = TexturePreset::ordered()->paginate(50)->withQueryString();

        return view('admin.texture-presets.index', compact('presets'));
    }

    public function create(): View
    {
        return view('admin.texture-presets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        TexturePreset::create($this->validated($request));

        return redirect()->route('admin.texture-presets.index')->with('success', 'Texture added to the library.');
    }

    public function edit(TexturePreset $texturePreset): View
    {
        return view('admin.texture-presets.edit', ['preset' => $texturePreset]);
    }

    public function update(Request $request, TexturePreset $texturePreset): RedirectResponse
    {
        $texturePreset->update($this->validated($request, $texturePreset->id));

        return redirect()->route('admin.texture-presets.index')->with('success', 'Texture updated.');
    }

    public function destroy(TexturePreset $texturePreset): RedirectResponse
    {
        $texturePreset->delete();

        return redirect()->route('admin.texture-presets.index')->with('success', 'Texture removed from the library.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [...V::text(max: 60, min: 1), Rule::unique('texture_presets', 'name')->ignore($ignoreId)],
            'is_active' => V::boolean(),
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
    }
}
