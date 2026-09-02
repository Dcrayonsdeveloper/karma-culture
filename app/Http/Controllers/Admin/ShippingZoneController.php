<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingZoneController extends Controller
{
    public function index(): View
    {
        // Straight off the query string this let ?per_page=999999 pull the
        // whole table into one render.
        $perPage = min(max((int) request()->integer('per_page', 10), 5), 100);
        $zones = ShippingZone::withCount('rates')->orderBy('name')->paginate($perPage)->withQueryString();

        return view('admin.settings.shipping-zones.index', compact('zones'));
    }

    public function create(): View
    {
        return view('admin.settings.shipping-zones.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'regions' => 'nullable|string|max:2000',
        ]);

        // An unchecked box submits nothing, so taking is_active out of
        // $validated meant the record could be activated but never
        // deactivated. boolean() reads the absent key as false.
        $validated['is_active'] = $request->boolean('is_active');
        $validated['regions'] = $this->parseRegions($validated['regions'] ?? null);

        ShippingZone::create($validated);

        return redirect()->route('admin.settings.shipping-zones.index')->with('success', 'Shipping zone created');
    }

    public function edit(ShippingZone $shippingZone): View
    {
        $shippingZone->load('rates');

        return view('admin.settings.shipping-zones.edit', compact('shippingZone'));
    }

    public function update(Request $request, ShippingZone $shippingZone): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'regions' => 'nullable|string|max:2000',
        ]);

        // An unchecked box submits nothing, so taking is_active out of
        // $validated meant the record could be activated but never
        // deactivated. boolean() reads the absent key as false.
        $validated['is_active'] = $request->boolean('is_active');
        $validated['regions'] = $this->parseRegions($validated['regions'] ?? null);

        $shippingZone->update($validated);

        return redirect()->route('admin.settings.shipping-zones.index')->with('success', 'Shipping zone updated');
    }

    /**
     * The zone form collects regions as one-per-line text; the column is a
     * JSON array. Previously `regions` was validated, cast and rendered in the
     * index but no form ever collected it, so every zone was stored with null.
     */
    private function parseRegions(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $regions = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $raw))));

        return $regions ?: null;
    }

    public function destroy(ShippingZone $shippingZone): RedirectResponse
    {
        $shippingZone->delete();

        return redirect()->route('admin.settings.shipping-zones.index')->with('success', 'Shipping zone deleted');
    }
}
