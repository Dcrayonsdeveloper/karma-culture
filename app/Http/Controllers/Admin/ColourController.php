<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Colour;
use App\Rules\ValidationRules as V;
use App\Support\ShopFilterCatalogue;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The Colours list - the colours the product form OFFERS, not the colours the
 * shop has.
 *
 * Read that distinction before changing anything here, because every screen in
 * this controller looks like it is editing the catalogue and none of it is. A
 * row is a picker entry: a swatch that appears in the list when somebody
 * builds a product. The product then stores its own copy - name and hex, into
 * products.attributes -> "Colours" - and the cart and order lines store the
 * word again. Nothing holds this row's id. So:
 *
 *   - renaming a row renames the swatch on offer and nothing else; every
 *     product already carrying "Indigo" still says Indigo;
 *   - deleting a row takes the swatch off the picker and leaves every product,
 *     cart and order that already used it exactly as it was;
 *   - the usage figure on the index is counted off the live catalogue, not off
 *     this table, which is why a brand new row honestly reads 0.
 *
 * The shop's colour rail is still ShopFilterCatalogue's derivation from the
 * products themselves - and it is filed there under `shade`, which is the name
 * the storefront has always used for it and the reason index() below asks for
 * that type rather than 'colour'.
 */
class ColourController extends Controller
{
    /**
     * The rules for both store() and update().
     *
     * 60 characters is not a taste decision: cart_items.colour and
     * order_items.colour are varchar(60). A longer name could be saved onto a
     * product here and would then be silently truncated - or refused - the
     * first time a shopper tried to add that product to their cart.
     */
    private function rules(?Colour $colour = null): array
    {
        return [
            'name' => [...V::text(max: 60, min: 1), $this->uniqueKey($colour)],
            'description' => V::textarea(required: false, max: 2000),
            // The swatch itself. Deliberately six digits and a leading hash and
            // nothing else: this string is written straight into a style
            // attribute on the storefront, and the three-digit and named forms
            // a browser would also accept are the shapes that make "is this a
            // colour or is this something someone pasted" ambiguous.
            'hex_code' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'image' => V::image(required: false, maxKb: 2048, allowGif: true),
            'is_active' => V::boolean(),
        ];
    }

    private function messages(): array
    {
        return [
            'hex_code.regex' => 'The swatch must be a 6-digit hex colour beginning with #, such as #1A1A1A.',
            ...V::imageMessages('image', 2048),
        ];
    }

