<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttributeValueController extends Controller
{
    public function create(Attribute $attribute): View
    {
        return view('admin.attributes.values.create', compact('attribute'));
    }

    public function store(Request $request, Attribute $attribute): RedirectResponse
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'color_code' => 'nullable|string|max:7',
            'position' => 'nullable|integer',
        ]);

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
        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'color_code' => 'nullable|string|max:7',
            'position' => 'nullable|integer',
        ]);

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
