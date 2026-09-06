<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Size;
use App\Rules\ValidationRules as V;
use App\Support\ShopFilterCatalogue;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The Sizes list - the sizes the product form OFFERS, not the sizes the shop has.
 *
 * Read that distinction before changing anything here, because every screen in
 * this controller looks like it is editing the catalogue and none of it is. A
 * row is a picker entry: a word that appears in the dropdown when somebody
 * builds a product. The product then stores its own copy of the word, the
 * variant stores a copy, the cart line stores a copy and the order line stores
 * a copy - none of them holds this row's id. So:
 *
 *   - renaming a row renames the dropdown entry and nothing else; every
 *     product already carrying "Medium" still says Medium;
 *   - deleting a row takes the word off the dropdown and leaves every product,
 *     cart and order that already used it exactly as it was;
 *   - the usage figure on the index is counted off the live catalogue, not off
 *     this table, which is why a brand new row honestly reads 0.
 *
 * What the shop's size rail draws is still ShopFilterCatalogue's derivation
 * from the products themselves. This table only decides what an admin is
 * offered while typing.
 */
class SizeController extends Controller
{
    /**
     * The rules for both store() and update().
     *
     * 50 characters is not a taste decision: cart_items.size and
     * order_items.size are varchar(50). A longer name could be saved onto a
     * product here and would then be silently truncated - or refused - the
     * first time a shopper tried to add that product to their cart.
     */
    private function rules(?Size $size = null): array
    {
        return [
            'name' => [...V::text(max: 50, min: 1), $this->uniqueKey($size)],
            'description' => V::textarea(required: false, max: 2000),
            'is_active' => V::boolean(),
        ];
    }

