<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function show(Page $page): JsonResponse
    {
        if (!$page->is_published) {
            abort(404);
        }

        return response()->json([
            'data' => $page,
        ]);
    }
}
