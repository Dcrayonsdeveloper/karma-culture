<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttributeController extends Controller
{
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

        $perPage = $request->input('per_page', 10);
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
            'name' => 'required|string|max:255|unique:attributes',
            'type' => 'required|in:select,color,text',
            'is_filterable' => 'boolean',
            'is_visible' => 'boolean',
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
            'name' => 'required|string|max:255|unique:attributes,name,' . $attribute->id,
            'type' => 'required|in:select,color,text',
            'is_filterable' => 'boolean',
            'is_visible' => 'boolean',
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
