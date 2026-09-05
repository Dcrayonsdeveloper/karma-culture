<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RecommendationController extends Controller
{
    public function __construct(
        private RecommendationService $recommendationService
    ) {}

    public function recentlyViewed(Request $request): JsonResponse
    {
        return $this->cards($this->recommendationService->recentlyViewed(
            auth()->id(),
            $request->session()->getId(),
            (int) $request->input('limit', 10)
        ));
    }

    public function similar(int $productId): JsonResponse
    {
        return $this->cards($this->recommendationService->similarProducts($productId));
    }

    public function frequentlyBoughtTogether(int $productId): JsonResponse
    {
        return $this->cards($this->recommendationService->frequentlyBoughtTogether($productId));
    }

    public function personalized(Request $request): JsonResponse
    {
        $products = auth()->check()
            ? $this->recommendationService->personalizedForUser(auth()->id())
            : $this->recommendationService->popularProducts(12);

        return $this->cards($products);
    }

    /**
     * The one shape all four of these answer in.
     *
     * They used to carry four hand-written copies of the same array, and the
     * copies had drifted: bought-together omitted the rating its three siblings
     * sent, and every one of them read $p->images->first()?->image_path for the
     * picture. product_images has no image_path column - it is called url - so
     * that was null for every product on the site and every card in every one
     * of these rails fell back to the "no image" placeholder.
     *
     * primary_image_url is the accessor the rest of the app already emits: it
     * prefers the primary image, skips a video, resolves a bare path against
     * /storage and fingerprints it.
     *
     * The url is here for the same reason it is on the cart, search, wishlist
     * and quick-view payloads: without it the front end has to build a product
     * path out of the slug by hand, and three components did - at /products/,
     * which is now a redirect.
     */
    private function cards(Collection $products): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'url' => route('product.show', $p),
                'price' => (float) $p->price,
                'mrp' => (float) $p->mrp,
                'image' => $p->primary_image_url,
                'rating' => (float) $p->rating,
            ])->values(),
        ]);
    }
}
