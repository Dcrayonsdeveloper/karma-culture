<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $brands,
        ]);
    }

    public function show(Brand $brand): JsonResponse
    {
        if (!$brand->is_active) {
            abort(404);
        }

        return response()->json([
            'data' => $brand,
        ]);
    }
}
