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
        $perPage = request()->input('per_page', 10);
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
            'regions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

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
            'regions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $shippingZone->update($validated);

        return redirect()->route('admin.settings.shipping-zones.index')->with('success', 'Shipping zone updated');
    }

    public function destroy(ShippingZone $shippingZone): RedirectResponse
    {
        $shippingZone->delete();

        return redirect()->route('admin.settings.shipping-zones.index')->with('success', 'Shipping zone deleted');
    }
}
