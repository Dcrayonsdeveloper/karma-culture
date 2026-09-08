<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FestivalSale;
use App\Models\Product;
use App\Models\ProductImage;
use App\Rules\ValidationRules as V;
use App\Services\FestivalSaleService;
use App\Support\BannerMedia;
use App\Support\ImageWebp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The admin side of a festival sale: a name, one percentage, a banner and a
 * ticked list of products.
 *
 * Saving is not just a write. Switching a sale on rewrites every selected
 * product's price in the catalogue and switching it off puts them back, both
 * through {@see FestivalSaleService}, so the redirect back carries a count of
 * what actually moved rather than a bare "saved".
 */
class FestivalSaleController extends Controller
{
    /** Where a festival banner lands on the public disk. */
    private const BANNER_DIR = 'festival-sales';

    public function __construct(private readonly FestivalSaleService $sales)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $festivalSales = FestivalSale::withCount('products')
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return view('admin.festival-sales.index', compact('festivalSales'));
    }

    public function create(): View
    {
        return view('admin.festival-sales.create', $this->pickerData() + [
            'selectedIds' => collect(old('products', []))->map('intval')->all(),
        ]);
    }

    public function edit(FestivalSale $festivalSale): View
    {
        $festivalSale->load('products:id');

        return view('admin.festival-sales.edit', $this->pickerData() + [
            'festivalSale' => $festivalSale,
            'selectedIds' => old('products') !== null
                ? collect(old('products'))->map('intval')->all()
                : $festivalSale->products->pluck('id')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $sale = new FestivalSale([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'discount_percent' => $validated['discount_percent'],
            'is_active' => $request->boolean('is_active'),
            'show_on_home' => $request->boolean('show_on_home'),
        ]);

        if ($request->hasFile('banner')) {
            $sale->banner_path = ImageWebp::store($request->file('banner'), self::BANNER_DIR);
        }

        if ($request->hasFile('banner_mobile')) {
            $sale->banner_mobile_path = ImageWebp::store($request->file('banner_mobile'), self::BANNER_DIR);
        }

        $sale->save();

        $report = $this->sales->sync($sale, $validated['products'] ?? []);

        return redirect()
            ->route('admin.festival-sales.edit', $sale)
            ->with('success', $this->report('Festival sale created', $report));
    }

    public function update(Request $request, FestivalSale $festivalSale): RedirectResponse
    {
        $validated = $request->validate($this->rules($festivalSale), $this->messages());

        $festivalSale->fill([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'discount_percent' => $validated['discount_percent'],
            'is_active' => $request->boolean('is_active'),
            'show_on_home' => $request->boolean('show_on_home'),
        ]);

        // replace(), not store(): the artwork being superseded goes with it,
        // rather than being left on the disk for good.
        if ($request->hasFile('banner')) {
            $festivalSale->banner_path = ImageWebp::replace(
                $request->file('banner'), self::BANNER_DIR, $festivalSale->banner_path
            );
        } elseif ($request->boolean('remove_banner')) {
            ImageWebp::delete($festivalSale->banner_path);
            $festivalSale->banner_path = null;
        }

        if ($request->hasFile('banner_mobile')) {
            $festivalSale->banner_mobile_path = ImageWebp::replace(
                $request->file('banner_mobile'), self::BANNER_DIR, $festivalSale->banner_mobile_path
            );
        } elseif ($request->boolean('remove_banner_mobile')) {
            ImageWebp::delete($festivalSale->banner_mobile_path);
            $festivalSale->banner_mobile_path = null;
        }

        $festivalSale->save();

        $report = $this->sales->sync($festivalSale, $validated['products'] ?? []);

        return redirect()
            ->route('admin.festival-sales.edit', $festivalSale)
            ->with('success', $this->report('Festival sale updated', $report));
    }

    /**
     * The Live switch, on its own route.
     *
     * The edit form posts the whole record, so a row-level toggle sent through
     * update() would blank every field the button does not carry - the same
     * reason banners have one of these.
     */
    public function toggle(FestivalSale $festivalSale): RedirectResponse
    {
        $festivalSale->update(['is_active' => ! $festivalSale->is_active]);

        $report = $this->sales->reconcile($festivalSale);

        $verb = $festivalSale->is_active ? 'is now live' : 'has ended';

        return back()->with('success', $this->report($festivalSale->name.' '.$verb, $report));
    }

    /**
     * Deleting a sale must give the catalogue its prices back first.
     *
     * The cascade would take the snapshot rows with the sale and leave every
     * product stranded at its discounted price with nothing left that knew what
     * it used to cost.
     */
    public function destroy(FestivalSale $festivalSale): RedirectResponse
    {
        $reverted = $this->sales->revertAll($festivalSale);

        ImageWebp::delete($festivalSale->banner_path);
        ImageWebp::delete($festivalSale->banner_mobile_path);

        $name = $festivalSale->name;
        $festivalSale->delete();

        $message = $reverted > 0
            ? sprintf('%s deleted, and %d %s put back to its normal price.', $name, $reverted, $reverted === 1 ? 'product was' : 'products were')
            : $name.' deleted';

        return redirect()->route('admin.festival-sales.index')->with('success', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?FestivalSale $sale = null): array
    {
        return [
            'name' => [
                ...V::text(max: 255, min: 2),
                function (string $attribute, mixed $value, \Closure $fail) use ($sale): void {
                    $slug = Str::slug((string) $value);

                    if ($slug === '') {
                        $fail('The name must contain at least one letter or number.');

                        return;
                    }

                    $taken = FestivalSale::where('slug', $slug)
                        ->when($sale, fn ($q) => $q->whereKeyNot($sale->getKey()))
                        ->exists();

                    if ($taken) {
                        $fail('Another festival sale already uses this name.');
                    }
                },
            ],
            'description' => V::textarea(required: false, max: 1000),
            // Bounded away from both ends: 0% is not a sale, and 100% would
            // hand the catalogue away for the ₹1 floor the service clamps to.
            'discount_percent' => [...V::percentage(), 'min:1', 'max:95'],
            'banner' => V::image(required: false, maxKb: BannerMedia::MAX_IMAGE_KB, maxWidth: BannerMedia::MAX_IMAGE_EDGE, maxHeight: BannerMedia::MAX_IMAGE_EDGE),
            'banner_mobile' => V::image(required: false, maxKb: BannerMedia::MAX_IMAGE_KB, maxWidth: BannerMedia::MAX_IMAGE_EDGE, maxHeight: BannerMedia::MAX_IMAGE_EDGE),
            'remove_banner' => V::boolean(),
            'remove_banner_mobile' => V::boolean(),
            'is_active' => V::boolean(),
            'show_on_home' => V::boolean(),
            'products' => ['nullable', 'array', 'max:2000'],
            'products.*' => ['integer', 'exists:products,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'discount_percent.required' => 'Enter the discount percentage for this sale.',
            'discount_percent.min' => 'A festival sale has to take at least 1% off.',
            'discount_percent.max' => 'A discount over 95% is almost certainly a typo.',
            'products.max' => 'A single sale can hold at most 2,000 products.',
        ] + V::imageMessages('banner', BannerMedia::MAX_IMAGE_KB)
          + V::imageMessages('banner_mobile', BannerMedia::MAX_IMAGE_KB);
    }

    /**
     * Turn what the service actually did into a sentence.
     *
     * Skips are the part worth saying out loud: a product left at its old price
     * because another live sale already owns it is exactly the case an admin
     * would otherwise only discover by browsing the shop.
     *
     * @param  array{applied: int, reverted: int, skipped: array<int, string>}  $report
     */
    private function report(string $prefix, array $report): string
    {
        $parts = [];

        if ($report['applied'] > 0) {
            $parts[] = $report['applied'].' '.($report['applied'] === 1 ? 'product' : 'products').' repriced';
        }

        if ($report['reverted'] > 0) {
            $parts[] = $report['reverted'].' put back';
        }

        if ($report['skipped'] !== []) {
            $count = count($report['skipped']);
            $parts[] = $count.' skipped ('.reset($report['skipped']).')';
        }

        return $parts === [] ? $prefix : $prefix.' - '.implode(', ', $parts);
    }

    /**
     * The products an admin may tick.
     *
     * is_active alone, deliberately. Product::scopeActive() also demands
     * status=approved and every live product on this shop is status=draft, so
     * using it here would render an empty picker on production while every test
     * passed - the same trap DealsController documents at the top of index().
     */
    /**
     * Everything the picker needs: the tiles, and the category list above them.
     *
     * Both are built here rather than in the template because the tile rows are
     * JSON handed to Alpine, and a Blade file is the wrong place to be resolving
     * 1,231 image URLs and walking a category tree.
     *
     * @return array{pickerProducts: array<int, array<string, mixed>>, pickerCategories: array<int, array<string, mixed>>}
     */
    private function pickerData(): array
    {
        $products = $this->selectableProducts();

        // id => the category's own id plus every ancestor, from Category::$path
        // ("1/5/12"). Giving each product its whole chain is what lets picking a
        // root category match everything filed under its children, without the
        // template knowing anything about the tree.
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'path', 'level']);

        $chains = [];

        foreach ($categories as $category) {
            $chains[$category->id] = array_values(array_filter(array_map(
                'intval',
                explode('/', (string) $category->path)
            )));
        }

        $rows = [];
        $counts = [];

        foreach ($products as $product) {
            $chain = $chains[$product->category_id] ?? [];

            foreach ($chain as $id) {
                $counts[$id] = ($counts[$id] ?? 0) + 1;
            }

            $rows[] = [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'sku' => (string) ($product->sku ?? ''),
                'price' => (float) $product->price,
                'img' => $this->thumbnailUrl($product->thumbnail),
                'cats' => $chain,
            ];
        }

        // Only categories that actually hold something, indented so the tree is
        // still readable in a flat <select>.
        $options = [];

        foreach ($categories as $category) {
            if (($counts[$category->id] ?? 0) === 0) {
                continue;
            }

            $options[] = [
                'id' => (int) $category->id,
                'label' => str_repeat('— ', max(0, (int) $category->level)).$category->name,
                'count' => $counts[$category->id],
            ];
        }

        return ['pickerProducts' => $rows, 'pickerCategories' => $options];
    }

    /**
     * The three shapes a stored image path comes in, same as Product's own
     * accessor: an absolute URL, a web-root path, or a key on the public disk.
     */
    private function thumbnailUrl(?string $path): string
    {
        if (! $path) {
            return asset_v('images/no-product-image.svg');
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return str_starts_with($path, '/')
            ? asset_v(ltrim($path, '/'))
            : asset_v('storage/'.$path);
    }

    private function selectableProducts()
    {
        return Product::query()
            ->where('is_active', true)
            ->select(['id', 'name', 'sku', 'price', 'mrp', 'category_id'])
            // A correlated subquery rather than an eager load. `with(['images'
            // => fn ($q) => $q->limit(1)])` reads as one-image-per-product and
            // is not - the limit applies to the whole eager query, so it
            // returns a single row for the entire page. This gets one thumbnail
            // per product in one query and never loads a catalogue's worth of
            // image rows to show a grid of thumbnails.
            ->addSelect(['thumbnail' => ProductImage::query()
                ->select('url')
                ->whereColumn('product_id', 'products.id')
                ->where(fn ($q) => $q->whereNull('media_type')->orWhere('media_type', '!=', 'video'))
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->limit(1),
            ])
            ->orderBy('name')
            ->get();
    }
}
