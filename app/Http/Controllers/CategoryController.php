<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->withCount('products')
            ->orderBy('position')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function show(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        // Any category that sits UNDER a gender root (Men's/Women's) is browsed within
        // its parent's scope, so the sidebar "Sub-categories" filter always lists the
        // top-level categories (e.g. T-Shirts, Shirts, Kurtas, Trousers) - never the
        // clicked category's own deeper subcategories. Root pages scope to themselves.
        $isSubPage = $category->parent !== null;
        $scopeCategory = $isSubPage ? $category->parent : $category;

        // The whole tree in one query. Category::getAllDescendantIds() walks the
        // children relation, which lazy-loads a query per level - and the sidebar needs
        // a subtree per sub-category, so the page was firing dozens of them.
        $tree = Category::query()->get(['id', 'parent_id', 'slug']);
        $childrenByParent = $tree->groupBy('parent_id');
        $descendantIds = function (int $id) use (&$descendantIds, $childrenByParent): array {
            $ids = [$id];
            foreach ($childrenByParent->get($id, collect()) as $child) {
                $ids = array_merge($ids, $descendantIds($child->id));
            }

            return $ids;
        };

        // Outer bound: the scope category and every descendant beneath it, so products
        // filed under deeper subcategories are still included.
        $categoryIds = $descendantIds($scopeCategory->id);

        // The page's own bound before any sidebar tick: the clicked category and its
        // descendants. An empty category renders the view's "No products found" state -
        // never products from elsewhere in the catalogue.
        $pageCategoryIds = $isSubPage ? $descendantIds($category->id) : $categoryIds;

        // Ticked sub-categories resolve to their own subtrees. A sub page lists its
        // SIBLINGS, so a tick replaces the page's bound rather than narrowing it -
        // intersecting the two would always come back empty.
        $subSlugs = array_values(array_filter((array) $request->input('subcategory', [])));
        $subIds = collect();
        foreach ($tree->whereIn('slug', $subSlugs) as $sub) {
            $subIds = $subIds->merge($descendantIds($sub->id));
        }
        $subIds = $subIds->unique()->values();

        $selectedSizes = array_values(array_filter((array) $request->input('size', [])));
        $selectedColours = array_values(array_filter((array) $request->input('colour', [])));
        $selectedBrands = array_values(array_filter((array) $request->input('brand', [])));

        /**
         * The one query behind both the grid and the sidebar.
         *
         * $except names the dimensions to leave out, which is what makes the sidebar
         * live: the Size list is built from everything matching the OTHER filters, so
         * ticking a sub-category immediately reshapes the sizes, colours and brands on
         * offer. A facet never applies its own filter, because that is the only way two
         * sizes can both stay pickable - filtering to XL first would leave XL as the
         * only size the list could ever show.
         *
         * $withinCategoryIds overrides the category bound, for the per-sub-category
         * counts, which answer "how many would I get if I ticked this box".
         */
        $filtered = function (array $except = [], ?array $withinCategoryIds = null) use (
            $request,
            $pageCategoryIds,
            $subIds,
            $subSlugs,
            $selectedSizes,
            $selectedColours,
            $selectedBrands
        ) {
            $query = Product::query()->where('is_active', true);

            if ($withinCategoryIds !== null) {
                $query->whereIn('category_id', $withinCategoryIds);
            } elseif ($subSlugs !== [] && ! in_array('subcategory', $except, true)) {
                // Force an empty result (not "no filter") if the slugs resolve to nothing.
                $query->whereIn('category_id', $subIds->isNotEmpty() ? $subIds->all() : [0]);
            } else {
                $query->whereIn('category_id', $pageCategoryIds);
            }

            // Sizes live on the variants, colours on the product's Colours list, so each
            // needs its own lookup rather than a column on products. Stock is
            // deliberately not part of the size match: a sold-out size still belongs to
            // the product, and "In Stock Only" below is the control for hiding it.
            if ($selectedSizes !== [] && ! in_array('size', $except, true)) {
                $query->whereHas('variants', fn ($q) => $q->where('is_active', true)->whereSizeIn($selectedSizes));
            }

            if ($selectedColours !== [] && ! in_array('colour', $except, true)) {
                $query->where(function ($q) use ($selectedColours) {
                    foreach ($selectedColours as $colour) {
                        // Matches the Colours JSON, and the legacy colour that older
                        // products still keep on the variant.
                        $q->orWhere('attributes', 'like', '%"'.$colour.'"%')
                            ->orWhereHas('variants', fn ($vq) => $vq->where('attributes', 'like', '%"'.$colour.'"%'));
                    }
                });
            }

            // Brand filter. Slugs, not ids, so the URL stays readable and shareable.
            if ($selectedBrands !== [] && ! in_array('brand', $except, true)) {
                $query->whereHas('brand', fn ($q) => $q->whereIn('slug', $selectedBrands));
            }

            if (! in_array('price', $except, true)) {
                if ($request->filled('min_price')) {
                    $query->where('price', '>=', $request->min_price);
                }
                if ($request->filled('max_price')) {
                    $query->where('price', '<=', $request->max_price);
                }
            }

            // Attributes filter (dynamic based on category)
            foreach ($request->except(['page', 'sort', 'brand', 'min_price', 'max_price', 'in_stock', 'on_sale']) as $key => $value) {
                if (str_starts_with($key, 'attr_')) {
                    $attributeSlug = str_replace('attr_', '', $key);
                    $values = is_array($value) ? $value : [$value];
                    $query->whereHas('variants.attributeValues', function ($q) use ($attributeSlug, $values) {
                        $q->whereHas('attribute', function ($aq) use ($attributeSlug) {
                            $aq->where('slug', $attributeSlug);
                        })->whereIn('slug', $values);
                    });
                }
            }

            // In stock filter
            if ($request->boolean('in_stock')) {
                $query->where('stock_quantity', '>', 0);
            }

            // On sale filter (price less than mrp)
            if ($request->boolean('on_sale')) {
                $query->whereNotNull('mrp')->whereColumn('price', '<', 'mrp');
            }

            return $query;
        };

        $query = $filtered()->with(['category', 'brand', 'primaryImage']);

        // Sorting
        $sortBy = $request->get('sort', 'newest');
        match ($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating' => $query->orderBy('rating', 'desc'),
            'bestselling' => $query->orderBy('sales_count', 'desc'),
            'name' => $query->orderBy('name', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $products = $query->paginate(24)->withQueryString();

        // Sub-categories for the sidebar checkbox filter (the scope category's children).
        $subcategories = $scopeCategory->children()->where('is_active', true)->get();

        // Every sub-category is listed, including empty ones, so the sidebar matches the
        // navigation menu and the shape of the catalogue is visible. Hiding the empty
        // ones made it look as though a category the customer had just seen in the menu
        // did not exist. The count covers the whole subtree under the sibling and is
        // measured with the shopper's other filters applied, so it always matches what
        // ticking the box actually returns.
        $filterSubcategories = $subcategories
            ->each(function ($sub) use ($filtered, $descendantIds) {
                $sub->setAttribute('products_total', $filtered([], $descendantIds($sub->id))->count());
            })
            ->values();

        // Sizes carried by the products the shopper is currently looking at. Listing
        // every size in the shop meant a category holding one polo still offered UK 7 to
        // UK 11, and picking one returned nothing. A ticked size is always kept in the
        // list, even once another filter has emptied it out, or it could never be
        // unticked - the shopper would be stranded on a page with no results.
        $filterSizes = ProductVariant::query()
            ->where('is_active', true)
            ->whereIn('product_id', $filtered(['size'])->select('id'))
            ->pluck('name')
            ->map(fn ($n) => ProductVariant::sizeLabel($n))
            ->filter()
            ->merge($selectedSizes)
            ->unique()
            ->sortBy(fn ($s) => ProductVariant::sizeRank($s))
            ->values();

        // Colours get the same treatment, read off the product's own Colours list. The
        // ticked ones are concatenated last so a real swatch hex wins over the
        // hex-less placeholder when unique() collapses the pair.
        $filterColours = $filtered(['colour'])
            ->pluck('attributes')
            ->flatMap(fn ($a) => collect(data_get($a, 'Colours', []))
                ->map(fn ($c) => is_array($c)
                    ? ['name' => trim((string) ($c['name'] ?? '')), 'hex' => $c['hex'] ?? null]
                    : ['name' => trim((string) $c), 'hex' => null]))
            ->filter(fn ($c) => $c['name'] !== '')
            ->concat(collect($selectedColours)->map(fn ($n) => ['name' => $n, 'hex' => null]))
            ->unique('name')
            ->sortBy('name')
            ->values();

        // Brands: only the ones actually stocked among the matching products. Listing all
        // 26 brands in the table meant a category holding two labels still offered every
        // brand in the shop, and picking one of the others returned nothing.
        $filterBrands = Brand::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereIn('id', $filtered(['brand'])->select('brand_id'))
                ->orWhereIn('slug', $selectedBrands))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        // Which sub-category checkboxes are active (default to the clicked category).
        $activeSubcategorySlugs = $subSlugs !== [] ? $subSlugs : ($isSubPage ? [$category->slug] : []);

        // Breadcrumbs
        $breadcrumbs = [];
        if ($category->parent) {
            $breadcrumbs[] = ['label' => $category->parent->name, 'url' => route('category.show', $category->parent)];
        }
        $breadcrumbs[] = ['label' => $category->name, 'url' => null];

        return view('categories.show', compact(
            'category',
            'products',
            'filterSubcategories',
            'filterBrands',
            'filterSizes',
            'filterColours',
            'subcategories',
            'activeSubcategorySlugs',
            'breadcrumbs'
        ));
    }
}
