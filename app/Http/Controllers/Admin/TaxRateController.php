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
        $perPage = request()->input('per_page', 10);
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
            'is_active' => 'boolean',
        ]);

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
            'is_active' => 'boolean',
        ]);

        $taxRate->update($validated);

        return redirect()->route('admin.settings.tax-rates.index')->with('success', 'Tax rate updated');
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        $taxRate->delete();

        return redirect()->route('admin.settings.tax-rates.index')->with('success', 'Tax rate deleted');
    }
}
