<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $products = Product::select('id', 'name')
            ->where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(15)
            ->get();

        return response()->json($products);
    }
}
