<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryLocationController extends Controller
{
    public function index(): View
    {
        $perPage = request()->input('per_page', 10);
        $locations = InventoryLocation::withCount('stocks')->orderBy('name')->paginate($perPage)->withQueryString();

        return view('admin.inventory.locations.index', compact('locations'));
    }

    public function create(): View
    {
        return view('admin.inventory.locations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:inventory_locations',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        InventoryLocation::create($validated);

        return redirect()->route('admin.inventory.locations.index')->with('success', 'Location created');
    }

    public function edit(InventoryLocation $location): View
    {
        return view('admin.inventory.locations.edit', compact('location'));
    }

    public function update(Request $request, InventoryLocation $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:inventory_locations,code,' . $location->id,
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $location->update($validated);

        return redirect()->route('admin.inventory.locations.index')->with('success', 'Location updated');
    }

    public function destroy(InventoryLocation $location): RedirectResponse
    {
        $location->delete();

        return redirect()->route('admin.inventory.locations.index')->with('success', 'Location deleted');
    }
}
