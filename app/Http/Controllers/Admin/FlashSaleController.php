<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Rules\ValidationRules as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FlashSaleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $flashSales = FlashSale::withCount('products')
            ->latest()
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return view('admin.flash-sales.index', compact('flashSales'));
    }

    public function create(): View
    {
        return view('admin.flash-sales.create')->with('allProducts', $this->selectableProducts());
    }

    /**
     * The rule set shared by store() and update().
     *
     * The slug is derived from the name and the column is unique, so two sales
     * called "Weekend Sale" used to collide on insert and surface as a 500
     * rather than as a message on the field the admin can actually fix. The
     * closure turns that integrity error back into validation, which is what it
     * always was.
     *
     * @return array<string, mixed>
     */
    private function rules(?FlashSale $flashSale = null): array
    {
        return [
            'name' => [
                ...V::text(max: 255, min: 2),
                function (string $attribute, mixed $value, \Closure $fail) use ($flashSale): void {
                    $slug = Str::slug((string) $value);

                    if ($slug === '') {
                        $fail('The name must contain at least one letter or number.');

                        return;
                    }

                    $taken = FlashSale::where('slug', $slug)
                        ->when($flashSale, fn ($q) => $q->whereKeyNot($flashSale->getKey()))
                        ->exists();

                    if ($taken) {
                        $fail('Another flash sale already uses this name.');
                    }
                },
            ],
            'description' => V::textarea(required: false, max: 1000),
            'starts_at' => V::scheduleStart(current: $flashSale?->starts_at),
            'ends_at' => V::scheduleEnd('starts_at', current: $flashSale?->ends_at),
            'is_active' => V::boolean(),
            'products' => ['nullable', 'array', 'max:200'],
            'products.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            // decimal(12,2) columns: two places, and a ceiling under the width
            // so a bad number is rejected rather than truncated by the database.
            'products.*.sale_price' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999.99'],
            'products.*.stock_limit' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    private function messages(): array
    {
        return [
            'ends_at.after' => 'The end time must be later than the start time.',
            'starts_at.date' => 'Enter a valid start date and time.',
            'ends_at.date' => 'Enter a valid end date and time.',
        ];
    }

    /**
     * Field names as they read in a message.
     *
     * Without these the schedule rules produce "The starts at cannot be set in
     * the past." - the column name, not the label on the form.
     *
     * @return array<string, string>
     */
    private function attributes(): array
    {
        return [
            'starts_at' => 'start time',
            'ends_at' => 'end time',
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages(), $this->attributes());

        $flashSale = FlashSale::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'is_active' => $request->boolean('is_active'),
        ]);

        // Attach here as well as on edit: making someone save, reopen and save
        // again just to add a product is a needless round trip.
        $flashSale->products()->sync($this->productRows($request));

        return redirect()->route('admin.flash-sales.edit', $flashSale)->with('success', 'Flash sale created');
    }

    /**
     * Turn the repeated form rows into a sync payload, keeping any sold_count
     * a surviving row already had so a running sale does not reset its limits.
     *
     * @return array<int, array<string, mixed>>
     */
    private function productRows(Request $request, ?FlashSale $flashSale = null): array
    {
        $existing = $flashSale?->products->keyBy('id') ?? collect();
        $rows = [];

        foreach ($request->input('products', []) as $row) {
            $productId = (int) ($row['product_id'] ?? 0);

            if ($productId <= 0 || isset($rows[$productId])) {
                continue;
            }

            $rows[$productId] = [
                'sale_price' => (float) ($row['sale_price'] ?? 0),
                'stock_limit' => ($row['stock_limit'] ?? '') === '' ? null : (int) $row['stock_limit'],
                'sold_count' => $existing[$productId]->pivot->sold_count ?? 0,
            ];
        }

        return $rows;
    }

    /** Products a sale can include: live and sellable, cheapest name-first. */
    private function selectableProducts()
    {
        return \App\Models\Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price']);
    }

    public function edit(FlashSale $flashSale): View
    {
        $flashSale->load('products');

        return view('admin.flash-sales.edit', compact('flashSale'))->with('allProducts', $this->selectableProducts());
    }

    public function update(Request $request, FlashSale $flashSale): RedirectResponse
    {
        $validated = $request->validate($this->rules($flashSale), $this->messages(), $this->attributes());

        $flashSale->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $flashSale->products()->sync($this->productRows($request, $flashSale));

        return redirect()->route('admin.flash-sales.edit', $flashSale)->with('success', 'Flash sale updated');
    }

    public function destroy(FlashSale $flashSale): RedirectResponse
    {
        $flashSale->delete();

        return redirect()->route('admin.flash-sales.index')->with('success', 'Flash sale deleted');
    }
}
