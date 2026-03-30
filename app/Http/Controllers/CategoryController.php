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

        // Get all descendant category IDs
        $categoryIds = collect([$category->id]);
        if ($category->children->count()) {
            $categoryIds = $categoryIds->merge(
                $category->children->pluck('id')
            );
        }

        $query = Product::query()
            ->where('is_active', true)
            ->whereIn('category_id', $categoryIds)
            ->with(['category', 'brand', 'primaryImage']);

        // Subcategory filter
        if ($request->filled('subcategory')) {
            $subSlugs = (array) $request->subcategory;
            $subIds = Category::whereIn('slug', $subSlugs)->pluck('id');
            if ($subIds->isNotEmpty()) {
                $query->whereIn('category_id', $subIds);
            }
        }

        // Price filter
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

        // Subcategories for filter sidebar
        $filterSubcategories = $category->children()->where('is_active', true)->withCount('products')->get();

        // Subcategories for pill nav
        $subcategories = $filterSubcategories;

        // Breadcrumbs
        $breadcrumbs = [];
        if ($category->parent) {
            $breadcrumbs[] = ['label' => $category->parent->name, 'url' => route('category.show', $category->parent)];
        }
        $breadcrumbs[] = ['label' => $category->name, 'url' => null];

        return view('categories.show', compact('category', 'products', 'filterSubcategories', 'subcategories', 'breadcrumbs'));
    }
}