    /**
     * Uniqueness, checked on the NORMALISED key rather than the typed spelling.
     *
     * ShopFilterCatalogue::normaliseKey - the same function the model stamps
     * `key` with - trims, collapses whitespace and lower-cases, so "Black",
     * "black" and "Black " are one identity. That is deliberately the same
     * grouping the shop's filter rails use, and it is why a plain
     * unique:colours,name would not do: two rows differing only in case pass a
     * name check, then collapse into ONE hanger on the storefront. The admin is
     * left looking at two rows, editing one of them - a different swatch hex,
     * say - and seeing nothing change, with nothing on the screen to say which
     * of the two the shop is drawing.
     *
     * Rule::unique cannot be pointed at a value other than the field's own, so
     * it is run against the normalised key through a throwaway validator and
     * the failure is reported back on `name` - the box the admin can actually
     * see and correct.
     */
    private function uniqueKey(?Colour $colour): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($colour): void {
            $rule = Rule::unique('colours', 'key')->ignore($colour?->id);
            $key = ShopFilterCatalogue::normaliseKey((string) $value);

            if (Validator::make(['key' => $key], ['key' => [$rule]])->fails()) {
                $fail('That colour is already on the list. Names are matched without case or spacing, so "Black" and "black" would be the same entry.');
            }
        };
    }

    public function index(): View
    {
        $perPage = min(max((int) request()->input('per_page', 10), 1), 100);

        $rows = Colour::ordered()->paginate($perPage)->withQueryString();

        // How many ACTIVE PRODUCTS currently carry each value, keyed by the
        // same normalised key the rows are stored under. It is counted off the
        // catalogue, not off a relationship, because there is no relationship:
        // products spell their colours out rather than pointing at these rows.
        // A row an admin has just added therefore reads 0 quite legitimately,
        // and a row reading 12 is not 12 things that would break if it were
        // deleted - it is 12 products that would simply keep the word.
        // 'shade' rather than 'colour' is not a typo: that is the rail's name
        // on the storefront, and ShopFilterCatalogue::TYPES spells it that way.
        // includeHidden, because a colour an admin has hidden from the shop
        // rail is still a colour products are carrying and this screen has to
        // say so.
        $usage = collect(ShopFilterCatalogue::values('shade', includeHidden: true))
            ->mapWithKeys(fn ($v) => [$v->key => $v->count])
            ->all();

        return view('admin.colours.index', compact('rows', 'usage'));
    }

    public function create(): View
    {
        return view('admin.colours.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(), $this->messages());
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_url'] = $request->file('image')->store('colours', 'public');
        }

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
        // colour an admin just typed jumping to the top of the picker.
        $data['position'] = (Colour::max('position') ?? 0) + 1;

        Colour::create($data);

        return redirect()->route('admin.colours.index')->with('success', 'Colour added to the picker.');
    }

    public function edit(Colour $colour): View
    {
        return view('admin.colours.edit', ['row' => $colour]);
    }

    public function update(Request $request, Colour $colour): RedirectResponse
    {
        $data = $request->validate([
            ...$this->rules($colour),
            'remove_image' => V::boolean(),
        ], $this->messages());
        unset($data['image'], $data['remove_image']);

        // A new upload replaces the old file; the explicit remove checkbox
        // sends the row back to its hex-only swatch. Either way the orphan is
        // deleted rather than left behind on disk - the failure mode this is
        // guarding against is a picker whose swatches have been re-uploaded a
        // dozen times leaving a dozen dead files under storage/colours.
        if ($request->hasFile('image')) {
            if ($colour->image_url) {
                Storage::disk('public')->delete($colour->image_url);
            }
            $data['image_url'] = $request->file('image')->store('colours', 'public');
        } elseif ($request->boolean('remove_image') && $colour->image_url) {
            Storage::disk('public')->delete($colour->image_url);
            $data['image_url'] = null;
        }

        $data['is_active'] = $request->boolean('is_active');

        $colour->update($data);

        return redirect()->route('admin.colours.index')
            ->with('success', 'Colour updated. Products already using the old spelling keep it - they store their own copy.');
    }

    /**
     * Delete a picker entry.
     *
     * This is safe by construction, and it is worth saying out loud because it
     * looks like it should not be. Nothing has a foreign key to this row: a
     * product's colours live in its own attributes JSON, and cart_items.colour
     * and order_items.colour are plain varchar columns holding the word itself.
     * Removing the row takes the swatch off the picker; the storefront, every
     * open cart and every historical order are untouched.
     *
     * The uploaded swatch image IS this row's own file, though, so it goes with
     * it rather than being orphaned on the public disk.
     */
    public function destroy(Colour $colour): RedirectResponse
    {
        if ($colour->image_url) {
            Storage::disk('public')->delete($colour->image_url);
        }

        $colour->delete();

        return redirect()->route('admin.colours.index')
            ->with('success', 'Colour removed from the picker. Products, carts and orders already using it are unchanged.');
    }

    /**
     * Offer this colour on the product form, or stop offering it.
     *
     * Hiding is the gentler alternative to deleting for a colour being retired:
     * it disappears from new products without disturbing the ones that have it.
     */
    public function toggle(Colour $colour): RedirectResponse
    {
        $colour->update(['is_active' => ! $colour->is_active]);

        return back()->with('success', $colour->is_active
            ? '"'.$colour->name.'" is offered on the product form again.'
            : '"'.$colour->name.'" is hidden from the product form. Products already using it are unchanged.');
    }

    public function move(Colour $colour, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $this->swapPosition($colour, $direction);

        return back()->with('success', 'Colour order updated.');
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
    private function swapPosition(Colour $row, string $direction): void
    {
        $up = $direction === 'up';

        $neighbour = Colour::query()
            ->when(
                $up,
                fn ($q) => $q->where('position', '<', $row->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $row->position)->orderBy('position'),
            )
            ->first();

        if (! $neighbour) {
            $neighbour = Colour::query()
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
