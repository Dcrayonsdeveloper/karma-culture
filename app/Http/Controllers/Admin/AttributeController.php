<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttributeController extends Controller
{
    /** The three shapes an attribute can take, as offered by the form. */
    public const TYPES = ['select', 'color', 'text'];

    public function index(Request $request): View
    {
        $query = Attribute::withCount('values')->with('values');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('values', fn($vq) => $vq->where('value', 'like', "%{$search}%"));
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by filterable
        if ($request->filled('filterable')) {
            $query->where('is_filterable', $request->filterable === 'yes');
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $attributes = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $stats = [
            'total' => Attribute::count(),
            'select' => Attribute::where('type', 'select')->count(),
            'color' => Attribute::where('type', 'color')->count(),
            'text' => Attribute::where('type', 'text')->count(),
            'filterable' => Attribute::where('is_filterable', true)->count(),
        ];

        return view('admin.attributes.index', compact('attributes', 'stats'));
    }

    public function create(): View
    {
        return view('admin.attributes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [...V::text(max: 100, min: 2), 'unique:attributes,name'],
            'type' => V::option(self::TYPES),
            'is_filterable' => V::boolean(),
            'is_visible' => V::boolean(),
        ]);

        Attribute::create($validated);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute created successfully');
    }

    public function edit(Attribute $attribute): View
    {
        $attribute->load('values');

        return view('admin.attributes.edit', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [...V::text(max: 100, min: 2), Rule::unique('attributes', 'name')->ignore($attribute->id)],
            'type' => V::option(self::TYPES),
            'is_filterable' => V::boolean(),
            'is_visible' => V::boolean(),
        ]);

        $attribute->update($validated);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute updated successfully');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        $attribute->delete();

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute deleted successfully');
    }
}
