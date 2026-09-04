<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Rules\ValidationRules as V;
use App\Support\ProductOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        // images is a relation, so it cannot ride in the column list - the query
        // asked products for an "images" column and this 500'd for any cart that
        // had something in it.
        $cart->load(['items.product:id,name,slug,price,mrp,stock_quantity', 'items.product.images']);

        return response()->json([
            'data' => $cart,
            'summary' => [
                'subtotal' => $cart->items->sum(fn($item) => $item->price * $item->quantity),
                'item_count' => $cart->items->sum('quantity'),
            ],
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
            'variant_id' => 'nullable|exists:product_variants,id',
            // Charset and length only - whether these are OPTIONS THIS PRODUCT
            // ACTUALLY OFFERS is checked below, once the product is loaded.
            'size' => V::text(required: false, max: 50),
            'colour' => V::text(required: false, max: 60),
            'texture' => V::text(required: false, max: 60),
        ]);

        $cart = $this->getOrCreateCart($request);
        $product = Product::findOrFail($validated['product_id']);

        $size = $validated['size'] ?? null;
        $colour = $validated['colour'] ?? null;
        $texture = $validated['texture'] ?? null;

        // These three are free-text POST fields that get written to the cart
        // line, carried onto order_items and printed on the invoice. Bounding
        // the charset is not enough - "Size: XXXL" for a product sold only in
        // S/M, or any string at all, was accepted and shipped. They are held to
        // the same lists the product page renders, through the same helper the
        // web cart validates against, so the two doors cannot disagree.
        $options = ProductOptions::for($product);

        $checks = [
            'size' => [$size, $options->sizes, fn (string $v) => $options->offersSize($v)],
            'colour' => [$colour, $options->colourNames(), fn (string $v) => $options->offersColour($v)],
            'texture' => [$texture, $options->textures, fn (string $v) => $options->offersTexture($v)],
        ];

        foreach ($checks as $field => [$chosen, $offered, $accepts]) {
            if ($chosen === null || $accepts($chosen)) {
                continue;
            }

            return response()->json([
                'message' => $offered->isEmpty()
                    ? 'This product is not sold by '.$field.'.'
                    : 'That '.$field.' is not available for this product.',
            ], 422);
        }

        if ($product->stock_quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock available',
            ], 422);
        }

        // Same product + variant + size + colour + texture = the same line. The
        // lookup matched on product and variant alone, so a second texture of a
        // size already in the cart found that row, failed to merge into it and
        // hit the cart_items_line_texture_unique index as a 1062.
        $existingItem = $cart->items()
            ->where('product_id', $product->id)
            ->where('variant_id', $validated['variant_id'] ?? null)
            ->where('size', $size)
            ->where('colour', $colour)
            ->where('texture', $texture)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $validated['quantity'],
            ]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'variant_id' => $validated['variant_id'] ?? null,
                'size' => $size,
                'colour' => $colour,
                'texture' => $texture,
                'quantity' => $validated['quantity'],
                'price' => $product->price,
            ]);
        }

        return response()->json([
            'message' => 'Item added to cart',
        ], 201);
    }

    public function updateItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);

        if ($cartItem->cart_id !== $cart->id) {
            abort(403);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        if ($cartItem->product->stock_quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock available',
            ], 422);
        }

        $cartItem->update($validated);

        return response()->json([
            'message' => 'Cart item updated',
        ]);
    }

    public function removeItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);

        if ($cartItem->cart_id !== $cart->id) {
            abort(403);
        }

        $cartItem->delete();

        return response()->json([
            'message' => 'Item removed from cart',
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $cart->items()->delete();

        return response()->json([
            'message' => 'Cart cleared',
        ]);
    }

    protected function getOrCreateCart(Request $request): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['session_id' => null]
        );
    }
}
