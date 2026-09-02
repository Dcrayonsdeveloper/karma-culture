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
        ]);

        // An unchecked box submits nothing, so taking is_active out of
        // $validated meant the record could be activated but never
        // deactivated. boolean() reads the absent key as false.
        $validated['is_active'] = $request->boolean('is_active');

        // min_order and the two estimate columns are NOT NULL with defaults.
        // Laravel turns a blank "Optional" input into null, and inserting an
        // explicit null into those columns is a database error rather than a
        // fallback to the default - so blanking one of these fields used to
        // throw a 500 instead of saving. Fall back to the column defaults.
        foreach (['min_order' => 0, 'estimated_days_min' => 3, 'estimated_days_max' => 7] as $field => $default) {
            $validated[$field] = $validated[$field] ?? $default;
        }

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
        ]);

        // An unchecked box submits nothing, so taking is_active out of
        // $validated meant the record could be activated but never
        // deactivated. boolean() reads the absent key as false.
        $validated['is_active'] = $request->boolean('is_active');

        // min_order and the two estimate columns are NOT NULL with defaults.
        // Laravel turns a blank "Optional" input into null, and inserting an
        // explicit null into those columns is a database error rather than a
        // fallback to the default - so blanking one of these fields used to
        // throw a 500 instead of saving. Fall back to the column defaults.
        foreach (['min_order' => 0, 'estimated_days_min' => 3, 'estimated_days_max' => 7] as $field => $default) {
            $validated[$field] = $validated[$field] ?? $default;
        }

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
