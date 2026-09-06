<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TexturePreset;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TexturePresetController extends Controller
{
    /** A swatch is a small crop of fabric, not a product photo. */
    private const MAX_IMAGE_KB = 2048;

    /** Where swatches are filed on the public disk. */
    private const IMAGE_DIR = 'texture-presets';

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
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store(self::IMAGE_DIR, 'public');
        }

        TexturePreset::create($data);

        return redirect()->route('admin.texture-presets.index')->with('success', 'Texture added to the library.');
    }

    public function edit(TexturePreset $texturePreset): View
    {
        return view('admin.texture-presets.edit', ['preset' => $texturePreset]);
    }

    public function update(Request $request, TexturePreset $texturePreset): RedirectResponse
    {
        $data = $this->validated($request, $texturePreset->id);

        // An empty file input means "keep the swatch you have" - only an upload
        // or an explicit tick replaces or clears it. The old file is deleted
        // either way, so replacing a swatch twenty times leaves one file behind
        // rather than twenty.
        $old = $texturePreset->image_path;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store(self::IMAGE_DIR, 'public');
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }

        $texturePreset->update($data);

        if ($old && array_key_exists('image_path', $data) && $data['image_path'] !== $old) {
            $this->deleteImage($old);
        }

        return redirect()->route('admin.texture-presets.index')->with('success', 'Texture updated.');
    }

    public function destroy(TexturePreset $texturePreset): RedirectResponse
    {
        $image = $texturePreset->image_path;

        $texturePreset->delete();

        $this->deleteImage($image);

        return redirect()->route('admin.texture-presets.index')->with('success', 'Texture removed from the library.');
    }

    /**
     * Drop an uploaded swatch off the disk.
     *
     * Only paths this screen wrote are touched: a row pointing at a full URL or
     * somewhere outside the swatch folder was not ours to delete.
     */
    private function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, self::IMAGE_DIR.'/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'name' => [...V::text(max: 60, min: 1), Rule::unique('texture_presets', 'name')->ignore($ignoreId)],
            'image' => V::image(required: false, maxKb: self::MAX_IMAGE_KB),
            'remove_image' => V::boolean(),
            'is_active' => V::boolean(),
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ], V::imageMessages('image', self::MAX_IMAGE_KB));

        // The upload itself is handled by the caller, which knows whether it is
        // creating or replacing; neither key is a column.
        unset($validated['image'], $validated['remove_image']);

        return $validated;
    }
}
