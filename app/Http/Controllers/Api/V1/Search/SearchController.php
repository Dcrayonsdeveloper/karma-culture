<?php

namespace App\Http\Controllers\Api\V1\Search;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $request->q;

        $products = Product::where('is_active', true)
            ->where('status', 'approved')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('short_description', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->with(['category:id,name,slug', 'brand:id,name,slug'])
            ->paginate($request->per_page ?? 20);

        return response()->json($products);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $request->q;

        $products = Product::where('is_active', true)
            ->where('status', 'approved')
            ->where('name', 'like', "%{$query}%")
            ->select('id', 'name', 'slug', 'price')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => $products,
        ]);
    }
}
