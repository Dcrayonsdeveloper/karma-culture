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
            // exists:products,id asks only whether the row is still in the
            // table, and this shop never removes a product row - it soft-deletes
            // it, or switches is_active off. So a withdrawn or deleted product
            // passed validation and then died in findOrFail one line later, and
            // the caller was handed a 404 whose body names the model class and
            // the id they sent: a leak, and the wrong answer besides. The id
            // arrived in a field, so the failure belongs on that field. Scoping
            // the rule to the rows the storefront actually sells turns it into
            // an ordinary 422 on product_id and leaves findOrFail nothing to
            // raise.
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'quantity' => 'required|integer|min:1|max:100',
<<<<<<< HEAD
            // Deliberately NOT exists:product_variants,id. That rule proves the
            // variant row exists, never that it belongs to THIS product, so an
            // id borrowed from another product sailed through it and the stock
            // figure read below described something the customer never chose.
            // The ownership check further down asks the stricter question.
            //
            // min:1 is what makes that swap safe rather than a downgrade. Ids
            // here start at 1, so 0 names no variant - but it is a perfectly
            // good integer and it is not null, which was enough to slip past a
            // guard that asked whether the value was truthy. exists refused 0
            // for free; nothing downstream does, because cart_items.variant_id
            // is a bare unsignedBigInteger with no foreign key behind it. The
            // floor buys that refusal back before the id ever reaches a query.
            'variant_id' => ['nullable', 'integer', 'min:1'],
        ], [
            // Scoping the rule moved the withdrawn-product case OUT of the
            // lookup below and INTO the validator, which answers it in the
            // framework's voice: "The selected product id is invalid." names a
            // request field rather than a thing, and reads as though the app
            // sent something malformed rather than as the shelf being empty.
            // The sentence below is the one the lookup already uses for the
            // same situation a moment later, so the shopper is told the same
            // thing whichever of the two catches it.
            'product_id.exists' => 'That product is no longer available.',
            // The default for that floor is "The variant id field must be at
            // least 1." - a rule talking about itself, in a field name the
            // customer never saw. An out-of-range id and an id belonging to
            // another product are the same thing as far as the person holding
            // the phone is concerned, so they are told the same thing, and the
            // ownership guard below already owns that wording.
            'variant_id.min' => 'That option is no longer available for this product.',
=======
            // Scoped to the submitted product, for the same reason size and
            // colour are checked against the product below.
            // `exists:product_variants,id` on its own accepted ANY variant id,
            // including one belonging to a different product - and both
            // checkout paths follow cart_items.variant_id unscoped, so stock
            // was checked and decremented against the foreign variant and the
            // order line was priced from it. A cheap product's variant could be
            // attached to an expensive one and bought at the cheap price.
            'variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')
                    ->where('product_id', $request->input('product_id')),
            ],
            // Charset and length only - whether these are OPTIONS THIS PRODUCT
            // ACTUALLY OFFERS is checked below, once the product is loaded.
            'size' => V::text(required: false, max: 50),
            'colour' => V::text(required: false, max: 60),
            'texture' => V::text(required: false, max: 60),
>>>>>>> e3a8ce0550d8732347a02aa9589f2867ee5b491f
        ]);

        $cart = $this->getOrCreateCart($request);

<<<<<<< HEAD
        // find(), not findOrFail(): the rule above already proved this id names
        // a sellable product, so a miss here can only be a race with an admin
        // withdrawing it mid-request - which deserves the same field message as
        // any other unusable product_id, not a ModelNotFoundException rendered
        // as a 404 with the class name in it.
        $product = Product::where('is_active', true)->find($validated['product_id']);

        if (! $product) {
            return $this->failed('That product is no longer available.', [
                'product_id' => 'That product is no longer available.',
            ]);
        }

        $variantId = $validated['variant_id'] ?? null;

        // The variant has to be one of THIS product's active rows - the same
        // question the web cart asks before it trusts a variant id.
        //
        // Both tests below ask whether the field was SENT, not whether its
        // value happens to be truthy, and the distinction is the whole point.
        // Read for truthiness, variant_id: 0 means "no variant chosen": the
        // lookup never runs, the refusal underneath it cannot fire, and the 0
        // is written onto the cart line as though it named a real option. The
        // web cart then looks that line up with variant_id null - which Laravel
        // compiles to whereNull - misses it, and opens a second, duplicate line
        // for the same product; and the 0 is copied onto order_items when the
        // customer checks out. Only a null means the customer picked nothing.
        $variant = $variantId !== null
            ? $product->variants()->where('is_active', true)->find($variantId)
            : null;

        if ($variantId !== null && ! $variant) {
            return $this->failed('That option is no longer available for this product.', [
                'variant_id' => 'That option is no longer available for this product.',
            ]);
        }

        // Past this point the variant is named by the row that was actually
        // found, never by the raw submitted value. The two agree once the guard
        // above has passed, so this changes nothing today - but it means the
        // line the cart matches on and the id it stores both come from a row
        // this product owns, and no later loosening of that guard can leave an
        // unvetted number sitting on the insert.
        $variantId = $variant?->id;

        $existingItem = $cart->items()
            ->where('product_id', $product->id)
            ->where('variant_id', $variantId)
