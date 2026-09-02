<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxRateController extends Controller
{
    public function index(): View
    {
        // Straight off the query string this let ?per_page=999999 pull the
        // whole table into one render.
        $perPage = min(max((int) request()->integer('per_page', 10), 5), 100);
        $taxRates = TaxRate::orderBy('name')->paginate($perPage)->withQueryString();

        return view('admin.settings.tax-rates.index', compact('taxRates'));
    }

    public function create(): View
    {
        return view('admin.settings.tax-rates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'state' => 'nullable|string|max:100',
            'cgst_rate' => 'required|numeric|min:0|max:100',
            'sgst_rate' => 'required|numeric|min:0|max:100',
            'igst_rate' => 'required|numeric|min:0|max:100',
        ]);

        // An unchecked box submits nothing, so taking is_active out of
        // $validated meant the record could be activated but never
        // deactivated. boolean() reads the absent key as false.
        $validated['is_active'] = $request->boolean('is_active');

        TaxRate::create($validated);

        return redirect()->route('admin.settings.tax-rates.index')->with('success', 'Tax rate created');
    }

    public function edit(TaxRate $taxRate): View
    {
        return view('admin.settings.tax-rates.edit', compact('taxRate'));
    }

    public function update(Request $request, TaxRate $taxRate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'state' => 'nullable|string|max:100',
            'cgst_rate' => 'required|numeric|min:0|max:100',
            'sgst_rate' => 'required|numeric|min:0|max:100',
            'igst_rate' => 'required|numeric|min:0|max:100',
        ]);

        // An unchecked box submits nothing, so taking is_active out of
        // $validated meant the record could be activated but never
        // deactivated. boolean() reads the absent key as false.
        $validated['is_active'] = $request->boolean('is_active');

        $taxRate->update($validated);

        return redirect()->route('admin.settings.tax-rates.index')->with('success', 'Tax rate updated');
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        $taxRate->delete();

        return redirect()->route('admin.settings.tax-rates.index')->with('success', 'Tax rate deleted');
    }
}
