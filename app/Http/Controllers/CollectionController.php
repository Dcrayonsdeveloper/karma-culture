<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Support\ProductFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CollectionController extends Controller
{
    /**
     * A collection's page: the shop listing, bound to the ticked products.
     *
     * Reuses ProductFilters and the shop's own view so the sidebar, sorting,
     * pagination and empty state behave exactly as they do everywhere else -
     * a second listing implementation is how two pages start disagreeing about
     * what "In Stock Only" means.
     */
    public function show(Request $request, string $collection): View
    {
        // Resolved here rather than by route model binding, which would run an
        // ordinary Category query - and the global scope hides system rows from
        // those, so binding would 404 the one kind of row this page serves.
        //
        // system() is also the authorisation: /collection/... belongs to the
        // built-in listings only. A real category has its own address at
        // /category/{slug} and is not served from two URLs - that is duplicate
        // content, and RouteIntegrityTest fails the build when two paths answer
        // 200 from the same action.
        $collection = Category::system()->where('slug', $collection)->first();

        if (! $collection) {
            throw new NotFoundHttpException;
        }

        // A deactivated listing is not a 404 for an admin who is checking it,
        // but it must not be browsable by customers - the link is gone from the
        // header the moment it is switched off, and typing the URL matches that.
        if (! $collection->is_active) {
            throw new NotFoundHttpException;
        }

        $request->merge(ProductFilters::tileAliases($request));

        $productIds = $collection->shownProducts()->pluck('products.id')->all();

        $filters = ProductFilters::for(
            $request,
            // whereIn on an empty list would match nothing, which is what an
            // empty collection should show - not the whole shop.
            fn () => Product::query()->where('is_active', true)->whereIn('products.id', $productIds ?: [0]),
            [
                'action' => route('collection.show', $collection),
                'reset' => route('collection.show', $collection),
            ],
        );

        return view('products.index', [
            'products' => $filters->results(),
            'filterPanel' => $filters->facets(),
            'listingTitle' => $collection->name,
            'listingDescription' => $collection->description,
        ]);
    }
}
