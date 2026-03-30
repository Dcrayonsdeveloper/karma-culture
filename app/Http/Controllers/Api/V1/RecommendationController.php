<?php

namespace App\Http\Controllers\Api\V1;

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
            null,
            (int) $request->input('limit', 10)
        );

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function similar(int $productId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->recommendationService->similarProducts($productId),
        ]);
    }

    public function personalized(): JsonResponse
    {
        $products = auth()->check()
            ? $this->recommendationService->personalizedForUser(auth()->id())
            : $this->recommendationService->popularProducts(12);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function popular(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->recommendationService->popularProducts(),
        ]);
    }
}
