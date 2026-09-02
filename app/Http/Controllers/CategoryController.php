<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\CategoryTree;
use App\Support\ProductFilters;
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

        // The whole tree in one query, walked in memory - the sidebar needs a subtree
        // per sub-category, and asking the children relation for each fired a query
        // per level.
        $tree = new CategoryTree;

        // Outer bound: the scope category and every descendant beneath it, so products
        // filed under deeper subcategories are still included.
        $scopeIds = $tree->descendantIds($scopeCategory->id);

        // The page's own bound before any sidebar tick: the clicked category and its
        // descendants. An empty category renders the view's "No products found" state -
        // never products from elsewhere in the catalogue.
        $pageIds = $isSubPage ? $tree->descendantIds($category->id) : $scopeIds;

        // Ticked sub-categories resolve to their own subtrees. A sub page lists its
        // SIBLINGS, so a tick REPLACES the page's bound rather than narrowing it -
        // intersecting the two would always come back empty. That is the one piece
        // this page cannot share, so the category bound stays here and everything
        // else - size, colour, brand, price, rating, availability - comes from the
        // shared sidebar, which is why a category now reads exactly like the shop.
        $subIds = $tree->idsForSlugs(ProductFilters::normalise($request)['subcategory']);

        $bound = function (array $except) use ($pageIds, $subIds) {
            $query = Product::query()->where('is_active', true);

            // 'category' means "the caller is supplying its own bound" - the
            // per-sub-category counts below answer "how many would I get if I
            // ticked this box", which is a different bound from the page's.
            if (in_array('category', $except, true)) {
                return $query;
            }

            if ($subIds !== null && ! in_array('subcategory', $except, true)) {
                // Force an empty result (not "no filter") if the slugs resolve to nothing.
                return $query->whereIn('products.category_id', $subIds ?: [0]);
            }

            return $query->whereIn('products.category_id', $pageIds);
        };

        $filters = ProductFilters::for($request, $bound, [
            'action' => route('category.show', $category),
            'reset' => route('category.show', $category),
            'owns_category' => false,
            'tree' => $tree,
        ]);

        $products = $filters->results();

        // Sub-categories for the sidebar checkbox filter (the scope category's
        // children). Every one is listed, including empty ones, so the sidebar matches
        // the navigation menu and the shape of the catalogue is visible. Hiding the
        // empty ones made it look as though a category the customer had just seen in
        // the menu did not exist. The count covers the whole subtree under the sibling
        // and is measured with the shopper's other filters applied, so it always
        // matches what ticking the box actually returns.
        $subcategories = $scopeCategory->children()->where('is_active', true)->get()
            ->each(fn ($sub) => $sub->setAttribute(
                'products_total',
                $filters->query(['category', 'subcategory'])
                    ->reorder()
                    ->whereIn('products.category_id', $tree->descendantIds($sub->id))
                    ->count()
            ))
            ->values();

        // Which sub-category checkboxes are active (default to the clicked category).
        $subSlugs = $filters->values()['subcategory'];
        $activeSubcategorySlugs = $subSlugs !== [] ? $subSlugs : ($isSubPage ? [$category->slug] : []);

        $breadcrumbs = [];
        if ($category->parent) {
            $breadcrumbs[] = ['label' => $category->parent->name, 'url' => route('category.show', $category->parent)];
        }
        $breadcrumbs[] = ['label' => $category->name, 'url' => null];

        return view('categories.show', [
            'category' => $category,
            'products' => $products,
            'subcategories' => $subcategories,
            'breadcrumbs' => $breadcrumbs,
            'filterPanel' => $filters->facets([
                'subcategories' => $subcategories,
                'active_subcategories' => $activeSubcategorySlugs,
                // The tick this page put there itself, as opposed to one the
                // shopper asked for. The sidebar draws it as settled rather than
                // as a checkbox that ignores being clicked.
                'pinned_subcategory' => ($subSlugs === [] && $isSubPage) ? $category->slug : null,
                'empty' => [
                    'title' => 'Nothing in this collection yet',
                    'text' => "There's nothing in this collection yet. Check back soon.",
                    'url' => route('shop'),
                    'label' => 'Browse all products',
                ],
            ]),
        ]);
    }
}
