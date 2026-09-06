<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SizePreset;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SizePresetController extends Controller
{
    public function index(): View
    {
        $presets = SizePreset::ordered()->paginate(50)->withQueryString();

        return view('admin.size-presets.index', compact('presets'));
    }

    public function create(): View
    {
        return view('admin.size-presets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        SizePreset::create($this->validated($request));

        return redirect()->route('admin.size-presets.index')->with('success', 'Size added to the library.');
    }

    public function edit(SizePreset $sizePreset): View
    {
        return view('admin.size-presets.edit', ['preset' => $sizePreset]);
    }

    public function update(Request $request, SizePreset $sizePreset): RedirectResponse
    {
        $sizePreset->update($this->validated($request, $sizePreset->id));

        return redirect()->route('admin.size-presets.index')->with('success', 'Size updated.');
    }

    public function destroy(SizePreset $sizePreset): RedirectResponse
    {
        $sizePreset->delete();

        return redirect()->route('admin.size-presets.index')->with('success', 'Size removed from the library.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [...V::text(max: 100, min: 1), Rule::unique('size_presets', 'name')->ignore($ignoreId)],
            // Measurements are optional and copied onto the size row as a default.
            'measurements' => ['nullable', 'string', 'max:160', new \App\Rules\NoHtml],
            'is_active' => V::boolean(),
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
    }
}
