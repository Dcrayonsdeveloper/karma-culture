<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAplusImage;
use App\Rules\ValidationRules as V;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductAplusImageController extends Controller
{
    /** Upload one or more A+ banner images for a product. */
    public function store(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'max:20'],
            // 5 MB each. V::image() adds the sniffed mimetypes check that
            // `mimes` alone (filename only) does not perform.
            //
            // The height ceiling is raised from the shared default: an A+ banner
            // is often one long stacked infographic, and 5000px would reject a
            // legitimate one. Width stays capped, and the 5 MB limit still
            // rules out a decompression bomb.
            'images.*' => V::image(maxKb: 5120, allowGif: true, maxHeight: 15000),
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

    /** Update an image's alt text and/or its display size. */
    public function update(Request $request, ProductAplusImage $aplusImage): JsonResponse
    {
        $sizeRule = ['nullable', 'string', 'max:20', 'regex:'.ProductAplusImage::DISPLAY_SIZE_REGEX];

        $request->validate([
            'alt_text' => V::text(required: false, max: 255),
            'display_width' => $sizeRule,
            'display_height' => $sizeRule,
        ], [
            'display_width.regex' => 'Width must be a number, a length like 600px or 50%, or "auto".',
            'display_height.regex' => 'Height must be a number, a length like 400px or 50%, or "auto".',
        ]);

        // Only touch fields the request actually carried: the admin UI PATCHes
        // alt text and size independently, and a blanket assignment would wipe
        // whichever field was not part of that particular request.
        $updates = [];

        foreach (['alt_text', 'display_width', 'display_height'] as $field) {
            if (! $request->has($field)) {
                continue;
            }

            $value = $request->input($field);
            $value = is_string($value) ? trim($value) : $value;

            // Empty clears the override, restoring the responsive default.
            $updates[$field] = ($value === '' || $value === null) ? null : $value;
        }

        if ($updates) {
            $aplusImage->update($updates);
        }

        return response()->json([
            'success' => true,
            'image' => $this->transform($aplusImage->fresh()),
        ]);
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
            'order' => ['required', 'array', 'max:200'],
            'order.*' => ['integer', 'min:1'],
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
            // '' rather than null so Alpine binds them to empty text inputs.
            'display_width' => $image->display_width ?? '',
            'display_height' => $image->display_height ?? '',
        ];
    }

    private function deleteFile(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete(ltrim(str_replace('/storage/', '', $path), '/'));
        }
    }
}