=======
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
>>>>>>> e3a8ce0550d8732347a02aa9589f2867ee5b491f
            ->first();

        // Two faults met on this line. The guard weighed only the INCOMING
        // quantity against stock and ignored what the line already holds, so
        // "add 1" repeated often enough walked a cart line clean past the last
        // unit in stock, every call answering 201 - the rule existed and never
        // fired. And it always read the PRODUCT's stock even when the customer
        // was buying a variant, which keeps its own. What the customer is
        // asking to own after this call is the quantity already on the line
        // plus the new one, measured against the row that holds the stock.
        $available = (int) ($variant ? $variant->stock_quantity : $product->stock_quantity);
        $wanted = ($existingItem->quantity ?? 0) + $validated['quantity'];

        if ($available < $wanted) {
            $message = $this->stockMessage($product->name, $available);

            return $this->failed($message, ['quantity' => $message]);
        }

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $wanted,
            ]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
<<<<<<< HEAD
                'variant_id' => $variantId,
=======
                'variant_id' => $validated['variant_id'] ?? null,
                'size' => $size,
                'colour' => $colour,
                'texture' => $texture,
>>>>>>> e3a8ce0550d8732347a02aa9589f2867ee5b491f
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
            return $this->notYourItem();
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        // Null-safe on both sides, and variant-aware. A cart line outlives the
        // rows it points at - products are soft-deleted, variants are deleted
        // outright - so reading ->stock_quantity straight off a missing relation
        // turned "that item is gone" into a 500 on an ordinary quantity change.
        // Reading the product's stock for a variant line was the same mistake
        // addItem made: the number came from the wrong row.
        $available = (int) ($cartItem->variant_id
            ? ($cartItem->variant?->stock_quantity ?? 0)
            : ($cartItem->product?->stock_quantity ?? 0));

        if ($available < $validated['quantity']) {
            $message = $this->stockMessage($cartItem->product?->name, $available);

            return $this->failed($message, ['quantity' => $message]);
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
            return $this->notYourItem();
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

    /**
     * One body for every failure this controller reports.
     *
     * The API answered a broken rule three different ways: the framework's own
     * 422 is {message, errors}, these endpoints returned a bare {message} with
     * no field map at all, and the checkout endpoints return {success, message}.
     * No client can write a single renderer against that, so a message that had
     * a field to go to ended up in a toast - or nowhere. Every failure now
     * carries the two keys the framework itself sends: `message` for the
     * sentence, and `errors` for the {field: [messages]} map that lets the
     * caller put each one under the input it belongs to. `success` is kept
     * alongside because the checkout endpoints already promise it.
     *
     * @param  array<string, string>  $fields
     */
    protected function failed(string $message, array $fields = [], int $status = 422): JsonResponse
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($fields)) {
            // Laravel sends an ARRAY of messages per field and every client
            // unwraps the first one, so a single sentence is wrapped rather
            // than sent bare - otherwise the caller reads a string a character
            // at a time.
            $body['errors'] = array_map(fn (string $text) => [$text], $fields);
        }

        return response()->json($body, $status);
    }

    /**
     * A cart line that belongs to somebody else.
     *
     * abort(403) with no message produces a 403 whose body carries an empty
     * message, so the client had nothing to display and fell back to its own
     * generic wording, or to silence. The reason is not a secret worth an empty
     * body either: the caller has either sent a stale item id or is poking at a
     * stranger's cart, and the same harmless sentence answers both without
     * confirming which.
     */
    protected function notYourItem(): JsonResponse
    {
        return $this->failed('This item is not in your cart.', [], 403);
    }

    /**
     * The one wording for "there is not enough of this to sell you".
     *
     * The same rule used to speak twice: "Insufficient stock available" when
     * adding to the cart, and '"X" only has N item(s) in stock.' at checkout -
     * so a customer who met it in both places was told two different things
     * about one fact, and the cart's version named neither the product nor how
     * many were left. This is the checkout's wording, which does both.
     *
     * Deliberately identical to Api\V1\CheckoutController::stockMessage(). The
     * two have no shared home to live in yet, so they must be changed together.
     */
    protected function stockMessage(?string $product, int $available): string
    {
        $name = trim((string) $product);
        $subject = $name !== '' ? "\"{$name}\"" : 'This item';

        return $available > 0
            ? "{$subject} only has {$available} item(s) in stock."
            : "{$subject} is out of stock.";
    }

    protected function getOrCreateCart(Request $request): Cart
    {
        return Cart::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['session_id' => null]
        );
    }
}
