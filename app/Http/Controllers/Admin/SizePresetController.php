<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SizePreset;
use App\Rules\ValidationRules as V;
use App\Services\SizeRetirementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SizePresetController extends Controller
{
    public function __construct(private readonly SizeRetirementService $sizes) {}

    public function index(): View
    {
        $presets = SizePreset::ordered()->paginate(50)->withQueryString();

        // What each label is actually carrying, so the delete confirmation can
        // say what it is about to take off the shop rather than asking about a
        // word in a list.
        $counts = $this->sizes->counts();

        return view('admin.size-presets.index', compact('presets', 'counts'));
    }

    public function create(): View
    {
        return view('admin.size-presets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        SizePreset::create($this->validated($request));

        return redirect()->route('admin.size-presets.index')->with('success', 'Size added to the library.');
    }

    public function edit(SizePreset $sizePreset): View
    {
        return view('admin.size-presets.edit', ['preset' => $sizePreset]);
    }

    public function update(Request $request, SizePreset $sizePreset): RedirectResponse
    {
        $sizePreset->update($this->validated($request, $sizePreset->id));

        return redirect()->route('admin.size-presets.index')->with('success', 'Size updated.');
    }

    /**
     * Remove the size from the library AND from every product carrying it.
     *
     * Deleting only the library row used to leave the size on the products:
     * it kept selling on the product page and kept its chip on the shop's size
     * rail, because a product's sizes ARE its variant rows and nothing had
     * touched them. The products themselves are never deleted - only the size
     * comes off them. {@see SizeRetirementService} for what happens to the
     * baskets, orders and stock that pointed at those rows.
     */
    public function destroy(SizePreset $sizePreset): RedirectResponse
    {
        // A size the whole catalogue carries is a few thousand rows, and each
        // one has to go through the model so its warehouse shelves are cleared
        // - too slow for the default limit on a box this size. There is no
        // queue worker to hand it to.
        set_time_limit(300);

        $report = $this->sizes->retire($sizePreset->name);

        $sizePreset->delete();

        $redirect = redirect()->route('admin.size-presets.index')
            ->with('success', $this->summarise($sizePreset->name, $report));

        return $report['locked'] === []
            ? $redirect
            : $redirect->with('warning', $this->lockedNotice($report));
    }

    /** @param  array{variants: int, products: int, carts: int, orders: int, locked: array<int, string>}  $report */
    private function summarise(string $name, array $report): string
    {
        if ($report['variants'] === 0) {
            return '"'.$name.'" removed from the library. No product was carrying it.';
        }

        $parts = ['"'.$name.'" removed from the library and taken off '
            .$report['products'].' '.($report['products'] === 1 ? 'product' : 'products')
            .' ('.$report['variants'].' size '.($report['variants'] === 1 ? 'row' : 'rows').').'];

        if ($report['carts'] > 0) {
            $parts[] = $report['carts'].' basket '.($report['carts'] === 1 ? 'line was' : 'lines were').' removed.';
        }

        if ($report['orders'] > 0) {
            // Said out loud because it is the reassuring half: the orders keep
            // their size, their price and their name - only the link goes.
            $parts[] = 'Past orders keep their record of it.';
        }

        return implode(' ', $parts);
    }

    /** @param  array{locked: array<int, string>}  $report */
    private function lockedNotice(array $report): string
    {
        if ($report['locked'] === []) {
            return '';
        }

        return count($report['locked']).' size '.(count($report['locked']) === 1 ? 'row was' : 'rows were')
            .' kept because a stock transfer still refers to '.(count($report['locked']) === 1 ? 'it' : 'them')
            .': '.implode(', ', array_slice($report['locked'], 0, 10)).'.';
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [...V::text(max: 100, min: 1), Rule::unique('size_presets', 'name')->ignore($ignoreId)],
            // Measurements are optional and copied onto the size row as a default.
            'measurements' => ['nullable', 'string', 'max:160', new \App\Rules\NoHtml],
            'is_active' => V::boolean(),
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
    }
}
