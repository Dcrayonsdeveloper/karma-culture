<?php

namespace App\Http\Controllers\Api\V1\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn($q) => $q->where('is_active', true)])
            ->orderBy('position')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    public function tree(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn($q) => $q->where('is_active', true)->with('children')])
            ->orderBy('position')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    public function show(Category $category): JsonResponse
    {
        if (!$category->is_active) {
            abort(404);
        }

        $category->load(['children' => fn($q) => $q->where('is_active', true)]);

        return response()->json([
            'data' => $category,
        ]);
    }

    public function products(Request $request, Category $category): JsonResponse
    {
        if (!$category->is_active) {
            abort(404);
        }

        $categoryIds = collect([$category->id]);

        if ($category->children->isNotEmpty()) {
            $categoryIds = $categoryIds->merge($category->children->pluck('id'));
        }

        $products = Product::whereIn('category_id', $categoryIds)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->with(['brand:id,name,slug'])
            ->paginate($request->per_page ?? 20);

        return response()->json($products);
    }
}
