<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
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
        // top-level categories (e.g. T-Shirts, Shirts, Kurtas, Trousers) — never the
        // clicked category's own deeper subcategories. Root pages scope to themselves.
        $isSubPage = $category->parent !== null;
        $scopeCategory = $isSubPage ? $category->parent : $category;

        // Outer bound: the scope category and every descendant beneath it, so products
        // filed under deeper subcategories are still included.
        $categoryIds = $scopeCategory->getAllDescendantIds();

        $query = Product::query()
            ->where('is_active', true)
            ->with(['category', 'brand', 'primaryImage']);

        if ($request->filled('subcategory')) {
            // Explicit sub-category selection ALWAYS filters — even in fallback mode — so
            // ticking a box narrows the products to that category (and its descendants).
            $subSlugs = (array) $request->subcategory;
            $subIds = collect();
            foreach (Category::whereIn('slug', $subSlugs)->get() as $sub) {
                $subIds = $subIds->merge($sub->getAllDescendantIds());
            }
            $subIds = $subIds->unique()->values();
            // Force an empty result (not "no filter") if the slugs resolve to nothing.
            $query->whereIn('category_id', $subIds->isNotEmpty() ? $subIds->all() : [0]);
        } else {
            // Always scope strictly to the clicked category (and its descendants).
            // An empty category renders the view's "No products found" state —
            // never products from elsewhere in the catalogue: the old full-catalogue
            // fallback made every empty category page show unrelated products.
            $query->whereIn('category_id', $isSubPage ? $category->getAllDescendantIds() : $categoryIds);
        }

        // Price filter
        // Sizes live on the variants, colours on the product's Colours list, so
        // each needs its own lookup rather than a column on products.
        if ($request->filled('size')) {
            $sizes = array_filter((array) $request->input('size'));
            $query->whereHas('variants', function ($q) use ($sizes) {
                $q->where('is_active', true)
                  ->where('stock_quantity', '>', 0)
                  ->whereSizeIn($sizes);
            });
        }

        if ($request->filled('colour')) {
            $colours = array_filter((array) $request->input('colour'));
            $query->where(function ($q) use ($colours) {
                foreach ($colours as $colour) {
                    // Matches the Colours JSON, and the legacy colour that older
                    // products still keep on the variant.
                    $q->orWhere('attributes', 'like', '%"' . $colour . '"%')
                      ->orWhereHas('variants', fn ($vq) => $vq->where('attributes', 'like', '%"' . $colour . '"%'));
                }
            });
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
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
        $subcategories = $scopeCategory->children()->where('is_active', true)->withCount('products')->get();

        // A checkbox that can never match anything is worse than no checkbox: it
        // reads as a broken filter. withCount('products') only counts products
        // sitting directly on the sub-category, so total the whole subtree and
        // drop the ones that would always return nothing.
        $filterSubcategories = $subcategories
            ->each(function ($sub) {
                $sub->setAttribute('products_total', Product::query()
                    ->where('is_active', true)
                    ->whereIn('category_id', $sub->getAllDescendantIds())
                    ->count());
            })
            ->filter(fn ($sub) => $sub->products_total > 0)
            ->values();

        // Which sub-category checkboxes are active (default to the clicked category).
        $activeSubcategorySlugs = (array) $request->input('subcategory', $isSubPage ? [$category->slug] : []);

        // Breadcrumbs
        $breadcrumbs = [];
        if ($category->parent) {
            $breadcrumbs[] = ['label' => $category->parent->name, 'url' => route('category.show', $category->parent)];
        }
        $breadcrumbs[] = ['label' => $category->name, 'url' => null];

        return view('categories.show', compact('category', 'products', 'filterSubcategories', 'subcategories', 'activeSubcategorySlugs', 'breadcrumbs'));
    }
}