    /**
     * Uniqueness, checked on the NORMALISED key rather than the typed spelling.
     *
     * ShopFilterCatalogue::normaliseKey - the same function the model stamps
     * `key` with - trims, collapses whitespace and lower-cases, so "Small",
     * "small" and "Small " are one identity. That is deliberately the same
     * grouping the shop's filter rails use, and it is why a plain
     * unique:sizes,name would not do: two rows differing only in case pass a
     * name check, then collapse into ONE hanger on the storefront. The admin is
     * left looking at two rows, editing one of them, and seeing nothing change,
     * with nothing on the screen to say which of the two the shop is drawing.
     *
     * Rule::unique cannot be pointed at a value other than the field's own, so
     * it is run against the normalised key through a throwaway validator and
     * the failure is reported back on `name` - the box the admin can actually
     * see and correct.
     */
    private function uniqueKey(?Size $size): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($size): void {
            $rule = Rule::unique('sizes', 'key')->ignore($size?->id);
            $key = ShopFilterCatalogue::normaliseKey((string) $value);

            if (Validator::make(['key' => $key], ['key' => [$rule]])->fails()) {
                $fail('That size is already on the list. Names are matched without case or spacing, so "Small" and "small" would be the same entry.');
            }
        };
    }

    public function index(): View
    {
        $perPage = min(max((int) request()->input('per_page', 10), 1), 100);

        $rows = Size::ordered()->paginate($perPage)->withQueryString();

        // How many ACTIVE PRODUCTS currently carry each value, keyed by the
        // same normalised key the rows are stored under. It is counted off the
        // catalogue, not off a relationship, because there is no relationship:
        // products spell their sizes out rather than pointing at these rows.
        // A row an admin has just added therefore reads 0 quite legitimately,
        // and a row reading 12 is not 12 things that would break if it were
        // deleted - it is 12 products that would simply keep the word.
        // includeHidden, because a size an admin has hidden from the shop rail
        // is still a size products are carrying and this screen has to say so.
        $usage = collect(ShopFilterCatalogue::values('size', includeHidden: true))
            ->mapWithKeys(fn ($v) => [$v->key => $v->count])
            ->all();

        return view('admin.sizes.index', compact('rows', 'usage'));
    }

    public function create(): View
    {
        return view('admin.sizes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        // The forms post a hidden 0 ahead of the checkbox - the shape every
        // other admin form here uses - so an unticked box arrives as "0"
        // rather than not arriving at all. Read through boolean() rather than
        // out of $data because a nullable rule drops an absent key entirely,
        // which on update() would quietly keep the old value instead of
        // saving the untick.
        $data['is_active'] = $request->boolean('is_active', true);

        // Land new rows at the END of the list. Every row created before this
        // screen existed carries position 0, so a new row taking the default
        // would join that pile and sort by name inside it - which reads as the
        // size an admin just typed jumping to the top of the dropdown.
        $data['position'] = (Size::max('position') ?? 0) + 1;

        Size::create($data);

        return redirect()->route('admin.sizes.index')->with('success', 'Size added to the picker.');
    }

    public function edit(Size $size): View
    {
        return view('admin.sizes.edit', ['row' => $size]);
    }

    public function update(Request $request, Size $size): RedirectResponse
    {
        $data = $request->validate($this->rules($size));

        $data['is_active'] = $request->boolean('is_active');

        $size->update($data);

        return redirect()->route('admin.sizes.index')
            ->with('success', 'Size updated. Products already using the old spelling keep it - they store their own copy.');
    }

    /**
     * Delete a picker entry.
     *
     * This is safe by construction, and it is worth saying out loud because it
     * looks like it should not be. Nothing has a foreign key to this row: a
     * product's sizes are its own variant names, and cart_items.size and
     * order_items.size are plain varchar columns holding the word itself.
     * Removing the row takes the size off the dropdown; the storefront, every
     * open cart and every historical order are untouched.
     */
    public function destroy(Size $size): RedirectResponse
    {
        $size->delete();

        return redirect()->route('admin.sizes.index')
            ->with('success', 'Size removed from the picker. Products, carts and orders already using it are unchanged.');
    }

    /**
     * Offer this size on the product form, or stop offering it.
     *
     * Hiding is the gentler alternative to deleting for a size being retired:
     * it disappears from new products without disturbing the ones that have it.
     */
    public function toggle(Size $size): RedirectResponse
    {
        $size->update(['is_active' => ! $size->is_active]);

        return back()->with('success', $size->is_active
            ? '"'.$size->name.'" is offered on the product form again.'
            : '"'.$size->name.'" is hidden from the product form. Products already using it are unchanged.');
    }

    public function move(Size $size, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $this->swapPosition($size, $direction);

        return back()->with('success', 'Size order updated.');
    }

    /**
     * Move a row one place by swapping `position` with its nearest neighbour.
     *
     * Lifted from HomepageController::swapPosition(), tie-break included,
     * because the tie-break is the part that is not obvious: rows that predate
     * this screen all sit at position 0, the strict < / > comparison skips them
     * entirely, and without the fallback the arrows would appear to do nothing
     * at all on exactly the rows an admin most wants to reorder.
     */
    private function swapPosition(Size $row, string $direction): void
    {
        $up = $direction === 'up';

        $neighbour = Size::query()
            ->when(
                $up,
                fn ($q) => $q->where('position', '<', $row->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $row->position)->orderBy('position'),
            )
            ->first();

        if (! $neighbour) {
            $neighbour = Size::query()
                ->where('position', $row->position)
                ->when(
                    $up,
                    fn ($q) => $q->where('id', '<', $row->id)->orderByDesc('id'),
                    fn ($q) => $q->where('id', '>', $row->id)->orderBy('id'),
                )
                ->first();
        }

        if (! $neighbour) {
            return;
        }

        $original = $row->position;
        $row->update(['position' => $neighbour->position]);
        $neighbour->update(['position' => $original]);

        // Swapping two equal positions changes nothing visible, so break the tie.
        if ($original === $neighbour->position) {
            $row->update(['position' => $up ? $original - 1 : $original + 1]);
        }
    }
}
