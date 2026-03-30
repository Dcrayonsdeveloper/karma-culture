<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function __construct(
        private RecommendationService $recommendationService
    ) {}

    public function recentlyViewed(Request $request): JsonResponse
    {
        $products = $this->recommendationService->recentlyViewed(
            auth()->id(),
            $request->session()->getId(),
            (int) $request->input('limit', 10)
        );

        return response()->json([
            'success' => true,
            'data' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => (float) $p->price,
                'mrp' => (float) $p->mrp,
                'image' => $p->images->first()?->image_path,
                'rating' => (float) $p->rating,
            ]),
        ]);
    }

    public function similar(int $productId): JsonResponse
    {
        $products = $this->recommendationService->similarProducts($productId);

        return response()->json([
            'success' => true,
            'data' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => (float) $p->price,
                'mrp' => (float) $p->mrp,
                'image' => $p->images->first()?->image_path,
                'rating' => (float) $p->rating,
            ]),
        ]);
    }

    public function frequentlyBoughtTogether(int $productId): JsonResponse
    {
        $products = $this->recommendationService->frequentlyBoughtTogether($productId);

        return response()->json([
            'success' => true,
            'data' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => (float) $p->price,
                'mrp' => (float) $p->mrp,
                'image' => $p->images->first()?->image_path,
            ]),
        ]);
    }

    public function personalized(Request $request): JsonResponse
    {
        if (! auth()->check()) {
            $products = $this->recommendationService->popularProducts(12);
        } else {
            $products = $this->recommendationService->personalizedForUser(auth()->id());
        }

        return response()->json([
            'success' => true,
            'data' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => (float) $p->price,
                'mrp' => (float) $p->mrp,
                'image' => $p->images->first()?->image_path,
                'rating' => (float) $p->rating,
            ]),
        ]);
    }
}
