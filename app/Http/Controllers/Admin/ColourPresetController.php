<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ColourPreset;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ColourPresetController extends Controller
{
    /** A #rrggbb swatch, the same shape a product colour stores. */
    private const HEX_RULES = ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];

    public function index(): View
    {
        $presets = ColourPreset::ordered()->paginate(50)->withQueryString();

        return view('admin.colour-presets.index', compact('presets'));
    }

    public function create(): View
    {
        return view('admin.colour-presets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        ColourPreset::create($data);

        return redirect()->route('admin.colour-presets.index')->with('success', 'Colour added to the library.');
    }

    public function edit(ColourPreset $colourPreset): View
    {
        return view('admin.colour-presets.edit', ['preset' => $colourPreset]);
    }

    public function update(Request $request, ColourPreset $colourPreset): RedirectResponse
    {
        $data = $this->validated($request, $colourPreset->id);

        $colourPreset->update($data);

        return redirect()->route('admin.colour-presets.index')->with('success', 'Colour updated.');
    }

    public function destroy(ColourPreset $colourPreset): RedirectResponse
    {
        $colourPreset->delete();

        return redirect()->route('admin.colour-presets.index')->with('success', 'Colour removed from the library.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [...V::text(max: 60, min: 1), Rule::unique('colour_presets', 'name')->ignore($ignoreId)],
            'hex' => self::HEX_RULES,
            'is_active' => V::boolean(),
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
    }
}
