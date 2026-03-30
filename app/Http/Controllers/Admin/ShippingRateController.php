<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingRateController extends Controller
{
    public function create(ShippingZone $shippingZone): View
    {
        return view('admin.settings.shipping-rates.create', compact('shippingZone'));
    }

    public function store(Request $request, ShippingZone $shippingZone): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:flat,weight,price,free',
            'rate' => 'required|numeric|min:0',
            'min_order' => 'nullable|numeric|min:0',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0',
            'estimated_days_min' => 'nullable|integer|min:1',
            'estimated_days_max' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['zone_id'] = $shippingZone->id;

        ShippingRate::create($validated);

        return redirect()->route('admin.settings.shipping-zones.edit', $shippingZone)->with('success', 'Rate added');
    }

    public function edit(ShippingRate $rate): View
    {
        return view('admin.settings.shipping-rates.edit', compact('rate'));
    }

    public function update(Request $request, ShippingRate $rate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:flat,weight,price,free',
            'rate' => 'required|numeric|min:0',
            'min_order' => 'nullable|numeric|min:0',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0',
            'estimated_days_min' => 'nullable|integer|min:1',
            'estimated_days_max' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $rate->update($validated);

        return redirect()->route('admin.settings.shipping-zones.edit', $rate->zone)->with('success', 'Rate updated');
    }

    public function destroy(ShippingRate $rate): RedirectResponse
    {
        $zone = $rate->zone;
        $rate->delete();

        return redirect()->route('admin.settings.shipping-zones.edit', $zone)->with('success', 'Rate deleted');
    }
}
