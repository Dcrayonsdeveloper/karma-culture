<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    public function index(): View
    {
        // Straight off the query string this let ?per_page=999999 pull the
        // whole table into one render.
        $perPage = min(max((int) request()->integer('per_page', 10), 5), 100);
        $currencies = Currency::orderBy('name')->paginate($perPage)->withQueryString();

        return view('admin.settings.currencies.index', compact('currencies'));
    }

    public function create(): View
    {
        return view('admin.settings.currencies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:3|unique:currencies',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        // An unchecked box submits nothing, so taking is_active out of
        // $validated meant the record could be activated but never
        // deactivated. boolean() reads the absent key as false.
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active');

        if ($validated['is_default']) {
            Currency::where('is_default', true)->update(['is_default' => false]);
        }

        Currency::create($validated);

        return redirect()->route('admin.settings.currencies.index')->with('success', 'Currency created');
    }

    public function edit(Currency $currency): View
    {
        return view('admin.settings.currencies.edit', compact('currency'));
    }

    public function update(Request $request, Currency $currency): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:3|unique:currencies,code,' . $currency->id,
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        // An unchecked box submits nothing, so taking is_active out of
        // $validated meant the record could be activated but never
        // deactivated. boolean() reads the absent key as false.
        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active');

        if ($validated['is_default']) {
            Currency::where('is_default', true)->where('id', '!=', $currency->id)->update(['is_default' => false]);
        }

        $currency->update($validated);

        return redirect()->route('admin.settings.currencies.index')->with('success', 'Currency updated');
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        if ($currency->is_default) {
            return back()->withErrors(['error' => 'Cannot delete default currency']);
        }

        $currency->delete();

        return redirect()->route('admin.settings.currencies.index')->with('success', 'Currency deleted');
    }
}
