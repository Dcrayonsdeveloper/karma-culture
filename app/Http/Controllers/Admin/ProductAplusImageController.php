<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAplusImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductAplusImageController extends Controller
{
    /** Upload one or more A+ banner images for a product. */
    public function store(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|max:20',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp,gif|max:5120', // 5 MB each
        ]);

        $position = (int) ($product->aplusImages()->max('sort_order') ?? -1);
        $created = [];

        foreach ($request->file('images') as $file) {
            // Capture intrinsic dimensions so the storefront can reserve layout space (no CLS).
            $dimensions = @getimagesize($file->getPathname());
            $path = $file->store('products/aplus', 'public');
            $image = $product->aplusImages()->create([
                'image_path' => $path,
                'width' => $dimensions[0] ?? null,
                'height' => $dimensions[1] ?? null,
                'sort_order' => ++$position,
            ]);
            $created[] = $this->transform($image);
        }

        return response()->json(['success' => true, 'images' => $created]);
    }

    /** Update an image's alt text. */
    public function update(Request $request, ProductAplusImage $aplusImage): JsonResponse
    {
        $data = $request->validate([
            'alt_text' => 'nullable|string|max:255',
        ]);

        $aplusImage->update(['alt_text' => $data['alt_text'] ?? null]);

        return response()->json(['success' => true]);
    }

    /** Delete an A+ image (file + row). */
    public function destroy(ProductAplusImage $aplusImage): JsonResponse
    {
        $this->deleteFile($aplusImage->image_path);
        $aplusImage->delete();

        return response()->json(['success' => true]);
    }

    /** Persist a new order from drag-and-drop / up-down controls. */
    public function reorder(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        foreach ($data['order'] as $position => $id) {
            ProductAplusImage::where('id', $id)
                ->where('product_id', $product->id)
                ->update(['sort_order' => $position]);
        }

        return response()->json(['success' => true]);
    }

    private function transform(ProductAplusImage $image): array
    {
        return [
            'id' => $image->id,
            'url' => $image->image_url,
            'alt_text' => $image->alt_text,
        ];
    }

    private function deleteFile(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $path), '/'));
        }
    }
}
