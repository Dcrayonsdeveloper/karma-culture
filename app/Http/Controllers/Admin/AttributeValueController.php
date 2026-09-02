<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttributeValueController extends Controller
{
    /**
     * One shape for both store() and update(), so an edit cannot accept a value
     * that a create would refuse.
     *
     * color_code is varchar(7): a #rrggbb swatch, which is the only thing an
     * <input type="color"> ever posts. position is a plain INT column, capped
     * well below its range because it is only ever a sort key.
     */
    private function rules(): array
    {
        return [
            'value' => V::text(max: 255),
            'color_code' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'position' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function create(Attribute $attribute): View
    {
        return view('admin.attributes.values.create', compact('attribute'));
    }

    public function store(Request $request, Attribute $attribute): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $validated['attribute_id'] = $attribute->id;

        AttributeValue::create($validated);

        return redirect()->route('admin.attributes.edit', $attribute)->with('success', 'Value added successfully');
    }

    public function edit(AttributeValue $value): View
    {
        return view('admin.attributes.values.edit', compact('value'));
    }

    public function update(Request $request, AttributeValue $value): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $value->update($validated);

        return redirect()->route('admin.attributes.edit', $value->attribute)->with('success', 'Value updated successfully');
    }

    public function destroy(AttributeValue $value): RedirectResponse
    {
        $attribute = $value->attribute;
        $value->delete();

        return redirect()->route('admin.attributes.edit', $attribute)->with('success', 'Value deleted successfully');
    }
}
