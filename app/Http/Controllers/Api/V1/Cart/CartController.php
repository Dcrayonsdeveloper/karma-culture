<?php

namespace App\Http\Controllers\Api\V1\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            // Scoped to the submitted product. `exists:product_variants,id`
            // on its own accepted ANY variant id, including one belonging to a
            // different product - and both checkout paths follow
            // cart_items.variant_id unscoped, so stock was checked and
            // decremented against the foreign variant and the order line was
            // priced from it. A cheap product's variant could be attached to an
            // expensive one and bought at the cheap price.
            'variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')
                    ->where('product_id', $request->input('product_id')),
            ],
        ]);

        $cart = $this->getOrCreateCart($request);
        $product = Product::findOrFail($validated['product_id']);

        if ($product->stock_quantity < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock available',
            ], 422);
        }

        $existingItem = $cart->items()
            ->where('product_id', $product->id)
            ->where('variant_id', $validated['variant_id'] ?? null)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $validated['quantity'],
            ]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'variant_id' => $validated['variant_id'] ?? null,
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
